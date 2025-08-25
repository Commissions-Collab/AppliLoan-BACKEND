<?php

namespace App\Helpers;

use App\Models\DividendSetting;
use App\Models\Member;
use App\Models\MemberAccount;
use Carbon\Carbon;

class DividendAnalyticsHelper
{
    public static function getTotalDividendsPaid($year, $quarter = null)
    {
        $distribution = self::getDynamicDividendDistribution($year, $quarter);
        $data =  collect($distribution['distribution'])->sum('annual_dividend');
        return round($data, 2);
    }

    public static function getLatestAnnualDividend($year)
    {
        return self::getTotalDividendsPaid($year);
    }

    public static function getAverageYield($year)
    {
        $distribution = self::getDynamicDividendDistribution($year);
        if ($distribution['total_share_capital'] <= 0) {
            return 0;
        }
        // Use the rate from the distribution result for consistency
        $result = $distribution['dividend_rate'] > 0 ? $distribution['dividend_rate'] : ($distribution['total_dividend_pool'] / $distribution['total_share_capital']);

        return round($result, 2);
    }

    public static function getQuarterlyDividends($year)
    {
        $quarters = [1, 2, 3, 4];
        $data = [];
        foreach ($quarters as $q) {
            $distribution = self::getDynamicDividendDistribution($year, $q);
            $data["Q{$q}"] = collect($distribution['distribution'])->sum('annual_dividend');
        }
        return $data;
    }

    public static function getAnnualDividendYield($year)
    {
        return round(self::getAverageYield($year), 2);
    }

    public static function getMemberDividendBreakdown($year, $quarter = null)
    {
        $distribution = self::getDynamicDividendDistribution($year, $quarter);
        return $distribution['distribution'];
    }

    /**
     * TODO: Change this based on the requirement of the company
     * @param mixed $year
     * @param mixed $quarter
     */
    public static function getDividendSettings($year, $quarter = null)
    {
        $settings = DividendSetting::where('year', $year)
            ->when($quarter, fn ($query) => $query->where('quarter', $quarter))
            ->first();

        if ($settings) {
            return $settings->toArray(); // Simplified return
        }

        // Default settings
        return [
            'total_dividend_pool' => self::calculateDefaultDividendPool(),
            'distribution_method' => 'percentage_based',
            'dividend_rate' => 0.05, // 5% of share capital
            'is_approved' => false,
        ];
    }
    
    /** 
     * *The main calculation function
    */
    public static function getDynamicDividendDistribution($year, $quarter = null)
    {
        $dividendSettings = self::getDividendSettings($year, $quarter);

        $members = Member::with('account')
            ->where('status', 'active')
            ->whereYear('created_at', '<=', $year)
            ->get();

        $activeMembersCount = $members->count();
        
        $totalShareCapital = $members->sum('account.current_share_capital');

        $distribution = [];

        foreach ($members as $member) {
            if (!$member->account) continue;

            $shareCapital = $member->account->current_share_capital;

            $dividendData = self::calculateMemberDividend(
                $member,
                $shareCapital,
                $totalShareCapital,
                $dividendSettings,
                $activeMembersCount,
                $year,
                $quarter
            );

            $distribution[] = array_merge($dividendData, [
                'member_id' => $member->id,
                'member_name' => $member->full_name,
                'membership_date' => Carbon::parse($member->created_at)->format('m d, Y'),
                'account_status' => $member->status,
                'share_capital' => $shareCapital,
                'savings_balance' => $member->account->savings_balance,
                'loan_balance' => $member->account->regular_loan_balance,
            ]);
        }

        return [
            'year' => $year,
            'quarter' => $quarter,
            'total_dividend_pool' => round($dividendSettings['total_dividend_pool'], 2),
            'total_share_capital' => $totalShareCapital,
            'distribution_method' => $dividendSettings['distribution_method'],
            'dividend_rate' => round($dividendSettings['dividend_rate'], 2),
            'members_count' => $activeMembersCount,
            'distribution' => $distribution
        ];
    }

    // Updated to accept the member count
    private static function calculateMemberDividend($member, $shareCapital, $totalShareCapital, $settings, $activeMembersCount, $year, $quarter = null)
    {
        $membershipMonths = self::getMembershipMonthsInPeriod($member->membership_date, $year, $quarter);
        $prorationFactor = $quarter ? ($membershipMonths / 3) : ($membershipMonths / 12);
        $prorationFactor = min($prorationFactor, 1); // Ensure it doesn't exceed 100%

        $annualDividend = 0;

        switch ($settings['distribution_method']) {
            case 'percentage_based':
                $annualDividend = $shareCapital * $settings['dividend_rate'] * $prorationFactor;
                break;

            case '=ortional':
                $percentage = $totalShareCapital > 0 ? ($shareCapital / $totalShareCapital) : 0;
                $annualDividend = $percentage * $settings['total_dividend_pool'] * $prorationFactor;
                break;

            case 'equal':
                $annualDividend = $activeMembersCount > 0 ? ($settings['total_dividend_pool'] / $activeMembersCount) * $prorationFactor : 0;
                break;

            case 'hybrid':
                $proportionalPart = $totalShareCapital > 0 ? (($shareCapital / $totalShareCapital) * $settings['total_dividend_pool'] * 0.7) : 0;

                $equalPart = $activeMembersCount > 0 ? ($settings['total_dividend_pool'] * 0.3) / $activeMembersCount : 0;
                $annualDividend = ($proportionalPart + $equalPart) * $prorationFactor;
                break;
        }

        // Remainder of the function is mostly the same...
        $quarterlyAmount = $quarter ? $annualDividend : ($annualDividend / 4);
        $quarterlyBreakdown = [];
        if ($quarter) {
            $quarterlyBreakdown["Q{$quarter}"] = round($quarterlyAmount, 2);
        } else {
            for ($q = 1; $q <= 4; $q++) {
                $quarterlyBreakdown["Q{$q}"] = round($quarterlyAmount, 2);
            }
        }

        return [
            'dividend_percentage' => $totalShareCapital > 0 ? round(($shareCapital / $totalShareCapital) * 100, 4) : 0,
            'annual_dividend' => round($annualDividend, 2),
            'quarterly_breakdown' => $quarterlyBreakdown,
            'proration_factor' => round($prorationFactor, 4),
            'membership_months_in_period' => round($membershipMonths, 2),
        ];
    }
    
    private static function getMembershipMonthsInPeriod($membershipDate, $year, $quarter = null)
    {
        $membershipDate = Carbon::parse($membershipDate);
        
        if ($quarter) {
            $startOfPeriod = Carbon::create($year, ($quarter - 1) * 3 + 1, 1)->startOfDay();
            $endOfPeriod = $startOfPeriod->copy()->addMonths(2)->endOfMonth();
        } else {
            $startOfPeriod = Carbon::create($year, 1, 1)->startOfDay();
            $endOfPeriod = Carbon::create($year, 12, 31)->endOfDay();
        }

        if ($membershipDate > $endOfPeriod) {
            return 0; // Joined after the period ended
        }
        
        // Start counting from the later of the two dates
        $effectiveStartDate = $membershipDate->max($startOfPeriod);

        // Calculate the difference in months, adding 1 to include the starting month.
        return $effectiveStartDate->diffInMonths($endOfPeriod) + 1;
    }

    private static function calculateDefaultDividendPool()
    {
        $totalShareCapital = MemberAccount::sum('current_share_capital');
        $defaultRate = 0.08; // 8% of total share capital as dividend pool
        return $totalShareCapital * $defaultRate;
    }
}