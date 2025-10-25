<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\LoanPayment;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoanMonitoringController extends Controller
{
    public function index()
    {
        $member = Auth::user()->member;

        $userId = Auth::id();

        // If the user has no Member profile yet, still fetch loans by user_id so non-members see their loans
        if (!$member) {
            $loans = \App\Models\Loan::query()
                ->with([
                    'application.product',
                    'schedules' => function ($query) {
                        $query->orderBy('due_date');
                    },
                    'payments'
                ])
                ->whereHas('application', function ($q) use ($userId) {
                    $q->where('user_id', $userId);
                })
                ->orderByDesc('created_at')
                ->get();
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
        }

        // Summary totals should reflect approved payments only
        $totalPaid = $loans->sum(function ($loan) {
            return $loan->payments->where('status', 'approved')->sum('amount_paid');
        });

        $nextPaymentDate = $this->getNextPaymentDate($loans);
        $totalDividends = 0; // Temporarily set to 0 for testing

        $loansList = $loans->map(function ($loan) {
            // Use approved payments for calculations and status
            $approvedPaid = $loan->payments->where('status', 'approved')->sum('amount_paid');
            // Apply interest depending on term: 5% per term (capped to 25% for term >=5)
            $principalWithInterest = $this->principalWithInterest($loan);
            $progress = $principalWithInterest > 0 ? round(($approvedPaid / $principalWithInterest) * 100, 1) : 0;
            $remainingBalance = $principalWithInterest - $approvedPaid;

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

            // Determine UI status based on downpayment state and completion
            $hasApprovedDownpayment = \App\Models\LoanPayment::where('loan_id', $loan->id)
                ->whereNull('schedule_id')
                ->where('status', 'approved')
                ->exists();

            $hasPendingDownpayment = \App\Models\LoanPayment::where('loan_id', $loan->id)
                ->whereNull('schedule_id')
                ->where('status', 'pending')
                ->exists();

            if ($remainingBalance <= 0) {
                $status = 'completed';
            } elseif (!$hasApprovedDownpayment) {
                $status = $hasPendingDownpayment
                    ? 'awaiting_down_payment_verification'
                    : 'pending_down_payment';
            } else {
                $derived = $this->getLoanDetailedStatus($loan, $nextSchedule); // e.g., Current/Overdue/Completed
                $status = strtolower(str_replace(' ', '_', $derived));
            }
            $dividend = $this->calculateDividend($loan);

            return [
                'id' => $loan->id,
                'item' => $loan->application->product->name ?? 'N/A',
                'status' => $status,
                    // show principal including applied interest
                    'amount' => '₱' . number_format($principalWithInterest, 2),
                'paid' => '₱' . number_format($approvedPaid, 2),
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
        $userId = Auth::id();

        // If there's no Member profile yet, fetch the loan by user ownership via application.user_id
        if (!$member) {
            $loan = \App\Models\Loan::query()
                ->with([
                    'application.product',
                    'schedules' => function ($query) {
                        $query->orderBy('due_date');
                    },
                    'payments' => function ($query) {
                        $query->orderBy('payment_date', 'desc');
                    }
                ])
                ->where('id', $loadId)
                ->whereHas('application', function ($q) use ($userId) {
                    $q->where('user_id', $userId);
                })
                ->firstOrFail();
        } else {
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
        }

        // Use approved payments only for detailed view calculations
        $totalPaid = $loan->payments->where('status', 'approved')->sum('amount_paid');
        // principal including interest applied depending on term
        $principalWithInterest = $this->principalWithInterest($loan);
        $remainingBalance = $principalWithInterest - $totalPaid;
        $progress = $principalWithInterest > 0
            ? round(($totalPaid / $principalWithInterest) * 100, 1)
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
            // Normalize status for frontend (map 'pending' -> 'pending_verification')
            $normalizedStatus = $payment->status === 'pending' ? 'pending_verification' : $payment->status;

            // Determine payment type: down payment has no schedule_id
            $type = is_null($payment->schedule_id) ? 'down_payment' : 'monthly_payment';

            // Build receipt image URL if available
            $imageUrl = $payment->payment_image
                ? asset('storage/' . ltrim($payment->payment_image, '/'))
                : null;

            return [
                'id' => $payment->id,
                'payment_date' => Carbon::parse($payment->payment_date)->format('M j, Y'),
                'amount_paid' => '₱' . number_format($payment->amount_paid, 2),
                'payment_method' => ucfirst(str_replace('_', ' ', $payment->payment_method)),
                'receipt_number' => $payment->receipt_number,
                'remaining_balance' => '₱' . number_format($payment->remaining_balance, 2),
                'status' => $normalizedStatus,
                'type' => $type,
                'receipt_image_url' => $imageUrl,
            ];
        });

        // Determine UI status flags and status string
        $hasApprovedDownpayment = \App\Models\LoanPayment::where('loan_id', $loan->id)
            ->whereNull('schedule_id')
            ->where('status', 'approved')
            ->exists();

        $hasPendingDownpayment = \App\Models\LoanPayment::where('loan_id', $loan->id)
            ->whereNull('schedule_id')
            ->where('status', 'pending')
            ->exists();

        if ($remainingBalance <= 0) {
            $uiStatus = 'completed';
        } elseif (!$hasApprovedDownpayment) {
            $uiStatus = $hasPendingDownpayment
                ? 'awaiting_down_payment_verification'
                : 'pending_down_payment';
        } else {
            $nextUnpaid = $loan->schedules
                ->where('status', 'unpaid')
                ->sortBy('due_date')
                ->first();
            $base = $this->getLoanDetailedStatus($loan, $nextUnpaid);
            $uiStatus = strtolower(str_replace(' ', '_', $base));
        }

        return response()->json([
            'success' => true,
            'data' => [
                'loan' => [
                    'id' => $loan->id,
                    'loan_number' => $loan->loan_number,
                    'appliance' => $loan->application->product->name ?? 'N/A',
                    // show principal including applied interest and recompute monthly payment based on term
                    'principal_amount' => '₱' . number_format($principalWithInterest, 2),
                    'monthly_payment' => '₱' . number_format(
                        $loan->term_months > 0 ? ($principalWithInterest / $loan->term_months) : $loan->monthly_payment,
                        2
                    ),
                    'interest_rate' => $loan->interest_rate . '%',
                    'term_months' => $loan->term_months,
                    'total_paid' => '₱' . number_format($totalPaid, 2),
                    'remaining_balance' => '₱' . number_format($remainingBalance, 2),
                    'progress_percentage' => $progress,
                    'status' => $uiStatus,
                    'release_date' => $loan->release_date ? Carbon::parse($loan->release_date)->format('M j, Y') : null,
                    'maturity_date' => $loan->maturity_date ? Carbon::parse($loan->maturity_date)->format('M j, Y') : null,
                    'has_approved_downpayment' => $hasApprovedDownpayment,
                    'has_pending_downpayment' => $hasPendingDownpayment,
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

    /**
     * Compute principal including interest based on term.
     * Interest = 5% per term month, capped at 5 (i.e. max 25%).
     * Returns a float (rounded to 2 decimals).
     */
    private function principalWithInterest($loan)
    {
        $term = isset($loan->term_months) ? intval($loan->term_months) : 1;
        // cap term between 1 and 5
        $term = max(1, min($term, 5));
        $interestRate = 0.05 * $term; // 5% per term
        $principal = isset($loan->principal_amount) ? floatval($loan->principal_amount) : 0.0;

        return round($principal * (1 + $interestRate), 2);
    }

    private function calculateDividend($loan)
    {
        // Dividend calculation logic - typically paid on completed loans
        if ($loan->status === 'closed') {
            // Simple dividend calculation: 2% of principal amount (including applied interest)
            $principalWithInterest = $this->principalWithInterest($loan);
            return $principalWithInterest * 0.02;
        }

        return 0;
    }
}
