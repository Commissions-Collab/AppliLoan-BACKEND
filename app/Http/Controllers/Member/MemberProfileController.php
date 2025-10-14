<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class MemberProfileController extends Controller
{
    public function show()
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated'
            ], 401);
        }

        $member = $user->member; // relation member()

        if (!$member) {
            return response()->json([
                'success' => false,
                'message' => 'Member profile not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'email' => $user->email,
                    'full_name' => $user->full_name ?? $member->full_name,
                    'is_member' => $user->is_member,
                ],
                'member' => [
                    'id' => $member->id,
                    'full_name' => $member->full_name,
                    'phone_number' => $member->phone_number,
                    'address' => $member->address,
                    'date_of_birth' => optional($member->date_of_birth)->toDateString(),
                    'place_of_birth' => $member->place_of_birth,
                    'age' => $member->age,
                    'civil_status' => $member->civil_status,
                    'religion' => $member->religion,
                    'tin_number' => $member->tin_number,
                    'employer' => $member->employer,
                    'position' => $member->position,
                    'monthly_income' => $member->monthly_income,
                    'other_income' => $member->other_income,
                    'number_of_children' => $member->number_of_children,
                    'spouse_name' => $member->spouse_name,
                    'spouse_employer' => $member->spouse_employer,
                    'spouse_monthly_income' => $member->spouse_monthly_income,
                ]
            ]
        ]);
    }
}
