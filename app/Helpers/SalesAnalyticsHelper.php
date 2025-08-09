<?php

namespace App\Helpers;

use App\Models\LoanApplication;
use App\Models\LoanPayment;
use Carbon\Carbon;

class SalesAnalyticsHelper
{
    public static function getTotalRevenue($year)
    {
        $lastYear = $year - 1;

        $current = LoanPayment::whereYear('payment_date', $year)
            ->sum('amount_paid');

        $previous = LoanPayment::whereYear('payment_date', $lastYear)
            ->sum('amount_paid');

        $growth = $previous > 0 ? (($current - $previous) / $previous) * 100 : 0;

        return [
            'amount' => $current,
            'growth_percentage' => round($growth, 1)
        ];
    }

    public static function getAverageMonthlyRevenue($year)
    {
        $totalRevenue = LoanPayment::whereYear('payment_date', $year)->sum('amount_paid');

        $monthsWithPayments = LoanPayment::whereYear('payment_date', $year)
            ->selectRaw('MONTH(payment_date) as month')
            ->distinct()
            ->count();

        return $monthsWithPayments > 0
            ? round($totalRevenue / $monthsWithPayments, 2)
            : 0;
    }

    public static function getTargetAchievement($year)
    {
        $annualTarget = 100000;

        $currentYearRevenue = LoanPayment::whereYear('payment_date', $year)
            ->sum('amount_paid');

        return round(($currentYearRevenue / $annualTarget) * 100, 2);
    }

    public static function getMonthlyRevenueOverview($year)
    {
        $data = [];

        for ($month = 1; $month <= 12; $month++) {
            $date = Carbon::createFromDate($year, $month, 1);

            $current = LoanPayment::whereYear('payment_date', $date->year)
                ->whereMonth('payment_date', $date->month)
                ->sum('amount_paid');

            $previous = LoanPayment::whereYear('payment_date', $date->year - 1)
                ->whereMonth('payment_date', $date->month)
                ->sum('amount_paid');

            $data[] = [
                'month' => $date->format('M'),
                'revenue' => $current,
                'previous_year' => $previous,
                'year' => $date->year
            ];
        }

        return $data;
    }

    public static function getSalesByCategory($year)
    {
        $applications = LoanApplication::with('product.category')
            ->where('status', 'approved')
            ->whereYear('created_at', $year)
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
