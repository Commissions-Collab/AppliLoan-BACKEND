<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Member;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class MemberManagementController extends Controller
{
    // Controller methods for member management will go here

    public function displayAllUsers(Request $request)
    {   
        $user = Auth::user();

        // Only allow admin to access
        if ($user->role !== UserRole::ADMIN) {
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
        if ($user->role !== UserRole::ADMIN) {
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
        if ($user->role !== UserRole::ADMIN) {
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

    // Delete by userId: remove the User and any related Member if present (robust for applicants without Member rows)
    public function deleteMember($userId)
    {
        $authUser = Auth::user();

        // Only allow admin to access
        if ($authUser->role !== UserRole::ADMIN) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access. Only admins can delete members.'
            ], 401);
        }

        $user = User::find($userId);
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found.'
            ], 404);
        }

        DB::transaction(function () use ($userId, $user) {
            // Delete related member first if exists
            $member = Member::where('user_id', $userId)->first();
            if ($member) {
                $member->delete();
            }
            // Then delete the user account
            $user->delete();
        });

        return response()->json([
            'success' => true,
            'message' => 'User and related member (if any) deleted successfully.'
        ]);
    }

    public function bulkDeleteUsers(Request $request)
    {
        $authUser = Auth::user();

        if ($authUser->role !== UserRole::ADMIN) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access. Only admins can delete members.'
            ], 401);
        }

        $userIds = $request->input('user_ids');

        if (!is_array($userIds) || empty($userIds)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid request. Provide an array of user_ids.'
            ], 422);
        }

        $deleted = 0;
        DB::transaction(function () use ($userIds, &$deleted) {
            foreach ($userIds as $uid) {
                // Delete related member first if present
                $member = Member::where('user_id', $uid)->first();
                if ($member) {
                    $member->delete();
                }
                // Delete the user record if exists
                $userToDelete = User::find($uid);
                if ($userToDelete) {
                    $userToDelete->delete();
                    $deleted++;
                }
            }
        });

        return response()->json([
            'success' => true,
            'deleted' => $deleted,
            'message' => "Deleted {$deleted} member(s)."
        ]);
    }
}
