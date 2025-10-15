<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LoanPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PaymentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $payments = LoanPayment::with(['loan.id', 'receivedBy'])->latest()->paginate(10);
        return response()->json($payments);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $payment = LoanPayment::with(['loan.id', 'receivedBy'])->find($id);

        if (!$payment) {
            return response()->json(['message' => 'Payment not found'], 404);
        }

        return response()->json(['payment' => $payment]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function updateStatus(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:pending,approved,rejected',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $payment = LoanPayment::find($id);

        if (!$payment) {
            return response()->json(['message' => 'Payment not found'], 404);
        }

        $payment->status = $request->status;
        $payment->save();

        return response()->json([
            'message' => 'Payment status updated successfully',
            'payment' => $payment,
        ]);
    }
}
