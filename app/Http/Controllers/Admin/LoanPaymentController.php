<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Loan;
use App\Models\LoanSchedule;
use App\Models\LoanPayment;
use App\Models\Product;
use App\Models\Member;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LoanPaymentController extends Controller
{
    public function getLoanPayment()
    {
        // Get all active loans with their payment information
        $loans = Loan::with([
            'application:id,user_id,product_id,user_name',
            'application.user:id,email',
            'application.product:id,name',
            'payments:id,loan_id,amount_paid,payment_date',
            'schedules:id,loan_id,due_date,status'
        ])
        ->where('status', 'active')
        ->latest()
        ->paginate(25);

        // Ensure schedules exist for any active loans missing them (backfill for previously-approved loans)
        $loans->getCollection()->each(function ($loan) {
            if ($loan->schedules->count() === 0) {
                $this->ensureLoanSchedules($loan);
                // Reload schedules for accurate next due date
                $loan->load('schedules');
            }
        });

        // Format the data for frontend consumption
        $formattedLoans = $loans->getCollection()->map(function ($loan) {
            // Get member info if exists
            $member = Member::where('user_id', $loan->application->user_id)->first();
            
            // Calculate payment status
            $nextDueDate = $loan->schedules()
                ->where('status', 'unpaid')
                ->orderBy('due_date')
                ->first()?->due_date;
            
            // Only approved payments count towards balances/progress
            $totalApprovedPaid = $loan->payments()
                ->where('status', 'approved')
                ->sum('amount_paid');
            $remainingBalance = $loan->principal_amount - $totalApprovedPaid;
            // Completed installments = schedules marked as paid
            $paymentsCompleted = $loan->schedules()->where('status', 'paid')->count();
            
            // Determine payment status
            $paymentStatus = 'Current';
            if ($nextDueDate) {
                $dueDate = Carbon::parse($nextDueDate);
                $today = Carbon::today();
                
                if ($dueDate->isPast()) {
                    $paymentStatus = 'Overdue';
                } elseif ($dueDate->diffInDays($today) <= 7) {
                    $paymentStatus = 'Due Soon';
                }
            }
            
            // If the member just paid today (approved payment), consider status Current instead of Due Soon
            $lastApprovedPayment = $loan->payments()
                ->where('status', 'approved')
                ->orderByDesc('payment_date')
                ->first();
            if ($lastApprovedPayment && Carbon::parse($lastApprovedPayment->payment_date)->isToday()) {
                $paymentStatus = 'Current';
            }

            if ($remainingBalance <= 0) {
                $paymentStatus = 'Completed';
            }

            return [
                'id' => $loan->id,
                'loan_id' => $loan->id,
                'loan_number' => $loan->loan_number,
                'member_name' => $member?->full_name ?? $loan->application->user_name ?? $loan->application->user?->email,
                'product_name' => $loan->application->product?->name,
                'principal_amount' => $loan->principal_amount,
                'monthly_payment' => $loan->monthly_payment,
                'amount_paid' => $totalApprovedPaid,
                'remaining_balance' => $remainingBalance,
                'next_due_date' => $nextDueDate,
                'status' => $paymentStatus,
                'payments_completed' => $paymentsCompleted,
                'total_payments' => $loan->term_months,
                'payment_date' => $loan->payments->last()?->payment_date,
                'approval_date' => $loan->approval_date,
                'maturity_date' => $loan->maturity_date,
            ];
        });

        $loans->setCollection($formattedLoans);

        return response()->json([
            'payments' => $loans
        ]);
    }

    /**
     * Create basic monthly schedules if missing, based on release_date or approval_date.
     */
    protected function ensureLoanSchedules(Loan $loan): void
    {
        $months = (int) $loan->term_months;
        if ($months <= 0) return;

        // Use release_date if available, otherwise approval_date, otherwise today
        $startDate = $loan->release_date
            ? Carbon::parse($loan->release_date)
            : ($loan->approval_date ? Carbon::parse($loan->approval_date) : Carbon::today());

    // Business rule (restart): Finance 80% after downpayment
    $principal = (float) $loan->principal_amount * 0.8;
    $basePrincipal = round($principal / $months, 2);
    $accPrincipal = 0.0;

        for ($i = 1; $i <= $months; $i++) {
            $principalPortion = ($i === $months)
                ? round($principal - $accPrincipal, 2)
                : $basePrincipal;
            $interestPortion = 0.0;

            $dueDate = $startDate->copy()->addMonths($i);

            LoanSchedule::create([
                'loan_id' => $loan->id,
                'due_date' => $dueDate,
                'amount_due' => round($principalPortion + $interestPortion, 2),
                'principal_amount' => $principalPortion,
                'interest_amount' => $interestPortion,
                'status' => 'unpaid',
            ]);

            $accPrincipal += $principalPortion;
        }
    }

    public function getLoanPaymentDetails($loanId)
    {
        $loan = Loan::with([
            'application:id,user_id,product_id,user_name',
            'application.user:id,email',
            'application.product:id,name',
            'payments' => function ($query) {
                $query->orderBy('payment_date', 'desc');
            },
            'schedules' => function ($query) {
                $query->orderBy('due_date', 'asc');
            }
        ])->findOrFail($loanId);

        // Get member info if exists
        $member = Member::where('user_id', $loan->application->user_id)->first();

        return response()->json([
            'loan' => $loan,
            'member' => $member,
        ]);
    }

    public function updatePaymentStatus(Request $request, $paymentId)
    {
        $request->validate([
            'status' => 'required|in:pending,approved,rejected',
        ]);

        $payment = LoanPayment::with(['loan.application.product', 'schedule'])->findOrFail($paymentId);

        // Wrap in transaction to ensure stock decrement and status update are atomic
        DB::beginTransaction();
        try {
            $newStatus = $request->input('status');

            if ($newStatus === 'approved') {
                // If this is a down payment (no schedule_id), and it's the first approved down payment, decrement stock
                if (is_null($payment->schedule_id)) {
                    $hasApprovedDownpayment = LoanPayment::where('loan_id', $payment->loan_id)
                        ->whereNull('schedule_id')
                        ->where('status', 'approved')
                        ->exists();

                    if (!$hasApprovedDownpayment) {
                        $application = optional($payment->loan)->application;
                        $productId = optional($application)->product_id;
                        if ($productId) {
                            $product = Product::whereKey($productId)->lockForUpdate()->first();
                            if (!$product || (int) $product->stock_quantity <= 0) {
                                throw ValidationException::withMessages([
                                    'stock' => 'Insufficient stock to approve down payment',
                                ]);
                            }
                            $product->decrement('stock_quantity');
                        }
                    }
                } else {
                    // If this is a scheduled payment, mark the schedule as paid
                    if ($payment->schedule) {
                        $payment->schedule->status = 'paid';
                        $payment->schedule->save();
                    }
                }
            }

            $payment->status = $newStatus;
            $payment->save();

            DB::commit();

            return response()->json([
                'message' => 'Payment status updated successfully',
                'payment' => $payment,
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            $status = $e instanceof ValidationException ? 422 : 500;
            return response()->json([
                'message' => 'Failed to update payment status',
                'error' => $e->getMessage(),
            ], $status);
        }
    }
    


}
