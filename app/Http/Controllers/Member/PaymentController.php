<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\LoanPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class PaymentController extends Controller
{
    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'loan_id' => 'required|exists:loans,id',
            'schedule_id' => 'nullable|exists:loan_schedules,id',
            'payment_date' => 'required|date',
            'amount_paid' => 'required|numeric',
            'remaining_balance' => 'required|numeric',
            'payment_method' => 'required|in:cash,check,bank_transfer',
            'receipt_number' => 'required|string',
            'notes' => 'nullable|string',
            'payment_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();
        $data['received_by'] = Auth::id();

        if ($request->hasFile('payment_image')) {
            $path = $request->file('payment_image')->store('payment_images', 'public');
            $data['payment_image'] = $path;
        }

        $payment = LoanPayment::create($data);

        return response()->json([
            'message' => 'Payment created successfully',
            'payment' => $payment,
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $payment = LoanPayment::with(['loan', 'schedule', 'receivedBy'])->find($id);

        if (!$payment) {
            return response()->json(['message' => 'Payment not found'], 404);
        }

        // Ensure the authenticated user is the one who owns the loan associated with the payment
        $loan = $payment->loan;
        if ($loan->member_id !== Auth::user()->member->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json(['payment' => $payment]);
    }
}
