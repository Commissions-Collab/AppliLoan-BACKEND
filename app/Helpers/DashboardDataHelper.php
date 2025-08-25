<?php

namespace App\Helpers;

use App\Models\Loan;
use App\Models\LoanPayment;
use App\Models\Notification;
use Carbon\Carbon;

class DashboardDataHelper
{
    public static function getMonthlySalesOverview($year)
    {
        $data = [];
        $currentYear = Carbon::now()->year;
        $currentMonth = Carbon::now()->month;

        for ($month = 1; $month <= 12; $month++) {
            $date = Carbon::createFromDate($year, $month, 1);

            // Only get data up to current month if it's the current year
            if ($year == $currentYear && $month > $currentMonth) {
                $sales = 0;
            } else {
                $sales = Loan::whereYear('release_date', $date->year)
                    ->whereMonth('release_date', $date->month)
                    ->where('status', '!=', 'closed')
                    ->sum('principal_amount');
            }

            $data[] = [
                'month' => $date->format('M'),
                'sales' => (float) $sales,
                'year' => $date->year,
                'month_number' => $month
            ];
        }

        return $data;
    }

    public static function getCurrentMonthRevenue()
    {
        $revenue = LoanPayment::whereYear('payment_date', Carbon::now()->year)
            ->whereMonth('payment_date', Carbon::now()->month)
            ->sum('amount_paid');

        return (float) $revenue;
    }

    public static function getRecentNotications()
    {
        return Notification::with('notifiable')
            ->where('is_read', false)
            ->latest()
            ->take(5)
            ->get()
            ->map(function ($n) {
                $notifiableValue = null;
                $notifiableType = null;

                if ($n->notifiable) {
                    $notifiableType = class_basename($n->notifiable_type);
                    
                    switch ($notifiableType) {
                        case 'Product':
                            $notifiableValue = $n->notifiable->name ?? 'Unknown Product';
                            break;
                        case 'Member':
                            $notifiableValue = $n->notifiable->full_name ?? 'Unknown Member';
                            break;
                        case 'LoanApplication':
                            $loanNumber = optional($n->notifiable->loan)->loan_number;
                            $memberName = optional($n->notifiable->member)->full_name;
                            
                            if ($loanNumber && $memberName) {
                                $notifiableValue = "{$loanNumber} - {$memberName}";
                            } elseif ($memberName) {
                                $notifiableValue = $memberName;
                            } else {
                                $notifiableValue = 'Loan Application';
                            }
                            break;
                        default:
                            $notifiableValue = 'System Notification';
                    }
                }

                return [
                    'id' => $n->id,
                    'title' => $n->title,
                    'description' => $n->description,
                    'created_at' => $n->created_at->diffForHumans(),
                    'notifiable' => $notifiableValue,
                    'notifiable_type' => $notifiableType,
                    'is_read' => $n->is_read
                ];
            })
            ->toArray();
    }

    public static function getYearlyComparison($currentYear)
    {
        $previousYear = $currentYear - 1;
        
        $currentYearSales = Loan::whereYear('release_date', $currentYear)
            ->where('status', '!=', 'closed')
            ->sum('principal_amount');
            
        $previousYearSales = Loan::whereYear('release_date', $previousYear)
            ->where('status', '!=', 'closed')
            ->sum('principal_amount');

        $growthRate = $previousYearSales > 0 
            ? round((($currentYearSales - $previousYearSales) / $previousYearSales) * 100, 1)
            : 0;

        return [
            'current_year_sales' => (float) $currentYearSales,
            'previous_year_sales' => (float) $previousYearSales,
            'growth_rate' => $growthRate
        ];
    }
}