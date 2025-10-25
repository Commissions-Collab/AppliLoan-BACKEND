<?php

namespace App\Http\Controllers\Clerk;

use App\Http\Controllers\Controller;
use App\Models\Member;
use App\Models\ModelRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use App\Mail\StatusUpdateMail;

class MembershipApprovalController extends Controller
{
    /**
     * Get latest membership request by user_id
     */
    public function getRequestByUser($userId)
    {
        $request = ModelRequest::where('user_id', $userId)
            ->orderByDesc('created_at')
            ->first();
        if (!$request) {
            return response()->json(['message' => 'No membership request found for user'], 404);
        }
        // Attach document URLs like in show()
        $data = $request->toArray();
        $data['documents'] = [
            'brgy_clearance' => $request->brgy_clearance ? asset('storage/' . $request->brgy_clearance) : null,
            'birth_cert' => $request->birth_cert ? asset('storage/' . $request->birth_cert) : null,
            'certificate_of_employment' => $request->certificate_of_employment ? asset('storage/' . $request->certificate_of_employment) : null,
            'applicant_photo' => $request->applicant_photo ? asset('storage/' . $request->applicant_photo) : null,
            'valid_id_front' => $request->valid_id_front ? asset('storage/' . $request->valid_id_front) : null,
            'valid_id_back' => $request->valid_id_back ? asset('storage/' . $request->valid_id_back) : null,
        ];
        return response()->json($data);
    }
    /**
     * Delete a membership request by ID.
     * Admin-only via route middleware. This deletes the request record only.
     */
    public function destroy($id)
    {
        $request = ModelRequest::find($id);
        if (!$request) {
            return response()->json(['message' => 'Request not found'], 404);
        }

        try {
            $request->delete();
            return response()->json(['message' => 'Request deleted successfully.']);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to delete request.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function showMemberRequests($id)
    {
        $request = ModelRequest::find($id);

        if (!$request) {
            return response()->json(['message' => 'Request not found'], 404);
        }

        // Add document URLs
        $requestData = $request->toArray();
        $requestData['documents'] = [
            'brgy_clearance' => $request->brgy_clearance
                ? asset('storage/' . $request->brgy_clearance)
                : null,
            'birth_cert' => $request->birth_cert
                ? asset('storage/' . $request->birth_cert)
                : null,
            'certificate_of_employment' => $request->certificate_of_employment
                ? asset('storage/' . $request->certificate_of_employment)
                : null,
            'applicant_photo' => $request->applicant_photo
                ? asset('storage/' . $request->applicant_photo)
                : null,
            'valid_id_front' => $request->valid_id_front
                ? asset('storage/' . $request->valid_id_front)
                : null,
            'valid_id_back' => $request->valid_id_back
                ? asset('storage/' . $request->valid_id_back)
                : null,
        ];

        return response()->json($requestData);
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
                        'number_of_children' => $userRequest->number_of_children,
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
                        'valid_id_front' => $userRequest->valid_id_front,
                        'valid_id_back' => $userRequest->valid_id_back,
                        'spouse_name' => $userRequest->spouse_name,
                        'spouse_employer' => $userRequest->spouse_employer,
                        'spouse_monthly_income' => $userRequest->spouse_monthly_income,
                        'spouse_birth_day' => $userRequest->spouse_birth_day,
                    ]);
                }

                // Mark user as member
                $user = User::find($userRequest->user_id);
                if ($user && !$user->is_member) {
                    $user->is_member = 1;
                    $user->save();
                }
            }

            DB::commit();

            if ($user && $user->email) {
                Mail::to($user->email)->send(new StatusUpdateMail($user, $request->status));
            }

            $response = [
                'message' => 'Status updated successfully.',
                'data' => $userRequest,
            ];
            if ($request->status === 'approved') {
                $response['user'] = isset($user) ? $user->only(['id', 'email', 'is_member']) : null;
                $response['member'] = $user?->member;
            }
            return response()->json($response);
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

        // Add document URLs
        $requestData = $requestItem->toArray();
        $requestData['documents'] = [
            'brgy_clearance' => $requestItem->brgy_clearance
                ? asset('storage/' . $requestItem->brgy_clearance)
                : null,
            'birth_cert' => $requestItem->birth_cert
                ? asset('storage/' . $requestItem->birth_cert)
                : null,
            'certificate_of_employment' => $requestItem->certificate_of_employment
                ? asset('storage/' . $requestItem->certificate_of_employment)
                : null,
            'applicant_photo' => $requestItem->applicant_photo
                ? asset('storage/' . $requestItem->applicant_photo)
                : null,
            'valid_id_front' => $requestItem->valid_id_front
                ? asset('storage/' . $requestItem->valid_id_front)
                : null,
            'valid_id_back' => $requestItem->valid_id_back
                ? asset('storage/' . $requestItem->valid_id_back)
                : null,
        ];

        return response()->json($requestData);
    }
}
