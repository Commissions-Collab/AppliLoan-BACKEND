<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\LoanClerk;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ManageClerkController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $user = Auth::user();

        if ($user->role !== UserRole::ADMIN) {
            return response()->json(['success' => false, 'message' => 'Admin profile is not found'], 401);
        }

        $clerks = LoanClerk::with('user:id,email')
            ->select(['id', 'user_id', 'clerk_id', 'full_name', 'contact_number', 'address', 'gender', 'job_title', 'date_hired', 'status'])
            ->orderByDesc('date_hired')
            ->paginate(25);

        return response()->json($clerks);
    }

    /**
     * Summary of store
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        DB::beginTransaction();

        try {
            $user = Auth::user();

            if ($user->role !== UserRole::ADMIN) {
                return response()->json(['success' => false, 'message' => 'Admin profile is not found'], 401);
            }

            $validated = $request->validate([
                'full_name' => ['regex:/^[A-Za-z\s]+$/', 'required', 'max:255'],
                'contact_number' => ['required', 'digits:11'],
                'address' => ['nullable', 'string', 'max:100'],
                'gender' => ['required', 'string', 'in:Male,Female'],
                'job_title' => ['regex:/^[A-Za-z\s]+$/', 'required', 'max:255'],
                'date_hired' => ['date', 'required'],
                'status' => ['required', 'string', 'in:active,inactive,terminated'],

                'email' => ['email', 'regex:/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/', 'required', 'unique:users'],
                'password' => ['required', 'min:6', 'confirmed']
            ]);

            $hashedPassword = Hash::make($validated['password']);
            $user = User::create([
                'email' => $validated['email'],
                'email_verified_at' => Carbon::now(),
                'password' => $hashedPassword,
                'role' => 'loan_clerk',
                'is_verified' => true
            ]);

            LoanClerk::create([
                'user_id' => $user->id,
                'clerk_id' => strtoupper('CLERK-' . str_pad(LoanClerk::max('id') + 1, 4, '0', STR_PAD_LEFT)),
                'full_name' => $validated['full_name'],
                'contact_number' => $validated['contact_number'],
                'address' => $validated['address'],
                'gender' => $validated['gender'],
                'job_title' => $validated['job_title'],
                'date_hired' => $validated['date_hired'],
                'status' => $validated['status']
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Successfully created loan clerk profile'
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error on creation of loan clerk profile',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Summary of update
     * @param \Illuminate\Http\Request $request
     * @param string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, string $id)
    {
        DB::beginTransaction();

        try {
            $user = Auth::user();

            if ($user->role !== UserRole::ADMIN) {
                return response()->json(['success' => false, 'message' => 'Admin profile is not found'], 401);
            }

            $clerk = LoanClerk::findOrFail($id);

            $validated = $request->validate([
                'full_name' => ['regex:/^[A-Za-z\s]+$/', 'required', 'max:255'],
                'contact_number' => ['required', 'digits:11'],
                'address' => ['nullable', 'string', 'max:100'],
                'gender' => ['required', 'string', 'in:Male,Female'],
                'job_title' => ['regex:/^[A-Za-z\s]+$/', 'required', 'max:255'],
                'date_hired' => ['date', 'required'],
                'status' => ['required', 'string', 'in:active,inactive,terminated'],

                // *ignore current user’s email in unique validation
                'email' => ['required', 'email', 'regex:/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/', 'unique:users,email,' . $clerk->user_id],
                'password' => ['nullable', 'min:6', 'confirmed'],
            ]);

            $clerk->user->update([
                'email' => $validated['email'],
                'password' => !empty($validated['password']) ? Hash::make($validated['password']) : $clerk->user->password,
            ]);

            $clerk->update([
                'full_name' => $validated['full_name'],
                'contact_number' => $validated['contact_number'],
                'address' => $validated['address'],
                'gender' => $validated['gender'],
                'job_title' => $validated['job_title'],
                'date_hired' => $validated['date_hired'],
                'status' => $validated['status']
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Successfully updated loan clerk profile'
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error on updating loan clerk profile',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        DB::beginTransaction();

        try {
            $user = Auth::user();

            if ($user->role !== UserRole::ADMIN) {
                return response()->json(['success' => false, 'message' => 'Admin profile is not found'], 401);
            }

            $clerk = LoanClerk::findOrFail($id);

            $clerk->user->delete();
            $clerk->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Successfully deleted loan clerk profile'
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error on deleting loan clerk profile',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
