<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Member;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MemberManagementController extends Controller
{
    // Controller methods for member management will go here

    public function displayAllUsers(Request $request)
    {   
        $user = Auth::user();

        // Only allow admin to access
        if ($user->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access. Only admins can view all users.'
            ], 401);
        }

        // Fetch all users except those with role 'admin' or 'clerk'
        $members = User::whereNotIn('role', ['admin', 'clerk'])
            ->select(['id', 'email', 'role', 'is_verified', 'is_member', 'created_at'])
            ->orderByDesc('created_at')
            ->paginate(25);

        return response()->json($members);
    }

    public function getMemberDetails($userId)
    {
        $user = Auth::user();

        // Only allow admin to access
        if ($user->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access. Only admins can view member details.'
            ], 401);
        }

        // Fetch member details along with user info
        $member = Member::with('user:id,email,role,is_verified,is_member,created_at')
            ->where('user_id', $userId)
            ->first();

        if (!$member) {
            return response()->json([
                'success' => false,
                'message' => 'Member not found.'
            ], 404);
        }

        return response()->json($member);
    }


    public function searchMembers(Request $request)
    {
        $user = Auth::user();

        // Only allow admin to access
        if ($user->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access. Only admins can search members.'
            ], 401);
        }

        $searchTerm = $request->input('query');

        $members = Member::with('user:id,email,role,is_verified,is_member,created_at')
            ->whereHas('user', function ($query) use ($searchTerm) {
                $query->where('email', 'like', '%' . $searchTerm . '%');
            })
            ->orWhere('full_name', 'like', '%' . $searchTerm . '%')
            ->orWhere('contact_number', 'like', '%' . $searchTerm . '%')
            ->select(['id', 'user_id', 'full_name', 'contact_number', 'address', 'gender', 'date_of_birth', 'membership_date'])
            ->orderByDesc('membership_date')
            ->paginate(25);
        return response()->json($members);
    }

    public function deleteMember($memberId)
    {
        $user = Auth::user();

        // Only allow admin to access
        if ($user->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access. Only admins can delete members.'
            ], 401);
        }

        $member = Member::find($memberId);

        if (!$member) {
            return response()->json([
                'success' => false,
                'message' => 'Member not found.'
            ], 404);
        }

        // Optionally, you might want to delete the associated user as well
        $userToDelete = User::find($member->user_id);
        if ($userToDelete) {
            $userToDelete->delete();
        }

        $member->delete();

        return response()->json([
            'success' => true,
            'message' => 'Member deleted successfully.'
        ]);
    }
}
