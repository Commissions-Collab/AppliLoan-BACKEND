<?php

namespace App\Helpers;

use App\Models\Member;
use App\Models\MemberAccount;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DividendAnalyticsHelper
{
    public static function getTotalDividendsPaid($year, $quarter = null)
    {
        $distribution = self::getDynamicDividendDistribution($year, $quarter);
        $data = collect($distribution['distribution'])->sum('annual_dividend');
        return round($data, 2);
    }


    public static function getQuarterlyDividends($year, $specificQuarter = null)
    {
        // Get the full year distribution
        $distribution = self::getDynamicDividendDistribution($year, $specificQuarter);
        $data = [];

        // Extract quarterly data from member breakdowns
        for ($q = 1; $q <= 4; $q++) {
            $quarterlyTotal = 0;

            foreach ($distribution['distribution'] as $member) {
                $quarterlyTotal += $member['quarterly_breakdown']["Q{$q}"] ?? 0;
            }

            $data["Q{$q}"] = round($quarterlyTotal, 2);
        }

        return $data;
    }

    public static function getLatestAnnualDividend($year)
    {
        return self::getTotalDividendsPaid($year);
    }

    public static function getAverageYield($year)
    {
        $distribution = self::getDynamicDividendDistribution($year);

        // For payments-based method, calculate yield based on total payments
        if ($distribution['distribution_method'] === 'payments') {
            if ($distribution['total_paid_amount'] <= 0) {
                return 0;
            }
            $result = $distribution['total_dividend_pool'] / $distribution['total_paid_amount'];
        } else {
            // For other methods, use share capital
            if ($distribution['total_share_capital'] <= 0) {
                return 0;
            }
            $result = $distribution['dividend_rate'] > 0
                ? $distribution['dividend_rate']
                : ($distribution['total_dividend_pool'] / $distribution['total_share_capital']);
        }

        return round($result, 2);
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
        // Dividend settings are not persisted for analytics mode. Use a
        // fixed default percentage and payments-based distribution.
        return [
            'distribution_method' => 'payments',
            'dividend_rate' => 0.05, // default 5%
            'is_approved' => true,
        ];
    }

    /** 
     * *The main calculation function
     */
    public static function getDynamicDividendDistribution($year, $quarter = null)
    {
        $dividendSettings = self::getDividendSettings($year, $quarter);

        // Force payments-based distribution across the system per request
        // (ignore existing share-capital based settings so the logic always
        // uses loan payments as the allocation basis).
        $dividendSettings['distribution_method'] = 'payments';
        $isPaymentsMode = true;

    $membersQuery = Member::query()
            ->whereIn('status', ['active', 'approved'])
            ->whereYear('created_at', '<=', $year)
            ->whereExists(function ($query) use ($year, $quarter) {
                $query->select(DB::raw(1))
                    ->from('loan_payments as p')
                    ->join('loan_schedules as s', 'p.schedule_id', '=', 's.id')
                    ->join('loans as l', 'p.loan_id', '=', 'l.id')
                    ->join('loan_applications as la', 'l.loan_application_id', '=', 'la.id')
                    ->whereColumn('la.user_id', 'members.user_id')
                    ->where('p.status', 'approved')
                    ->whereRaw('p.payment_date <= s.due_date')
                    ->where('p.amount_paid', '>', 0);

                if ($quarter) {
                    $start = Carbon::create($year, ($quarter - 1) * 3 + 1, 1)->startOfDay();
                    $end = Carbon::create($year, $quarter * 3, 1)->endOfMonth()->endOfDay();
                    $query->whereBetween('p.payment_date', [$start->toDateString(), $end->toDateString()]);
                } else {
                    $query->whereYear('p.payment_date', '<=', $year);
                }
            });

        $members = $membersQuery->get();
        $activeMembersCount = $members->count();

        // Sum share capital based only on the member.share_capital field
        $totalShareCapital = collect($members)->sum(function ($m) {
            return (float) ($m->share_capital ?? 0);
        });

        $distribution = [];

        // Calculate payment totals for all members
        $paymentsByUser = collect();
        $totalPaidAmount = 0;

        $memberUserIds = $members->pluck('user_id')->filter()->unique()->values()->all();

        if (!empty($memberUserIds)) {
            $paymentsQuery = DB::table('loan_payments as p')
                ->join('loan_schedules as s', 'p.schedule_id', '=', 's.id')
                ->join('loans as l', 'p.loan_id', '=', 'l.id')
                ->join('loan_applications as la', 'l.loan_application_id', '=', 'la.id')
                ->whereIn('la.user_id', $memberUserIds)
                ->where('p.status', 'approved')
                ->whereRaw('p.payment_date <= s.due_date')
                ->where('p.amount_paid', '>', 0)
                ->select('la.user_id', DB::raw('SUM(p.amount_paid) as total_paid'))
                ->groupBy('la.user_id');

            if ($quarter) {
                $start = Carbon::create($year, ($quarter - 1) * 3 + 1, 1)->startOfDay();
                $end = Carbon::create($year, $quarter * 3, 1)->endOfMonth()->endOfDay();
                $paymentsQuery->whereBetween('p.payment_date', [$start->toDateString(), $end->toDateString()]);
            } else {
                $paymentsQuery->whereYear('p.payment_date', '<=', $year);
            }

            $paymentsByUser = collect($paymentsQuery->get())->keyBy('user_id');
            $totalPaidAmount = $paymentsByUser->sum('total_paid');
        }

        // Compute dividend pool as a fixed percentage of total approved on-time payments
        $dividendSettings['payments_total'] = $totalPaidAmount;
        $dividendSettings['total_dividend_pool'] = ($dividendSettings['dividend_rate'] ?? 0.05) * $totalPaidAmount;

        foreach ($members as $member) {
            $shareCapital = (float) ($member->share_capital ?? 0);
            $memberPaid = isset($paymentsByUser[$member->user_id])
                ? (float) $paymentsByUser[$member->user_id]->total_paid
                : 0;

            $augmentedSettings = $dividendSettings;
            $augmentedSettings['payments_total'] = $totalPaidAmount;
            $augmentedSettings['member_paid'] = $memberPaid;

            $dividendData = self::calculateMemberDividend(
                $member,
                $shareCapital,
                $totalShareCapital,
                $augmentedSettings,
                $activeMembersCount,
                $year,
                $quarter
            );

            $distribution[] = array_merge($dividendData, [
                'member_id' => $member->id,
                'member_name' => $member->full_name,
                'membership_date' => Carbon::parse($member->created_at)->format('m d, Y'),
                'account_status' => $member->status,
                'share_capital' => $isPaymentsMode ? null : $shareCapital,
                'member_paid' => $memberPaid,
                'savings_balance' => null,
                'loan_balance' => null,
            ]);
        }

        return [
            'year' => $year,
            'quarter' => $quarter,
            'total_dividend_pool' => round($dividendSettings['total_dividend_pool'], 2),
            'total_share_capital' => $isPaymentsMode ? 0 : $totalShareCapital,
            'total_paid_amount' => $totalPaidAmount,
            'distribution_method' => $dividendSettings['distribution_method'],
            'dividend_rate' => round($dividendSettings['dividend_rate'], 2),
            'members_count' => $activeMembersCount,
            'distribution' => $distribution
        ];
    }

    private static function calculateMemberDividend($member, $shareCapital, $totalShareCapital, $settings, $activeMembersCount, $year, $quarter = null)
    {
        $membershipDate = Carbon::parse($member->created_at);
        $prorationFactor = 1;
        $membershipMonths = 12;

        if ($quarter) {
            $membershipMonths = self::getMembershipMonthsInPeriod($membershipDate, $year, $quarter);
            $prorationFactor = $quarter ? ($membershipMonths / 3) : ($membershipMonths / 12);
            $prorationFactor = min($prorationFactor, 1);
        }

        $annualDividend = 0;

        // Determine dividend rate for percentage-based calculations
        $rate = $settings['dividend_rate'] ?? null;
        if ($rate === null && $totalShareCapital > 0) {
            $rate = $settings['total_dividend_pool'] / $totalShareCapital;
        }

        switch ($settings['distribution_method']) {
            case 'percentage_based':
                $annualDividend = $shareCapital * ($rate ?? 0);
                break;

            case 'proportional':
                $percentage = $totalShareCapital > 0 ? ($shareCapital / $totalShareCapital) : 0;
                $annualDividend = $percentage * $settings['total_dividend_pool'];
                break;

            case 'equal':
                $annualDividend = $activeMembersCount > 0 ? ($settings['total_dividend_pool'] / $activeMembersCount) : 0;
                break;

            case 'hybrid':
                $proportionalPart = $totalShareCapital > 0 ? (($shareCapital / $totalShareCapital) * $settings['total_dividend_pool'] * 0.7) : 0;
                $equalPart = $activeMembersCount > 0 ? ($settings['total_dividend_pool'] * 0.3) / $activeMembersCount : 0;
                $annualDividend = $proportionalPart + $equalPart;
                break;

            case 'payments':
                $totalPaid = $settings['payments_total'] ?? 0;
                $memberPaid = $settings['member_paid'] ?? 0;
                if ($totalPaid > 0) {
                    $annualDividend = ($memberPaid / $totalPaid) * $settings['total_dividend_pool'];
                } else {
                    $annualDividend = 0;
                }
                break;
        }

        // Build quarterly breakdown
        $quarterlyBreakdown = [];
        $totalQuarterlyAmount = 0;

        if ($quarter) {
            for ($q = 1; $q <= 4; $q++) {
                if ($q === $quarter) {
                    $quarterStart = Carbon::create($year, ($q - 1) * 3 + 1, 1);
                    $quarterEnd = Carbon::create($year, $q * 3, 1)->endOfMonth();

                    if ($membershipDate <= $quarterEnd) {
                        $quarterlyAmount = ($annualDividend / 4) * $prorationFactor;
                        $quarterlyBreakdown["Q{$q}"] = round($quarterlyAmount, 2);
                        $totalQuarterlyAmount += $quarterlyAmount;
                    } else {
                        $quarterlyBreakdown["Q{$q}"] = 0;
                    }
                } else {
                    $quarterlyBreakdown["Q{$q}"] = 0;
                }
            }
        } else {
            for ($q = 1; $q <= 4; $q++) {
                $quarterStart = Carbon::create($year, ($q - 1) * 3 + 1, 1);
                $quarterEnd = Carbon::create($year, $q * 3, 1)->endOfMonth();

                if ($membershipDate <= $quarterEnd) {
                    $quarterMonths = self::getMembershipMonthsInPeriod($membershipDate, $year, $q);
                    $quarterProration = min($quarterMonths / 3, 1);

                    $quarterlyAmount = ($annualDividend / 4) * $quarterProration;
                    $quarterlyBreakdown["Q{$q}"] = round($quarterlyAmount, 2);
                    $totalQuarterlyAmount += $quarterlyAmount;
                } else {
                    $quarterlyBreakdown["Q{$q}"] = 0;
                }
            }
        }

        // Calculate dividend_percentage based on distribution method
        $dividendPercentage = 0;
        if ($settings['distribution_method'] === 'payments') {
            $totalPaid = $settings['payments_total'] ?? 0;
            $memberPaid = $settings['member_paid'] ?? 0;
            $dividendPercentage = $totalPaid > 0 ? round(($memberPaid / $totalPaid) * 100, 4) : 0;
        } else {
            $dividendPercentage = $totalShareCapital > 0 ? round(($shareCapital / $totalShareCapital) * 100, 4) : 0;
        }

        return [
            'dividend_percentage' => $dividendPercentage,
            'annual_dividend' => round($totalQuarterlyAmount, 2),
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
            return 0;
        }

        $effectiveStartDate = $membershipDate->max($startOfPeriod);
        return $effectiveStartDate->diffInMonths($endOfPeriod) + 1;
    }

    private static function calculateDefaultDividendPool()
    {
        $totalShareCapital = MemberAccount::sum('current_share_capital');
        $defaultRate = 0.08;
        return $totalShareCapital * $defaultRate;
    }
}
