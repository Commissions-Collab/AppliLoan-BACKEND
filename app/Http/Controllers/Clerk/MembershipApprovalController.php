<?php

namespace App\Http\Controllers\Clerk;

use App\Http\Controllers\Controller;
use App\Models\Member;
use App\Models\ModelRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MembershipApprovalController extends Controller
{
    public function showMemberRequests($id)
    {
        $request = ModelRequest::find($id);

        if (!$request) {
            return response()->json(['message' => 'Request not found'], 404);
        }

        return response()->json($request);
    }

    public function getPendingRequests()
     {
            $pendingRequests = ModelRequest::where('status', 'pending')->get();

            return response()->json($pendingRequests);
    }

    public function getApprovedRequests()
    {
        $approvedRequests = ModelRequest::where('status', 'approved')->get();

        return response()->json($approvedRequests);
    }

    public function getRejectedRequests()
    {
        $approvedRequests = ModelRequest::where('status', 'rejected')->get();

        return response()->json($approvedRequests);
    }

    public function getAllRequests()
    {
        $requests = ModelRequest::orderBy('created_at', 'desc')->get();
        return response()->json($requests);
    }

    public function filterAndSortRequests(Request $request)
    {
        $query = ModelRequest::query();

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'full_name'); // Default to 'full_name'
        $order = $request->get('order', 'asc'); // Default to ascending

        if (in_array($sortBy, ['full_name', 'id']) && in_array($order, ['asc', 'desc'])) {
            $query->orderBy($sortBy, $order);
        }

        $requests = $query->get();

        return response()->json($requests);
    }

    // Membership Approval function 
    public function updateStatus(Request $request, $id)
{
    $request->validate([
        'status' => 'required|in:pending,approved,rejected',
    ]);

        DB::beginTransaction();

    try {
        $userRequest = ModelRequest::findOrFail($id);
        $userRequest->status = $request->status;
        $userRequest->save();

        if ($request->status === 'approved') {
            // Check if already a member (prevent duplicate entry)
            $existingMember = Member::where('member_number', $userRequest->member_number)->first();

            if (!$existingMember) {
                Member::create([
                    'user_id' => $userRequest->user_id,
                    'member_number' => $userRequest->member_number,
                    'full_name' => $userRequest->full_name,
                    'phone_number' => $userRequest->phone_number,
                    'address' => $userRequest->address,
                    'tin_number' => $userRequest->tin_number,
                    'date_of_birth' => $userRequest->date_of_birth,
                    'place_of_birth' => $userRequest->place_of_birth,
                    'age' => $userRequest->age,
                    'civil_status' => $userRequest->civil_status,
                    'religion' => $userRequest->religion,
                    'dependents' => $userRequest->dependents,
                    'employer' => $userRequest->employer,
                    'position' => $userRequest->position,
                    'monthly_income' => $userRequest->monthly_income,
                    'other_income' => $userRequest->other_income,
                    'share_capital' => $userRequest->share_capital,
                    'fixed_deposit' => $userRequest->fixed_deposit,
                    'seminar_date' => $userRequest->seminar_date,
                    'venue' => $userRequest->venue,
                    'status' => 'approved',
                    'brgy_clearance' => $userRequest->brgy_clearance,
                    'birth_cert' => $userRequest->birth_cert,
                    'certificate_of_employment' => $userRequest->certificate_of_employment,
                    'applicant_photo' => $userRequest->applicant_photo,
                    'valid_id' => $userRequest->valid_id,
                ]);
            }
        }

            DB::commit();

            return response()->json([
                'message' => 'Status updated successfully.',
                'data' => $userRequest,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Failed to update status.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function show($id)
    {
        $requestItem = ModelRequest::findOrFail($id);
        return response()->json($requestItem);
    }
} 