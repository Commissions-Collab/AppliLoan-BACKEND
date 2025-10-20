<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Loan;
use App\Models\LoanPayment;
use App\Models\Member;
use Carbon\Carbon;
use Illuminate\Http\Request;

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

        // Format the data for frontend consumption
        $formattedLoans = $loans->getCollection()->map(function ($loan) {
            // Get member info if exists
            $member = Member::where('user_id', $loan->application->user_id)->first();
            
            // Calculate payment status
            $nextDueDate = $loan->schedules()
                ->where('status', 'unpaid')
                ->orderBy('due_date')
                ->first()?->due_date;
            
            $totalPaid = $loan->payments->sum('amount_paid');
            $remainingBalance = $loan->principal_amount - $totalPaid;
            $paymentsCompleted = $loan->payments->count();
            
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
                'amount_paid' => $totalPaid,
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

        $payment = LoanPayment::findOrFail($paymentId);
        $payment->status = $request->input('status');
        $payment->save();

        return response()->json([
            'message' => 'Payment status updated successfully',
            'payment' => $payment,
        ]);
    }
    


}
