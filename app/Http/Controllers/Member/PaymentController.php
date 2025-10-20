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
            'payment_method' => 'required|in:cash,check,bank_transfer',
            'receipt_number' => 'required|string|max:255',
            'notes' => 'nullable|string',
            'payment_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();
        $data['received_by'] = Auth::id();

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
            'payment_method' => 'required|in:cash,check,bank_transfer',
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

        if ($data['amount_paid'] > $data['remaining_balance']) {
            return response()->json([
                'success' => false,
                'message' => 'Payment amount exceeds remaining balance',
            ], 400);
        }

        $payment = $loan->payments()->create($data);

        $loan->remaining_balance = $data['remaining_balance'];
        $loan->save();

        return response()->json([
            'success' => true,
            'message' => 'Payment processed successfully',
            'payment' => $payment,
        ]);
    }
}
