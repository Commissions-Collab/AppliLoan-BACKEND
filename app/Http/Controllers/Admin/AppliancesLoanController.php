<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Loan;
use App\Models\LoanApplication;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AppliancesLoanController extends Controller
{
    public function index()
    {
        $applications = LoanApplication::with('member:id,full_name', 'product:id,name', 'loan:id,loan_application_id,loan_number')
            ->select(['id', 'member_id', 'product_id', 'status'])
            ->whereHas('loan')
            ->latest()
            ->paginate(25);

        $formatted = $applications->getCollection()->map(function ($item) {
            return [
                'id' => $item->id,
                'loan_number' => optional($item->loan)->loan_number,
                'member_name' => optional($item->member)->full_name,
                'product_name' => optional($item->product)->name,
                'status' => $item->status
            ];
        });

        $applications->setCollection($formatted);

        return response()->json([
            'loan_applications' => $applications
        ]);
    }

    public function approvedApplication(string $id)
    {
        $user = Auth::user();

        if (!($user->isAdmin() || $user->isLoanClerk())) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $application = LoanApplication::with('loanType')->findOrFail($id);

        if ($application->status === 'approved') {
            return response()->json(['message' => 'Application already approved'], 400);
        }

        DB::beginTransaction();

        try {
            $loanNumber = $this->generateLoanNumber();

            /**
             * Can be changed based on the client
             */
            $releaseDate = Carbon::now()->addDays(3);
            $maturityDate = $releaseDate->copy()->addMonths($application->term_months);

            $loan = Loan::create([
                'loan_application_id' => $application->id,
                'loan_number' => $loanNumber,
                'principal_amount' => $application->applied_amount,
                'monthly_payment' => $this->calculateMonthlyPayment($application->applied_amount, $application->loanType->interest_rate, $application->term_months),
                'interest_rate' => $application->loanType->interest_rate,
                'term_months' => $application->term_months,
                'application_date' => $application->application_date,
                'approval_date' => Carbon::now(),
                'release_date' => $releaseDate,
                'maturity_date' => $maturityDate,
                'approved_by' => $user->id,
                'purpose' => $application->purpose,
                'status' => 'active'
            ]);

            $application->update([
                'status' => 'approved'
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Loan application approved successfully',
                'loan' => $loan
            ], 201);
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json(['error' => $th->getMessage()], 500);
        }
    }

    public function rejectApplication(string $id, Request $request)
    {
        $request->validate([
            'rejection_reason' => ['nullable', 'string']
        ]);

        $user = Auth::user();

        if (!($user->isAdmin() || $user->isLoanClerk())) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $application = LoanApplication::with('loanType')->findOrFail($id);

        if ($application->status === 'approved') {
            return response()->json(['message' => 'Application already approved and cannot be rejected'], 400);
        }

        if ($application->status === 'rejected') {
            return response()->json(['message' => 'Application already rejected'], 400);
        }

        DB::beginTransaction();

        try {

            $application->update([
                'status' => 'rejected',
                'rejection_reason' => $request->rejection_reason
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Loan application rejected successfully',
            ], 200);
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json(['error' => $th->getMessage()], 500);
        }
    }

    public function show(string $id)
    {
        $application = LoanApplication::with(['member', 'loanType', 'product', 'loan'])->findOrFail($id);

        return response()->json([
            'id' => $application->id,
            'status' => $application->status,
            'applied_amount' => $application->applied_amount,
            'term_months' => $application->term_months,
            'purpose' => $application->purpose,
            'application_date' => $application->application_date,

            'member' => [
                'id' => $application->member->id,
                'full_name' => $application->member->full_name,
                'email' => $application->member->user->email ?? null,
                'contact' => $application->member->phone_number ?? null,
            ],

            'product' => [
                'id' => $application->product->id,
                'name' => $application->product->name,
            ],

            'loan_type' => [
                'id' => $application->loanType->id,
                'name' => $application->loanType->type_name,
                'interest_rate' => $application->loanType->interest_rate,
                'max_term_months' => $application->loanType->max_term_months,
                'collateral_required' => $application->loanType->collateral_required, // The output of this is 1 or 0 so just change it to true or false in frontend
            ],

            'loan' => $application->loan ? [
                'id' => $application->loan->id,
                'loan_number' => $application->loan->loan_number,
                'principal_amount' => $application->loan->principal_amount,
                'monthly_payment' => $application->loan->monthly_payment,
                'interest_rate' => $application->loan->interest_rate,
                'term_months' => $application->loan->term_months,
                'application_date' => $application->loan->application_date,
                'approval_date' => $application->loan->approval_date,
                'release_date' => $application->loan->release_date,
                'maturity_date' => $application->loan->maturity_date,
                'approved_by' => $application->loan->approved_by,
                'status' => $application->loan->status,
            ] : null,
        ]);
    }

    private function generateLoanNumber(): string
    {
        $year = date('Y');

        // Get last loan of the year
        $lastLoan = Loan::whereYear('created_at', $year)
            ->orderBy('id', 'desc')
            ->first();

        $nextNumber = $lastLoan
            ? ((int)substr($lastLoan->loan_number, -4)) + 1
            : 1;

        return 'LN-' . $year . '-' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
    }

    private function calculateMonthlyPayment($principal, $rate, $months)
    {
        $interest = $principal * $rate;

        return ($principal + $interest) / $months;
    }
}
