<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Member;
use App\Models\ModelRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MembershipApprovalController extends Controller
{

    public function store(Request $request)
    {
         $validated = $request->validate([
        'request_to' => 'required|exists:users,id',
        'member_number' => 'required|string|unique:requests,member_number',
        'full_name' => 'required|string|max:255',
        'phone_number' => 'required|string|max:20',
        'street_address' => 'nullable|string|max:255',
        'city' => 'nullable|string|max:100',
        'province' => 'nullable|string|max:100',
        'postal_code' => 'nullable|string|max:10',
        'tin_number' => 'nullable|string|max:20',
        'date_of_birth' => 'nullable|date',
        'place_of_birth' => 'nullable|string|max:100',
        'age' => 'nullable|integer',
        'dependents' => 'nullable|integer',
        'employer' => 'nullable|string|max:255',
        'position' => 'nullable|string|max:100',
        'monthly_income' => 'nullable|numeric',
        'other_income' => 'nullable|numeric',
        'monthly_disposable_income_range' => 'nullable|in:0-5000,5001-10000,10001-20000,20001+',
        'status' => 'in:pending,approved,rejected'
    ]);
        $validated['status'] = $validated['status'] ?? 'pending';

        $requestData = ModelRequest::create($validated);

        return response()->json([
            'message' => 'Request submitted successfully.',
            'data' => $requestData
        ], 201);
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
                        'user_id' => $userRequest->id,
                        'member_number' => $userRequest->member_number,
                        'full_name' => $userRequest->full_name,
                        'phone_number' => $userRequest->phone_number,
                        'street_address' => $userRequest->street_address,
                        'city' => $userRequest->city,
                        'province' => $userRequest->province,
                        'postal_code' => $userRequest->postal_code,
                        'tin_number' => $userRequest->tin_number,
                        'date_of_birth' => $userRequest->date_of_birth,
                        'place_of_birth' => $userRequest->place_of_birth,
                        'age' => $userRequest->age,
                        'dependents' => $userRequest->dependents,
                        'employer' => $userRequest->employer,
                        'position' => $userRequest->position,
                        'monthly_income' => $userRequest->monthly_income,
                        'other_income' => $userRequest->other_income,
                        'monthly_disposable_income_range' => $userRequest->monthly_disposable_income_range,
                        'status' => 'active',
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