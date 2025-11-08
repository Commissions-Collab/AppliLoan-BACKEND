<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\LoanApplication;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LoanApplicationController extends Controller
{
    //desplay user info
    public function userdetails(Request $request, $id){
        $user = User::with('member','is_member')->find(auth()->$id);
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }

        if($user->is_member){
            return response()->json([
                'success' => true,
                'data' => [
                    'user' => $user,
                    'member' => $user->member,
                    'is_member' => $user->is_member,
                ]
            ]);
        }else{
            return response()->json([
                'success' => false,
               'data'=>[
                'user' => $user,
                'member' => null,
               ]
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $user
        ]);
    }

  // Store a loan application
    public function storeLoanApplication(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'loan_type_id' => 'required|exists:loan_types,id',
            'product_id' => 'nullable|exists:products,id',
            'user_name' => 'nullable|string|max:255',
            'applied_amount' => 'required|numeric|min:500',
            'term_months' => 'required|integer|min:1',

            // personal details
            'phone' => 'required|string|max:20|regex:/^[0-9+\-\s]+$/',
            'age' => 'nullable|integer|min:18|max:100',
            'address' => 'required|string|max:255',
            'tin_number' => 'nullable|string|max:50',
            'employer' => 'nullable|string|max:255',
            'position' => 'nullable|string|max:255',
            'monthly_income' => 'nullable|numeric|min:0',
            'other_income_source' => 'nullable|string|max:255',
            'monthly_disposable_income' => 'nullable|numeric|min:0',
            'birth_month' => 'nullable|string|max:20',
            'place_of_birth' => 'nullable|string|max:255',
            'no_of_dependents' => 'nullable|integer|min:0',

            // estimated expenses
            'education_expense' => 'nullable|numeric|min:0',
            'food_expense' => 'nullable|numeric|min:0',
            'house_expense' => 'nullable|numeric|min:0',
            'transportation_expense' => 'nullable|numeric|min:0',

            // amortization details
            'date_granted' => 'nullable|date',
            'monthly_installment' => 'nullable|numeric|min:0',
            'SMPC_regular_loan' => 'nullable|numeric|min:0',
            'SMPC_petty_cash_loan' => 'nullable|numeric|min:0',
            'total_amortization' => 'nullable|numeric|min:0',

            // required documents (file uploads)
            'applicant_photo' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
            'certificate_of_employment' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
            'bragy_certificate' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
            'valid_id_front' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
            'valid_id_back' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
            'birth_certificate' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',

            // scheduling details
            'preferred_meeting_date' => 'nullable|date|after_or_equal:today',
            'preferred_meeting_time' => 'nullable|string|max:20',

            'application_date' => 'required|date',
            'status' => ['nullable', Rule::in(['pending', 'approved', 'rejected'])],
        ]);

        // Handle file uploads
        $fileFields = [
            'applicant_photo',
            'certificate_of_employment',
            'bragy_certificate',
            'valid_id_front',
            'valid_id_back',
            'birth_certificate',
        ];

        foreach ($fileFields as $field) {
            if ($request->hasFile($field)) {
                $validated[$field] = $request->file($field)->store("loan_documents/{$field}", 'public');
            }
        }

        $validated['status'] = $validated['status'] ?? 'pending';

        $loanApplication = LoanApplication::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Loan application submitted successfully.',
            'data' => $loanApplication,
        ], 201);
    }

}
