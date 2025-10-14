<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Loan;
use App\Models\LoanApplication;
use App\Models\Member;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AppliancesLoanController extends Controller
{
    public function index()
    {
        $applications = LoanApplication::with(
            // include role so we can ensure exclusion
            'user:id,email,role',
            'product:id,name',
            'loan:id,loan_application_id,loan_number,monthly_payment,principal_amount,interest_rate,term_months,application_date,approval_date',
            'loanType:id,type_name,interest_rate'
        )
            ->whereHas('user', function ($q) {
                $q->whereNotIn('role', ['admin', 'loan_clerk']);
            })
            ->select(['id', 'user_id', 'product_id', 'status', 'applied_amount', 'term_months', 'application_date', 'loan_type_id', 'user_name'])
            ->latest()
            ->paginate(25);

        $formatted = $applications->getCollection()->map(function ($item) {
            $isMember = Member::where('user_id', $item->user_id)->exists();
            return [
                'id' => $item->id,
                'user_id' => $item->user_id,
                'loan_number' => optional($item->loan)->loan_number,
                'user_email' => optional($item->user)->email,
                'user_name' => $item->user_name,
                'product_name' => optional($item->product)->name,
                'applied_amount' => $item->applied_amount,
                'monthly_payment' => optional($item->loan)->monthly_payment,
                'term_months' => $item->term_months ?? optional($item->loan)->term_months,
                'interest_rate' => optional($item->loanType)->interest_rate ?? optional($item->loan)->interest_rate,
                'application_date' => $item->application_date ?? optional($item->loan)->application_date,
                'status' => $item->status,
                'is_member' => $isMember,
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

        if (!in_array($user->role->value, ['admin', 'loan_clerk'])) {
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
            'reason' => ['nullable', 'string']
        ]);

        $user = Auth::user();

        if (!in_array($user->role->value, ['admin', 'loan_clerk'])) {
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
                'rejection_reason' => $request->reason
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
        $application = LoanApplication::with(['user', 'loanType', 'product', 'loan'])->findOrFail($id);

        // Prevent access if application belongs to admin or loan_clerk (should not normally exist after filtering/seeding)
        if (in_array(optional($application->user)->role, ['admin', 'loan_clerk'])) {
            return response()->json(['message' => 'Application not found'], 404);
        }

    // Manually locate member record (avoid nested eager load issues if relation name differs)
    $member = \App\Models\Member::where('user_id', $application->user_id)->first();
        $isMember = $member !== null;

        return response()->json([
            'id' => $application->id,
            'status' => $application->status,
            'applied_amount' => $application->applied_amount,
            'term_months' => $application->term_months,
            'purpose' => $application->purpose,
            'application_date' => $application->application_date,
            'user_name' => $application->user_name,
            'is_member' => $isMember,
            
            // Personal information from application (for non-members)
            'phone' => $application->phone,
            'age' => $application->age,
            'address' => $application->address,
            'tin_number' => $application->tin_number,
            'employer' => $application->employer,
            'position' => $application->position,
            'monthly_income' => $application->monthly_income,
            'other_income_source' => $application->other_income_source,
            'monthly_disposable_income' => $application->monthly_disposable_income,
            'birth_month' => $application->birth_month,
            'place_of_birth' => $application->place_of_birth,
            'no_of_dependents' => $application->no_of_dependents,
            'SMPC_regular_loan' => $application->SMPC_regular_loan,
            'SMPC_petty_cash_loan' => $application->SMPC_petty_cash_loan,
            'total_amortization' => $application->total_amortization,
            
            // Document URLs
            'documents' => [
                'applicant_photo' => $application->applicant_photo 
                    ? asset('storage/' . $application->applicant_photo) 
                    : null,
                'certificate_of_employment' => $application->certificate_of_employment 
                    ? asset('storage/' . $application->certificate_of_employment) 
                    : null,
                'bragy_certificate' => $application->bragy_certificate 
                    ? asset('storage/' . $application->bragy_certificate) 
                    : null,
                'valid_id_front' => $application->valid_id_front 
                    ? asset('storage/' . $application->valid_id_front) 
                    : null,
                'valid_id_back' => $application->valid_id_back 
                    ? asset('storage/' . $application->valid_id_back) 
                    : null,
                'birth_certificate' => $application->birth_certificate 
                    ? asset('storage/' . $application->birth_certificate) 
                    : null,
            ],
            
            'member' => $member ? [
                'full_name' => $member->full_name,
                'address' => $member->address,
                'tin_number' => $member->tin_number,
                'phone_number' => $member->phone_number,
                'date_of_birth' => $member->date_of_birth,
                'place_of_birth' => $member->place_of_birth,
                'age' => $member->age,
                'dependents' => $member->number_of_children,
                'employer' => $member->employer,
                'position' => $member->position,
                'monthly_income' => $member->monthly_income,
                'other_income' => $member->other_income,
                'monthly_disposable_income' => $this->calculateDisposableIncome($member),
            ] : null,
            'user' => optional($application->user) ? [
                'id' => optional($application->user)->id,
                'email' => optional($application->user)->email,
            ] : null,
            'product' => $application->product ? [
                'id' => $application->product->id,
                'name' => $application->product->name,
            ] : null,
            'loan_type' => $application->loanType ? [
                'id' => $application->loanType->id,
                'name' => $application->loanType->type_name,
                'interest_rate' => $application->loanType->interest_rate,
                'max_term_months' => $application->loanType->max_term_months,
                'collateral_required' => (bool)$application->loanType->collateral_required,
            ] : null,
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

    private function calculateDisposableIncome($member)
    {
        if (!$member) {
            return 0;
        }

        $monthlyIncome = is_numeric($member->monthly_income) ? (float)$member->monthly_income : 0;
        $otherIncome = 0;

        // Handle other_income which might be string or numeric
        if ($member->other_income !== null) {
            if (is_numeric($member->other_income)) {
                $otherIncome = (float)$member->other_income;
            } else {
                // Try to extract numeric value from string
                $cleanValue = preg_replace('/[^\d.]/', '', $member->other_income);
                $otherIncome = is_numeric($cleanValue) ? (float)$cleanValue : 0;
            }
        }

        return $monthlyIncome - $otherIncome;
    }
}
