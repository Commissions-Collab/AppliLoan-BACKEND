<?php

namespace App\Helpers;

use App\Models\Loan;
use App\Models\LoanApplication;
use App\Models\LoanPayment;
use Carbon\Carbon;

class LoanAnalyticsHelper
{
    private static function calculateGrowth($current, $previous)
    {
        if ($previous == 0) {
            return $current > 0 ? 100 : 0;
        }
        return round((($current - $previous) / $previous) * 100, 2);
    }

    private static function getDateRange($period)
    {
        $now = Carbon::now();

        if ($period === 'current_year') {
            $startCurrent = $now->copy()->startOfYear();
            $startPrevious = $now->copy()->subYear()->startOfYear();
            $endPrevious = $now->copy()->subYear()->endOfYear();
        } else {
            $months = $period === 'last_6_months' ? 6 : 12;

            $startCurrent = $now->copy()->subMonths($months - 1)->startOfMonth();
            $startPrevious = $now->copy()->subMonths($months * 2 - 1)->startOfMonth();
            $endPrevious = $now->copy()->subMonths($months)->endOfMonth();
        }

        return [
            'current_start' => $startCurrent,
            'current_end' => $now,
            'previous_start' => $startPrevious,
            'previous_end' => $endPrevious
        ];
    }

    public static function getTotalLoanAmount($period)
    {
        $range = self::getDateRange($period);

        $current = Loan::where('status', 'active')
            ->whereBetween('approval_date', [$range['current_start'], $range['current_end']])
            ->sum('principal_amount');

        $previous = Loan::where('status', 'active')
            ->whereBetween('approval_date', [$range['previous_start'], $range['previous_end']])
            ->sum('principal_amount');

        return [
            'current' => $current,
            'growth_percentage' => self::calculateGrowth($current, $previous)
        ];
    }

    public static function getTotalApplications($period)
    {
        $range = self::getDateRange($period);

        $current = LoanApplication::whereBetween('application_date', [$range['current_start'], $range['current_end']])
            ->count();

        $previous = LoanApplication::whereBetween('application_date', [$range['previous_start'], $range['previous_end']])
            ->count();

        return [
            'current' => $current,
            'growth_percentage' => self::calculateGrowth($current, $previous)
        ];
    }

    public static function getApprovedLoansCount($period)
    {
        $range = self::getDateRange($period);

        $current = Loan::where('status', 'active')
            ->whereBetween('release_date', [$range['current_start'], $range['current_end']])
            ->count();

        $previous = Loan::where('status', 'active')
            ->whereBetween('release_date', [$range['previous_start'], $range['previous_end']])
            ->count();

        return [
            'current' => $current,
            'growth_percentage' => self::calculateGrowth($current, $previous)
        ];
    }

    public static function getPendingLoansCount($period)
    {
        $range = self::getDateRange($period);

        $current = LoanApplication::where('status', 'pending')
            ->whereBetween('application_date', [$range['current_start'], $range['current_end']])
            ->count();

        $previous = LoanApplication::where('status', 'pending')
            ->whereBetween('application_date', [$range['previous_start'], $range['previous_end']])
            ->count();

        return [
            'current' => $current,
            'growth_percentage' => self::calculateGrowth($current, $previous)
        ];
    }

    public static function getLoanPerformance($period)
    {
        $data = [];

        if ($period === 'current_year') {
            $year = Carbon::now()->year;

            for ($month = 1; $month <= 12; $month++) {
                $disbursed = Loan::whereYear('release_date', $year)
                    ->whereMonth('release_date', $month)
                    ->sum('principal_amount');

                $repaid = LoanPayment::whereYear('payment_date', $year)
                    ->whereMonth('payment_date', $month)
                    ->sum('amount_paid');

                $defaulted = Loan::where('status', 'defaulted')
                    ->whereYear('updated_at', $year)
                    ->whereMonth('updated_at', $month)
                    ->sum('principal_amount');

                $data[] = [
                    'month' => Carbon::create()->month($month)->format('M'),
                    'disbursed' => $disbursed,
                    'repaid' => $repaid,
                    'defaulted' => $defaulted
                ];
            }
        } else {
            $months = $period === 'last_6_months' ? 6 : 12;

            for ($i = $months - 1; $i >= 0; $i--) {
                $date = now()->subMonths($i);

                $disbursed = Loan::whereYear('release_date', $date->year)
                    ->whereMonth('release_date', $date->month)
                    ->sum('principal_amount');

                $repaid = LoanPayment::whereYear('payment_date', $date->year)
                    ->whereMonth('payment_date', $date->month)
                    ->sum('amount_paid');

                $defaulted = Loan::where('status', 'defaulted')
                    ->whereYear('updated_at', $date->year)
                    ->whereMonth('updated_at', $date->month)
                    ->sum('principal_amount');

                $data[] = [
                    'month' => $date->format('M'),
                    'disbursed' => $disbursed,
                    'repaid' => $repaid,
                    'defaulted' => $defaulted
                ];
            }
        }

        return $data;
    }

    public static function getLoanDistribution($period)
    {
        $range = self::getDateRange($period);

        $applications = LoanApplication::with('product.category')
            ->where('status', 'approved')
            ->whereBetween('application_date', [$range['current_start'], $range['current_end']])
            ->get();

        $total = $applications->count();

        $grouped = $applications->groupBy(function ($application) {
            return $application->product->category->name ?? 'Others';
        });

        return $grouped->map(function ($group, $category) use ($total) {
            return [
                'category' => $category,
                'percentage' => $total > 0 ? round(($group->count() / $total) * 100, 2) : 0,
                'amount' => $group->sum('applied_amount')
            ];
        })->values();
    }
}
