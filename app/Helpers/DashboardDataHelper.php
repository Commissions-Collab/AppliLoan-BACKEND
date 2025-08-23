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

        for ($month = 1; $month <= 12; $month++) {
            $date = Carbon::createFromDate($year, $month, 1);

            $current = Loan::whereYear('release_date', $date->year)
                ->whereMonth('release_date', $date->month)
                ->sum('principal_amount');

            $data[] = [
                'month' => $date->format('M'),
                'sales' => $current,
                'year' => $date->year
            ];
        }

        return $data;
    }

    public static function getCurrentMonthRevenue()
    {
        return LoanPayment::whereYear('payment_date', date('Y'))
            ->whereMonth('payment_date', date('n'))
            ->sum('amount_paid');
    }

    public static function getRecentNotications() {
        return Notification::with('notifiable')
            ->where('is_read', false)
            ->latest()
            ->take(4)
            ->get()
            ->map(function ($n) {
                $notifiableValue = null;

                if($n->notifiable) {
                    switch(class_basename($n->notifiable_type)){
                        case 'Product':
                            $notifiableValue = $n->notifiable->name;
                            break;
                        case 'Member':
                            $notifiableValue = $n->notifiable->full_name;
                            break;
                        case 'LoanApplication':
                            $loanNumber = optional($n->notifiable->loan)->loan_number;
                            $memberName = optional($n->notifiable->member)->full_name;

                            $notifiableValue = trim("{$loanNumber} - {$memberName}");
                            break; 
                    }
                }

                return [
                    'id' => $n->id,
                    'title' => $n->title,
                    'description' => $n->description,
                    'created_at' => $n->created_at->diffForHumans(),
                    'notifiable' => $notifiableValue,
                ];
            });
    }
}
