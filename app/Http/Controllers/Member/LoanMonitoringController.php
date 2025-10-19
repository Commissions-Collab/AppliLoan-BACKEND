<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoanMonitoringController extends Controller
{
    public function index()
    {
        $member = Auth::user()->member;

        if (!$member) {
            return response()->json([
                'success' => false,
                'message' => 'Member profile not found'
            ], 404);
        }

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

        $totalPaid = $loans->sum(function ($loan) {
            return $loan->payments->sum('amount_paid');
        });

        $nextPaymentDate = $this->getNextPaymentDate($loans);
        $totalDividends = 0; // Temporarily set to 0 for testing

        $loansList = $loans->map(function ($loan) {
            $totalPaid = $loan->payments->sum('amount_paid');

            $progress = $loan->principal_amount > 0 ? round(($totalPaid / $loan->principal_amount) * 100, 1) : 0;

            $nextSchedule = $loan->schedules
                ->where('status', 'unpaid')
                ->sortBy('due_date')
                ->first();

            $nextBilling = $nextSchedule
                ? Carbon::parse($nextSchedule->due_date)->subDays(10)->format('M j, Y')
                : '-';

            $dueDate = $nextSchedule
                ? Carbon::parse($nextSchedule->due_date)->format('M j, Y')
                : '-';

            $status = $this->getLoanDetailedStatus($loan, $nextSchedule);
            $dividend = $this->calculateDividend($loan);

            return [
                'id' => $loan->id,
                'item' => $loan->application->product->name ?? 'N/A',
                'status' => $status,
                'amount' => '₱' . number_format($loan->principal_amount, 2),
                'paid' => '₱' . number_format($totalPaid, 2),
                'progress' => $progress,
                'next_billing' => $nextBilling,
                'due_date' => $dueDate,
                'dividend' => '₱' . number_format($dividend, 2),
                'loan_number' => $loan->loan_number
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'summary' => [
                    'next_payment_date' => $nextPaymentDate,
                    'total_paid' => '₱' . number_format($totalPaid, 2),
                    'total_dividends' => '₱' . number_format($totalDividends, 2)
                ],
                'loans' => $loansList
            ]
        ], 200);
    }

    public function show($loadId)
    {
        $member = Auth::user()->member;

        if (!$member) {
            return response()->json([
                'success' => false,
                'message' => 'Member profile not found'
            ], 404);
        }

        $loan = $member->loans()
            ->with([
                'application.product',
                'schedules' => function ($query) {
                    $query->orderBy('due_date');
                },
                'payments' => function ($query) {
                    $query->orderBy('payment_date', 'desc');
                }
            ])
            ->findOrFail($loadId);

        $totalPaid = $loan->payments->sum('amount_paid');
        $remainingBalance = $loan->principal_amount - $totalPaid;
        $progress = $loan->principal_amount > 0
            ? round(($totalPaid / $loan->principal_amount) * 100, 1)
            : 0;

        $schedules = $loan->schedules->map(function ($schedule) {
            return [
                'id' => $schedule->id,
                'due_date' => Carbon::parse($schedule->due_date)->format('M j, Y'),
                'amount_due' => number_format($schedule->amount_due, 2),
                'principal_amount' => number_format($schedule->principal_amount, 2),
                'interest_amount' => number_format($schedule->interest_amount, 2),
                'status' => ucfirst($schedule->status),
                'is_overdue' => $schedule->status === 'unpaid' && Carbon::parse($schedule->due_date)->isPast()
            ];
        });

        $payments = $loan->payments->map(function ($payment) {
            return [
                'id' => $payment->id,
                'payment_date' => Carbon::parse($payment->payment_date)->format('M j, Y'),
                'amount_paid' => '₱' . number_format($payment->amount_paid, 2),
                'payment_method' => ucfirst(str_replace('_', ' ', $payment->payment_method)),
                'receipt_number' => $payment->receipt_number,
                'remaining_balance' => '₱' . number_format($payment->remaining_balance, 2)
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'loan' => [
                    'id' => $loan->id,
                    'loan_number' => $loan->loan_number,
                    'appliance' => $loan->application->product->name ?? 'N/A',
                    'principal_amount' => '₱' . number_format($loan->principal_amount, 2),
                    'monthly_payment' => '₱' . number_format($loan->monthly_payment, 2),
                    'interest_rate' => $loan->interest_rate . '%',
                    'term_months' => $loan->term_months,
                    'total_paid' => '₱' . number_format($totalPaid, 2),
                    'remaining_balance' => '₱' . number_format($remainingBalance, 2),
                    'progress_percentage' => $progress,
                    'status' => ucfirst($loan->status),
                    'release_date' => $loan->release_date ? Carbon::parse($loan->release_date)->format('M j, Y') : null,
                    'maturity_date' => $loan->maturity_date ? Carbon::parse($loan->maturity_date)->format('M j, Y') : null,
                ],
                'schedules' => $schedules,
                'payments' => $payments
            ]
        ], 200);
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

    private function getLoanDetailedStatus($loan, $nextSchedule = null)
    {
        if ($loan->status === 'closed') {
            return 'Completed';
        }

        if ($loan->status === 'defaulted') {
            return 'Defaulted';
        }

        if ($loan->status === 'active') {
            if ($nextSchedule && Carbon::parse($nextSchedule->due_date)->isPast()) {
                return 'Overdue';
            }
            return 'Current';
        }

        return ucfirst($loan->status);
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
