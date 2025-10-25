<?php

namespace App\Http\Controllers\Clerk;

use App\Http\Controllers\Controller;
use App\Mail\LoanStatusUpdateMail;
use App\Models\LoanApplication;
use App\Models\Member;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class LoanApplicationsController extends Controller
{

    // Display all loan applications with member and product details
    public function displayLoanApplication()
    {
        $displayLoanApplication = LoanApplication::select('application_date', 'applied_amount', 'status', 'term_months', 'product_id', "loan_type_id")
            ->with(['product:id,name', 'loanType:id,interest_rate'])
            ->get();

        return response()->json($displayLoanApplication);
    }

    public function showLoanApplication($id)
    {
        $loanApplication = LoanApplication::with(['product', 'loanType', 'processedBy'])
            ->find($id);

        if (!$loanApplication) {
            return response()->json(['message' => 'Loan application not found'], 404);
        }

        return response()->json($loanApplication);
    }

    public function updateLoanApplication(Request $request, $id)
    {
        // Validate incoming data
        $validated = $request->validate([
            'status' => 'required|string|in:pending,approved,rejected,cancelled',
            'rejection_reason' => 'nullable|string',
        ]);

        // Find loan application
        $loanApplication = LoanApplication::find($id);

        if (!$loanApplication) {
            return response()->json([
                'message' => 'Loan application not found.'
            ], 404);
        }

        // Get the related user (borrower)
        $user = User::find($loanApplication->user_id);

        // Enforce rejection reason if status is rejected
        if ($validated['status'] === 'rejected' && empty($validated['rejection_reason'])) {
            return response()->json([
                'message' => 'Rejection reason is required when the status is rejected.'
            ], 422);
        }

        // Update the application status and rejection reason
        $loanApplication->update([
            'status' => $validated['status'],
            'rejection_reason' => $validated['status'] === 'rejected'
                ? $validated['rejection_reason']
                : null,
        ]);

        // ✅ Send Email Notification
        try {
            if ($user && $user->email) {
                Mail::to($user->email)->send(
                    new LoanStatusUpdateMail($user, $loanApplication)
                );
            }
        } catch (\Exception $e) {
            // Log error but don't interrupt response
            Log::error('Failed to send loan status email', [
                'user_id' => $loanApplication->user_id,
                'error' => $e->getMessage(),
            ]);
        }

        // Success response
        return response()->json([
            'message' => 'Loan application status updated successfully.',
            'data' => $loanApplication
        ], 200);
    }

    public function deleteLoanApplication($id)
    {
        $loanApplication = LoanApplication::find($id);

        if (!$loanApplication) {
            return response()->json(['message' => 'Loan application not found'], 404);
        }

        $loanApplication->delete();

        return response()->json(['message' => 'Loan application deleted successfully']);
    }

    // Search loan applications by member name or product name
    public function searchLoanApplications(Request $request)
    {
        $search = $request->input('search');

        $loanApplications = LoanApplication::select('id', 'product_id', 'loan_type_id', 'processed_by', 'applied_amount', 'application_date', 'status')
            ->with([
                'loanType:id,name',
                'product:id,name',
                'processedBy:id,email'
            ])
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('status', 'like', "%{$search}%")
                        ->orWhereHas('product', function ($productQ) use ($search) {
                            $productQ->where('name', 'like', "%{$search}%");
                        })
                        ->orWhereHas('loanType', function ($loanTypeQ) use ($search) {
                            $loanTypeQ->where('name', 'like', "%{$search}%");
                        });
                });
            })
            ->get();
        return response()->json($loanApplications);
    }


    public function countLoanApplicationsByStatus()
    {
        $statuses = ['pending', 'approved', 'rejected', 'cancelled'];
        $counts = [];

        foreach ($statuses as $status) {
            $counts[$status] = LoanApplication::where('status', $status)->count();
        }

        return response()->json($counts);
    }

    public function countTotalLoanApplications()
    {
        $totalLoanApplications = LoanApplication::count();
        return response()->json(['total_loan_applications' => $totalLoanApplications]);
    }
}
