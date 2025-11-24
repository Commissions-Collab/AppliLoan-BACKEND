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
            'amount' => floatval($current),
            'growth_percentage' => round($growth, 1)
        ];
    }

    public static function getAverageMonthlyRevenue($year)
    {
        $totalRevenue = LoanPayment::whereYear('payment_date', $year)->sum('amount_paid');

        // Count actual months that have passed in the year
        $currentDate = Carbon::now();
        $yearStart = Carbon::create($year, 1, 1);

        if ($year == $currentDate->year) {
            // For current year, count months up to current month
            $monthsToCount = $currentDate->month;
        } else {
            // For past years, count all 12 months
            $monthsToCount = 12;
        }

        return $monthsToCount > 0
            ? round($totalRevenue / $monthsToCount, 2)
            : 0;
    }

    public static function getTargetAchievement($year)
    {
        $annualTarget = env('TARGET_SALES_ACHIEVEMENT');

        $currentYearRevenue = LoanPayment::whereYear('payment_date', $year)
            ->sum('amount_paid');

        return $annualTarget > 0
            ? round(($currentYearRevenue / $annualTarget) * 100, 2)
            : 0;
    }

    public static function getMonthlyRevenueOverview($year)
    {
        $data = [];

        for ($month = 1; $month <= 12; $month++) {
            $date = Carbon::create($year, $month, 1);

            $current = LoanPayment::whereYear('payment_date', $year)
                ->whereMonth('payment_date', $month)
                ->sum('amount_paid');

            $previous = LoanPayment::whereYear('payment_date', $year - 1)
                ->whereMonth('payment_date', $month)
                ->sum('amount_paid');

            $data[] = [
                'month' => $date->format('M'),
                'revenue' => floatval($current),
                'previous_year' => floatval($previous),
                'year' => $year
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

        if ($total === 0) {
            return [];
        }

        $grouped = $applications->groupBy(function ($application) {
            return $application->product->category->name ?? 'Others';
        });

        return $grouped->map(function ($group, $category) use ($total) {
            return [
                'category' => $category,
                'percentage' => round(($group->count() / $total) * 100, 2),
                'amount' => floatval($group->sum('applied_amount')),
                'count' => $group->count()
            ];
        })->values()->toArray();
    }

    // Debug method to check data availability
    public static function debugSalesData($year)
    {
        $results = [];

        // Check payment data
        $results['total_payments'] = LoanPayment::count();
        $results['payments_this_year'] = LoanPayment::whereYear('payment_date', $year)->count();
        $results['payments_last_year'] = LoanPayment::whereYear('payment_date', $year - 1)->count();

        // Check application data
        $results['total_applications'] = LoanApplication::count();
        $results['approved_this_year'] = LoanApplication::where('status', 'approved')
            ->whereYear('created_at', $year)->count();

        // Sample data
        $results['sample_payments'] = LoanPayment::whereYear('payment_date', $year)
            ->select('id', 'amount_paid', 'payment_date')
            ->limit(5)
            ->get();

        $results['sample_applications'] = LoanApplication::where('status', 'approved')
            ->whereYear('created_at', $year)
            ->with('product.category')
            ->select('id', 'applied_amount', 'status', 'created_at', 'product_id')
            ->limit(5)
            ->get();

        return $results;
    }

    public static function getWeeklySales($year, $month)
    {
        // Get the first day of the specified month
        $startOfMonth = Carbon::create($year, $month, 1)->startOfDay();
        $endOfMonth = Carbon::create($year, $month, 1)->endOfMonth()->endOfDay();

        // Get all weeks in the month
        $weeks = [];
        $currentWeekStart = $startOfMonth->copy()->startOfWeek();
        
        while ($currentWeekStart->lte($endOfMonth)) {
            $weekEnd = $currentWeekStart->copy()->endOfWeek();
            
            // Ensure we don't go beyond the month
            if ($weekEnd->gt($endOfMonth)) {
                $weekEnd = $endOfMonth->copy();
            }
            
            // Ensure we don't start before the month
            $weekStart = $currentWeekStart->lt($startOfMonth) ? $startOfMonth->copy() : $currentWeekStart->copy();
            
            $weekTotal = LoanPayment::whereBetween('payment_date', [$weekStart, $weekEnd])
                ->sum('amount_paid');
            
            $weeks[] = [
                'week' => 'Week ' . (count($weeks) + 1),
                'start_date' => $weekStart->format('M d'),
                'end_date' => $weekEnd->format('M d'),
                'total' => floatval($weekTotal)
            ];
            
            $currentWeekStart->addWeek();
        }

        return $weeks;
    }

    public static function getYearlySales($year)
    {
        $results = [];

        for ($month = 1; $month <= 12; $month++) {
            $monthlyTotal = LoanPayment::whereYear('payment_date', $year)
                ->whereMonth('payment_date', $month)
                ->sum('amount_paid');

            $results[] = [
                'month' => Carbon::create($year, $month, 1)->format('M'),
                'total' => floatval($monthlyTotal)
            ];
        }

        return $results;
    }

    public static function getMonthlySales($year, $month)
    {
        return LoanPayment::whereYear('payment_date', $year)
            ->whereMonth('payment_date', $month)
            ->selectRaw('DATE(payment_date) as date, SUM(amount_paid) as total')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(function ($item) {
                return [
                    'date' => Carbon::parse($item->date)->format('M d'),
                    'total' => floatval($item->total)
                ];
            });
    }
}
