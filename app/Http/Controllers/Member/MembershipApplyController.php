<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\ModelRequest;
use Illuminate\Http\Request;

class MembershipApplyController extends Controller
{

    public function applyForMembership(Request $request)
    {
        $validated = $request->validate([
            'request_to' => 'required|exists:users,id',
            'user_id' => 'required|exists:users,id',
            'member_number' => 'required|string|unique:requests,member_number',
            'full_name' => 'required|string|max:255',
            'phone_number' => 'required|string|max:20',
            'address' => 'nullable|string|max:255',
            'date_of_birth' => 'nullable|date',
            'place_of_birth' => 'nullable|string|max:100',
            'age' => 'nullable|integer',
            'civil_status' => 'nullable|in:single,married,widowed,separated',
            'religion' => 'nullable|string|max:100',
            'tin_number' => 'nullable|integer',
            'employer' => 'nullable|string|max:255',
            'position' => 'nullable|string|max:100',
            'monthly_income' => 'nullable|numeric',
            'other_income' => 'nullable|string|max:255',
            'dependents' => 'nullable|integer',
            'seminar_date' => 'required|string|max:100',
            'venue' => 'required|string|max:255',

            // Accept files instead of strings
            'brgy_clearance' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'birth_cert' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'certificate_of_employment' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'applicant_photo' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'valid_id' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
        ]);

        // Handle file uploads and store their paths
        $fileFields = [
            'brgy_clearance',
            'birth_cert',
            'certificate_of_employment',
            'applicant_photo',
            'valid_id'
        ];

        foreach ($fileFields as $field) {
            if ($request->hasFile($field)) {
                $validated[$field] = $request->file($field)->store('requests', 'public');
            }
        }

        // Set default status
        $validated['status'] = $validated['status']??'pending';
        $validated['request_to'] = '2';

        // Create the record
        $requestData = ModelRequest::create($validated);

        return response()->json([
            'message' => 'Membership application submitted successfully.',
            'data' => $requestData
        ], 201);
    }

        
}
