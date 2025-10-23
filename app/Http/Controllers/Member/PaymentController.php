<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\LoanPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class PaymentController extends Controller
{
    /**
     * Create a down payment
     */
    public function downPayment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'loan_id' => 'required|exists:loans,id',
            'schedule_id' => 'nullable|exists:loan_schedules,id',
            'payment_date' => 'required|date',
            'amount_paid' => 'required|numeric|min:1',
            'remaining_balance' => 'required|numeric|min:0',
            // Align with DB enum: ['gcash','check','bank_transfer','cash']
            'payment_method' => 'required|in:gcash,check,bank_transfer,cash',
            'receipt_number' => 'required|string|max:255',
            'notes' => 'nullable|string',
            'payment_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();
        $data['received_by'] = Auth::id();

        // Derive remaining balance server-side to avoid client-side drift
        $loanId = $request->input('loan_id');
        $approvedTotal = LoanPayment::where('loan_id', $loanId)
            ->where('status', 'approved')
            ->sum('amount_paid');
        $loanPrincipal = optional(\App\Models\Loan::find($loanId))->principal_amount ?? 0;
        $currentBalance = max(0, (float) $loanPrincipal - (float) $approvedTotal);

        if ((float) $data['amount_paid'] > $currentBalance + 0.01) {
            return response()->json([
                'errors' => [
                    'amount_paid' => [
                        'Payment amount exceeds remaining balance'
                    ],
                ],
            ], 422);
        }

        $data['remaining_balance'] = max(0, $currentBalance - (float) $data['amount_paid']);

        // Handle image upload
        if ($request->hasFile('payment_image')) {
            $data['payment_image'] = $request->file('payment_image')->store('payment_images', 'public');
        }

        $payment = LoanPayment::create($data);

        return response()->json([
            'message' => 'Down payment created successfully',
            'payment' => $payment,
        ], 201);
    }

    /**
     * Show specific payment details
     */
    public function show($id)
    {
        $payment = LoanPayment::with(['loan', 'schedule', 'receivedBy'])->find($id);

        if (!$payment) {
            return response()->json(['message' => 'Payment not found'], 404);
        }

        if ($payment->loan->member_id !== Auth::user()->member->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json(['payment' => $payment]);
    }

    /**
     * Show payment status
     */
    public function viewStatus($id)
    {
        $payment = LoanPayment::with(['loan', 'schedule', 'receivedBy'])->find($id);

        if (!$payment) {
            return response()->json(['message' => 'Payment not found'], 404);
        }

        if ($payment->loan->member_id !== Auth::user()->member->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json(['status' => $payment->status]);
    }

    /**
     * List all payments for the authenticated member
     */
    public function listPayments(Request $request)
    {
        $member = Auth::user()->member;

        if (!$member) {
            return response()->json(['success' => false, 'message' => 'Member profile not found'], 404);
        }

        $payments = LoanPayment::whereHas('loan', function ($query) use ($member) {
            $query->where('member_id', $member->id);
        })
            ->with(['loan', 'schedule', 'receivedBy'])
            ->orderBy('payment_date', 'desc')
            ->paginate(10);

        return response()->json(['success' => true, 'data' => $payments]);
    }

    /**
     * Show all payment history
     */
    public function historyPayments()
    {
        $member = Auth::user()->member;

        if (!$member) {
            return response()->json(['success' => false, 'message' => 'Member profile not found'], 404);
        }

        $payments = LoanPayment::whereHas('loan', function ($query) use ($member) {
            $query->where('member_id', $member->id);
        })
            ->with(['loan', 'schedule', 'receivedBy'])
            ->orderBy('payment_date', 'desc')
            ->get();

        return response()->json(['success' => true, 'data' => $payments]);
    }

    /**
     * List all payment schedules for a specific loan
     */
    public function paymentSchedules($loanId)
    {
        $member = Auth::user()->member;

        if (!$member) {
            return response()->json(['success' => false, 'message' => 'Member profile not found'], 404);
        }

        $loan = $member->loans()->find($loanId);

        if (!$loan) {
            return response()->json(['success' => false, 'message' => 'Loan not found'], 404);
        }

        $schedules = $loan->schedules()->with('payments')->get();

        return response()->json(['success' => true, 'data' => $schedules]);
    }

    /**
     * Make a payment for a specific loan
     */
    public function makePayment(Request $request, $loanId)
    {
        $member = Auth::user()->member;

        if (!$member) {
            return response()->json(['success' => false, 'message' => 'Member profile not found'], 404);
        }

        $loan = $member->loans()->find($loanId);

        if (!$loan) {
            return response()->json(['success' => false, 'message' => 'Loan not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'schedule_id' => 'nullable|exists:loan_schedules,id',
            'payment_date' => 'required|date',
            'amount_paid' => 'required|numeric|min:1',
            'remaining_balance' => 'required|numeric|min:0',
            // Align with DB enum: ['gcash','check','bank_transfer','cash']
            'payment_method' => 'required|in:gcash,check,bank_transfer,cash',
            'receipt_number' => 'required|string|max:255',
            'notes' => 'nullable|string',
            'payment_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

    $data = $validator->validated();
    $data['loan_id'] = $loan->id;
    $data['received_by'] = Auth::id();

        if ($request->hasFile('payment_image')) {
            $data['payment_image'] = $request->file('payment_image')->store('payment_images', 'public');
        }

        // Require an approved down payment before allowing monthly/scheduled payments
        $hasApprovedDownpayment = \App\Models\LoanPayment::where('loan_id', $loan->id)
            ->whereNull('schedule_id')
            ->where('status', 'approved')
            ->exists();

        if (!$hasApprovedDownpayment) {
            return response()->json([
                'success' => false,
                'message' => 'Down payment must be approved before making monthly payments.',
            ], 422);
        }

        // Compute current outstanding from approved payments only
        $approvedTotal = LoanPayment::where('loan_id', $loan->id)
            ->where('status', 'approved')
            ->sum('amount_paid');
        $currentBalance = max(0, (float) $loan->principal_amount - (float) $approvedTotal);

        if ((float) $data['amount_paid'] > $currentBalance + 0.01) {
            return response()->json([
                'success' => false,
                'message' => 'Payment amount exceeds remaining balance',
            ], 400);
        }

        // Override client-provided remaining_balance with server-derived value
        $data['remaining_balance'] = max(0, $currentBalance - (float) $data['amount_paid']);

        $payment = $loan->payments()->create($data);

        return response()->json([
            'success' => true,
            'message' => 'Payment processed successfully',
            'payment' => $payment,
        ]);
    }
}
