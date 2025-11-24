<?php

namespace App\Http\Controllers\Clerk;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Loan;
use App\Models\LoanPayment;
use App\Models\Member;
use App\Models\Product;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ClerkDashboardController extends Controller
{
    public function dashboardData(Request $request)
    {
        $user = Auth::user();

        if ($user->role !== UserRole::LOAN_CLERK) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        // Get today's date
        $today = Carbon::today();

        // Pending membership requests
        $pendingRequests = DB::table('requests')
            ->where('status', 'pending')
            ->count();

        // Recent loan applications (last 7 days)
        $recentApplications = Loan::with('application.product')
            ->where('created_at', '>=', Carbon::now()->subDays(7))
            ->count();

        // Active loans count
        $activeLoans = Loan::where('status', 'active')->count();

        // Today's payments count
        $todayPayments = LoanPayment::whereDate('payment_date', $today)->count();

        // Pending payments for approval
        $pendingPayments = LoanPayment::where('status', 'pending')->count();

        // Low stock alerts (products with stock < 10)
        $lowStockProducts = Product::where('status', 'active')
            ->where('stock_quantity', '<', 10)
            ->count();

        // Recent member registrations (last 30 days)
        $recentMembers = Member::where('created_at', '>=', Carbon::now()->subDays(30))
            ->count();

        // Monthly revenue (approved payments this month)
        $monthlyRevenue = LoanPayment::where('status', 'approved')
            ->whereYear('payment_date', Carbon::now()->year)
            ->whereMonth('payment_date', Carbon::now()->month)
            ->sum('amount_paid');

        // Recent activities (last 5)
        $recentActivities = $this->getRecentActivities();

        return response()->json([
            'success' => true,
            'data' => [
                'stats' => [
                    'pending_requests' => $pendingRequests,
                    'recent_applications' => $recentApplications,
                    'active_loans' => $activeLoans,
                    'today_payments' => $todayPayments,
                    'pending_payments' => $pendingPayments,
                    'low_stock_alerts' => $lowStockProducts,
                    'recent_members' => $recentMembers,
                    'monthly_revenue' => $monthlyRevenue
                ],
                'recent_activities' => $recentActivities,
                'generated_at' => now()->toISOString()
            ]
        ]);
    }

    private function getRecentActivities()
    {
        $activities = [];

        // Recent payments approved
        $recentPayments = LoanPayment::with(['loan.application.user'])
            ->where('status', 'approved')
            ->orderBy('updated_at', 'desc')
            ->limit(3)
            ->get()
            ->map(function ($payment) {
                return [
                    'type' => 'payment_approved',
                    'message' => 'Payment of ₱' . number_format($payment->amount_paid, 2) . ' approved',
                    'user' => $payment->loan?->application?->user?->email ?? 'Unknown',
                    'timestamp' => $payment->updated_at->diffForHumans()
                ];
            });

        // Recent loan applications
        $recentLoans = Loan::with('application.user')
            ->orderBy('created_at', 'desc')
            ->limit(2)
            ->get()
            ->map(function ($loan) {
                return [
                    'type' => 'loan_application',
                    'message' => 'New loan application for ₱' . number_format($loan->principal_amount, 2),
                    'user' => $loan->application?->user?->email ?? 'Unknown',
                    'timestamp' => $loan->created_at->diffForHumans()
                ];
            });

        // Recent membership requests
        $recentMemberships = DB::table('requests')
            ->join('users', 'requests.user_id', '=', 'users.id')
            ->where('requests.created_at', '>=', Carbon::now()->subDays(7))
            ->orderBy('requests.created_at', 'desc')
            ->limit(2)
            ->get(['users.email', 'requests.created_at'])
            ->map(function ($request) {
                return [
                    'type' => 'membership_request',
                    'message' => 'New membership request submitted',
                    'user' => $request->email,
                    'timestamp' => Carbon::parse($request->created_at)->diffForHumans()
                ];
            });

        // Combine and sort by timestamp (most recent first)
        $activities = collect()
            ->merge($recentPayments)
            ->merge($recentLoans)
            ->merge($recentMemberships)
            ->sortByDesc(function ($activity) {
                return strtotime($activity['timestamp']);
            })
            ->take(5)
            ->values()
            ->toArray();

        return $activities;
    }
}
