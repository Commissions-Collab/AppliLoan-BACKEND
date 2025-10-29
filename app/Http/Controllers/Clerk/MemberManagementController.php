<?php

namespace App\Http\Controllers\Clerk;

use App\Http\Controllers\Controller;
use App\Models\LoanApplication;
use App\Models\LoanPayment;
use App\Models\Member;
use App\Models\Request;

class MemberManagementController extends Controller
{
    //  Controller methods for member management can be added here

    public function displayAllMembers(){
        // Logic to display all members
        $members = Member::select('id', 'member_number', 'full_name', 'phone_number', 'status')
        ->withCount(['loanApplications as active_loans_count' => function ($query) {
                $query->where('status', 'active');
            }
        ])
        ->with('loans:loan_application_id')
        ->get();

        return response()->json($members);

    }
    // Show member balance by loan ID
    // Loan ID is the ID of the loan_application_id in the loans table
    public function showMemberBalance($loanId){
        $balance = LoanPayment::select('remaining_balance')->where('id', $loanId)
        ->first();
        return response()->json($balance);
    }

    // Count total members
    public function countTotalMembers(){
        $totalMembers = Member::count();
        return response()->json(['total_members' => $totalMembers]);
    }


    // Count members by status
    public function countActiveMember(){
        $activeMembers = Member::where('status', 'active')->count();
        return response()->json(['active_members' => $activeMembers]);
    }
    public function countInactiveMember(){
        $inactiveMembers = Member::where('status', 'inactive')->count();
        return response()->json(['inactive_members' => $inactiveMembers]);
    }
    public function countPendingMember(){
        $pendingMembers = Member::where('status', 'pending')->count();
        return response()->json(['pending_members' => $pendingMembers]);
    }
    // Get member details by ID
    public function getMemberDetails($id){
        $member = Member::find($id);
        if (!$member) {
            return response()->json(['message' => 'Member not found'], 404);
        }
        return response()->json($member);
    }
    public function updateMember(Request $request, $Id){
        $updateStatus = $request->validate([
            'status' => 'required|in:active,inactive,pending'
        ]);
        $member = Member::find($Id);
        if (!$member) {
            return response()->json(['message' => 'Member not found'], 404);
        }
        $member->update($updateStatus);
        return response()->json(['message' => 'Member status updated successfully', 'member' => $member]); 
    }

    public function deleteMember($Id){
        $member = Member::find($Id);
        if (!$member) {
            return response()->json(['message' => 'Member not found'], 404);
        }   
        $member->delete();
        return response()->json(['message' => 'Member deleted successfully']);
    }

    public function searchMember(Request $request){
        $search = $request->input('search');
        $members = Member::select('id', 'member_number', 'full_name', 'phone_number', 'status')
        ->where(function ($q) use ($search) {
            $q->where('full_name', 'like', "%{$search}%")
              ->orWhere('member_number', 'like', "%{$search}%")
              ->orWhere('phone_number', 'like', "%{$search}%");
        })
        ->get();   
        return response()->json($members);
    }
}
