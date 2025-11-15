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
        $distribution = self::getDynamicDividendDistribution($year, $specificQuarter);
        $data = [];

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

        if ($distribution['distribution_method'] === 'payments') {
            if ($distribution['total_paid_amount'] <= 0) {
                return 0;
            }
            $result = $distribution['total_dividend_pool'] / $distribution['total_paid_amount'];
        } else {
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

    public static function getDividendSettings($year, $quarter = null)
    {
        return [
            'distribution_method' => 'payments',
            'dividend_rate' => 0.05,
            'is_approved' => true,
        ];
    }

    public static function getDynamicDividendDistribution($year, $quarter = null)
    {
        $dividendSettings = self::getDividendSettings($year, $quarter);
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
                    $query->whereYear('p.payment_date', $year);
                }
            });

        $members = $membersQuery->get();
        $activeMembersCount = $members->count();

        $totalShareCapital = collect($members)->sum(function ($m) {
            return (float) ($m->share_capital ?? 0);
        });

        $distribution = [];
        $memberUserIds = $members->pluck('user_id')->filter()->unique()->values()->all();

        // If viewing full year, calculate per-quarter payments for each member
        if (!$quarter && !empty($memberUserIds)) {
            // Get payments broken down by quarter for all members
            $quarterlyPaymentsByMember = [];
            
            for ($q = 1; $q <= 4; $q++) {
                $start = Carbon::create($year, ($q - 1) * 3 + 1, 1)->startOfDay();
                $end = Carbon::create($year, $q * 3, 1)->endOfMonth()->endOfDay();
                
                $qPayments = DB::table('loan_payments as p')
                    ->join('loan_schedules as s', 'p.schedule_id', '=', 's.id')
                    ->join('loans as l', 'p.loan_id', '=', 'l.id')
                    ->join('loan_applications as la', 'l.loan_application_id', '=', 'la.id')
                    ->whereIn('la.user_id', $memberUserIds)
                    ->where('p.status', 'approved')
                    ->whereRaw('p.payment_date <= s.due_date')
                    ->where('p.amount_paid', '>', 0)
                    ->whereBetween('p.payment_date', [$start->toDateString(), $end->toDateString()])
                    ->select('la.user_id', DB::raw('SUM(p.amount_paid) as total_paid'))
                    ->groupBy('la.user_id')
                    ->get()
                    ->keyBy('user_id');
                
                foreach ($members as $member) {
                    if (!isset($quarterlyPaymentsByMember[$member->user_id])) {
                        $quarterlyPaymentsByMember[$member->user_id] = [];
                    }
                    $quarterlyPaymentsByMember[$member->user_id]["Q{$q}"] = isset($qPayments[$member->user_id]) 
                        ? (float) $qPayments[$member->user_id]->total_paid 
                        : 0;
                }
            }

            // Calculate total payments per quarter across all members
            $quarterlyTotals = [];
            for ($q = 1; $q <= 4; $q++) {
                $quarterlyTotals["Q{$q}"] = 0;
                foreach ($quarterlyPaymentsByMember as $userId => $quarters) {
                    $quarterlyTotals["Q{$q}"] += $quarters["Q{$q}"] ?? 0;
                }
            }

            // Calculate total for year
            $totalPaidAmount = array_sum($quarterlyTotals);
            $dividendSettings['payments_total'] = $totalPaidAmount;
            $dividendSettings['total_dividend_pool'] = ($dividendSettings['dividend_rate'] ?? 0.05) * $totalPaidAmount;

            // Build distribution with quarterly breakdown
            foreach ($members as $member) {
                $shareCapital = (float) ($member->share_capital ?? 0);
                $memberQuarterlyPayments = $quarterlyPaymentsByMember[$member->user_id] ?? [];
                $memberTotalPaid = array_sum($memberQuarterlyPayments);

                $augmentedSettings = $dividendSettings;
                $augmentedSettings['payments_total'] = $totalPaidAmount;
                $augmentedSettings['member_paid'] = $memberTotalPaid;
                $augmentedSettings['quarterly_payments'] = $memberQuarterlyPayments;
                $augmentedSettings['quarterly_totals'] = $quarterlyTotals;

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
                    'member_paid' => $memberTotalPaid,
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

        // Original logic for single quarter view
        $paymentsByUser = collect();
        $totalPaidAmount = 0;

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
            }

            $paymentsByUser = collect($paymentsQuery->get())->keyBy('user_id');
            $totalPaidAmount = $paymentsByUser->sum('total_paid');
        }

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
        $totalPaid = $settings['payments_total'] ?? 0;
        $memberPaid = $settings['member_paid'] ?? 0;
        
        $periodDividend = 0;
        if ($totalPaid > 0) {
            $periodDividend = ($memberPaid / $totalPaid) * $settings['total_dividend_pool'];
        }

        $quarterlyBreakdown = [];

        if ($quarter) {
            // Single quarter view
            for ($q = 1; $q <= 4; $q++) {
                $quarterlyBreakdown["Q{$q}"] = ($q === $quarter) ? round($periodDividend, 2) : 0;
            }
            $annualAmount = $periodDividend;
        } else {
            // Full year view - calculate actual per-quarter dividends
            $quarterlyPayments = $settings['quarterly_payments'] ?? [];
            $quarterlyTotals = $settings['quarterly_totals'] ?? [];
            $annualAmount = 0;
            
            for ($q = 1; $q <= 4; $q++) {
                $qPayment = $quarterlyPayments["Q{$q}"] ?? 0;
                $qTotal = $quarterlyTotals["Q{$q}"] ?? 0;
                
                if ($qTotal > 0) {
                    // Calculate dividend for this specific quarter
                    $qDividendPool = ($settings['dividend_rate'] ?? 0.05) * $qTotal;
                    $qDividend = ($qPayment / $qTotal) * $qDividendPool;
                    $quarterlyBreakdown["Q{$q}"] = round($qDividend, 2);
                    $annualAmount += $qDividend;
                } else {
                    $quarterlyBreakdown["Q{$q}"] = 0;
                }
            }
        }

        $dividendPercentage = $totalPaid > 0 ? round(($memberPaid / $totalPaid) * 100, 4) : 0;

        return [
            'dividend_percentage' => $dividendPercentage,
            'annual_dividend' => round($annualAmount, 2),
            'quarterly_breakdown' => $quarterlyBreakdown,
            'proration_factor' => 1,
            'membership_months_in_period' => $quarter ? 3 : 12,
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
}