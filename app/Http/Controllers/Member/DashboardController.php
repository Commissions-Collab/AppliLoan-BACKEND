<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\Loan;
use App\Models\LoanPayment;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function dashboardData()
    {
        try {
            $user = Auth::user();
            $member = $user->member;

            // Handle non-members by fetching via user_id
            if (!$member) {
                $loans = Loan::query()
                    ->with([
                        'application.product',
                        'schedules' => function ($query) {
                            $query->orderBy('due_date');
                        },
                        'payments'
                    ])
                    ->whereHas('application', function ($q) use ($user) {
                        $q->where('user_id', $user->id);
                    })
                    ->orderByDesc('created_at')
                    ->get();

                $memberName = $user->full_name ?? 'User';
                $memberNumber = 'N/A';
            } else {
                $loans = $member->loans()
                    ->with([
                        'application.product',
                        'schedules' => function ($query) {
                            $query->orderBy('due_date');
                        },
                        'payments'
                    ])
                    ->orderByDesc('created_at')
                    ->get();

                $memberName = $member->full_name;
                $memberNumber = $member->member_number;
            }

            // Calculate summary statistics using approved payments only
            $activeLoans = $loans->where('status', 'active')->count();
            $closedLoans = $loans->where('status', 'closed')->count();
            $defaultedLoans = $loans->where('status', 'defaulted')->count();

            // Calculate overdue loans
            $overdueLoans = $loans->filter(function ($loan) {
                if ($loan->status !== 'active') return false;

                $nextSchedule = $loan->schedules
                    ->where('status', 'unpaid')
                    ->sortBy('due_date')
                    ->first();

                return $nextSchedule && Carbon::parse($nextSchedule->due_date)->isPast();
            })->count();

            // Calculate total amounts using approved payments only
            $totalPrincipal = $loans->sum('principal_amount');
            $totalPaid = $loans->sum(function ($loan) {
                return $loan->payments->where('status', 'approved')->sum('amount_paid');
            });
            $totalRemaining = $totalPrincipal - $totalPaid;
            $overallProgress = $totalPrincipal > 0 ? round(($totalPaid / $totalPrincipal) * 100, 1) : 0;

            // Calculate total dividends
            $totalDividends = $loans->sum(function ($loan) {
                return $this->calculateDividend($loan);
            });

            // Get next payment date
            $nextPaymentDate = $this->getNextPaymentDate($loans);

            // Get pending down payments count - safely query
            $pendingDownPayments = 0;
            try {
                $loanIds = $loans->pluck('id')->toArray();
                if (!empty($loanIds)) {
                    $pendingDownPayments = LoanPayment::whereIn('loan_id', $loanIds)
                        ->whereNull('schedule_id')
                        ->where('status', 'pending')
                        ->count();
                }
            } catch (\Exception $e) {
                // If status column doesn't exist, set to 0
                $pendingDownPayments = 0;
            }

            // Recent loans with enhanced details
            $recentLoans = $loans->take(5)->map(function ($loan) {
                $approvedPaid = $loan->payments->where('status', 'approved')->sum('amount_paid');
                $progress = $loan->principal_amount > 0 ? round(($approvedPaid / $loan->principal_amount) * 100, 1) : 0;
                $remainingBalance = $loan->principal_amount - $approvedPaid;

                $nextSchedule = $loan->schedules
                    ->where('status', 'unpaid')
                    ->sortBy('due_date')
                    ->first();

                // Check down payment status safely
                $hasApprovedDownpayment = false;
                $hasPendingDownpayment = false;

                try {
                    $hasApprovedDownpayment = LoanPayment::where('loan_id', $loan->id)
                        ->whereNull('schedule_id')
                        ->where('status', 'approved')
                        ->exists();

                    $hasPendingDownpayment = LoanPayment::where('loan_id', $loan->id)
                        ->whereNull('schedule_id')
                        ->where('status', 'pending')
                        ->exists();
                } catch (\Exception $e) {
                    // If status column doesn't exist, treat as having approved
                    $hasApprovedDownpayment = true;
                }

                if ($remainingBalance <= 0) {
                    $status = 'completed';
                } elseif (!$hasApprovedDownpayment) {
                    $status = $hasPendingDownpayment
                        ? 'awaiting_down_payment_verification'
                        : 'pending_down_payment';
                } else {
                    $status = $this->getLoanStatus($loan, $nextSchedule);
                }

                return [
                    'id' => $loan->id,
                    'appliance' => $loan->application->product->name ?? 'N/A',
                    'loan_number' => $loan->loan_number,
                    'principal_amount' => '₱' . number_format($loan->principal_amount, 2),
                    'amount_paid' => '₱' . number_format($approvedPaid, 2),
                    'remaining_balance' => '₱' . number_format($remainingBalance, 2),
                    'progress' => $progress,
                    'start_date' => $loan->release_date ? Carbon::parse($loan->release_date)->format('M j, Y') : 'N/A',
                    'due_date' => $nextSchedule ? Carbon::parse($nextSchedule->due_date)->format('M j, Y') : 'N/A',
                    'status' => $status,
                    'monthly_payment' => '₱' . number_format($loan->monthly_payment, 2),
                    'is_overdue' => $nextSchedule && Carbon::parse($nextSchedule->due_date)->isPast()
                ];
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'member' => [
                        'name' => $memberName,
                        'member_number' => $memberNumber
                    ],
                    'summary' => [
                        'active_loans' => $activeLoans,
                        'completed_loans' => $closedLoans,
                        'overdue_loans' => $overdueLoans,
                        'defaulted_loans' => $defaultedLoans,
                        'pending_down_payments' => $pendingDownPayments,
                        'next_payment_date' => $nextPaymentDate,
                        'total_principal' => '₱' . number_format($totalPrincipal, 2),
                        'total_paid' => '₱' . number_format($totalPaid, 2),
                        'total_remaining' => '₱' . number_format($totalRemaining, 2),
                        'overall_progress' => $overallProgress,
                        'total_dividends' => '₱' . number_format($totalDividends, 2)
                    ],
                    'recent_loans' => $recentLoans
                ]
            ]);
        } catch (\Exception $e) {
            \Log::error('Dashboard Error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to load dashboard data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    private function getLoanStatus($loan, $nextSchedule = null)
    {
        if ($loan->status === 'closed') {
            return 'completed';
        }

        if ($loan->status === 'defaulted') {
            return 'defaulted';
        }

        if ($loan->status === 'active') {
            if ($nextSchedule && Carbon::parse($nextSchedule->due_date)->isPast()) {
                return 'overdue';
            }
            return 'current';
        }

        return strtolower($loan->status);
    }

    private function getNextPaymentDate($loans)
    {
        $nextDate = null;

        foreach ($loans as $loan) {
            if ($loan->status !== 'active') continue;

            $nextSchedule = $loan->schedules
                ->where('status', 'unpaid')
                ->sortBy('due_date')
                ->first();

            if ($nextSchedule) {
                $scheduleDate = Carbon::parse($nextSchedule->due_date);
                if (!$nextDate || $scheduleDate->isBefore($nextDate)) {
                    $nextDate = $scheduleDate;
                }
            }
        }

        return $nextDate ? $nextDate->format('M j, Y') : 'No upcoming payments';
    }

    private function calculateDividend($loan)
    {
        // Dividend calculation logic - typically paid on completed loans
        if ($loan->status === 'closed') {
            // Simple dividend calculation: 2% of principal amount
            return $loan->principal_amount * 0.02;
        }

        return 0;
    }
}
