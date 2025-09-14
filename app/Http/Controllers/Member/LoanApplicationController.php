<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\LoanApplication;
use App\Models\User;
use Illuminate\Http\Request;

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

    //store loan application
    public function store(Request $request){
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'loan_type_id' => 'required|exists:loan_types,id',
            'product_id' => 'required|exists:products,id',
            'user_name' => 'required|string|max:255',
            'applied_amount' => 'required|numeric|min:0',
            'term_months' => 'required|integer|min:1',
            'phone' => 'required|string|max:20',
            'age' => 'required|integer|min:18',
            'address' => 'required|string|max:255',
            'tin_number' => 'nullable|string|max:50',
            'employer' => 'nullable|string|max:255',
            'position' => 'nullable|string|max:255',
            'monthly_income' => 'required|numeric|min:0',
            'other_income_source' => 'nullable|string|max:255',
            'monthly_disposable_income' => 'required|numeric|min:0',
            'birthmonth' => 'required|date',
            'place_of_birth' => 'required|string|max:255',
            'no_of_dependents' => 'required|integer|min:0',
            'education_expense' => 'nullable|numeric|min:0',
            'food_expense' => 'nullable|numeric|min:0',
            'house_expense' => 'nullable|numeric|min:0',
            'transportation_expense' => 'nullable|numeric|min:0',
            'application_date' => 'required|date',
            'preferred_meeting_date' => 'nullable|date',
        ]);

        $loanApplication = LoanApplication::create($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Loan application submitted successfully',
            'data' => $loanApplication
        ], 201);
    }
}
