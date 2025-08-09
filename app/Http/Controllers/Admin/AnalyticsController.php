<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\LoanAnalyticsHelper;
use App\Helpers\MemberAnalyticsHelper;
use App\Helpers\SalesAnalyticsHelper;
use App\Http\Controllers\Controller;
use App\Models\Loan;
use App\Models\LoanApplication;
use App\Models\LoanPayment;
use App\Models\Member;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class AnalyticsController extends Controller
{
    public function dashboardData()
    {
        $admin = Auth::user();

        if (!$admin) {
            return response()->json([
                'success' => false,
                'message' => 'Admin profile is not found'
            ]);
        }

        $memberCount = Member::where('status', 'active')->count();
        $loansCount = Loan::where('status', 'active')->count();
        $inventoryItems = Product::where('status', 'active')->count();

        return Cache::remember('dashboard_overview', 3600, function () use ($memberCount, $loansCount, $inventoryItems) {
            return response()->json([
                'total_members' => $memberCount,
                'active_loans' => $loansCount,
                'inventory_items' => $inventoryItems,
                'monthly_revenue' => $this->getCurrentMonthRevenue()
            ]);
        });
    }

    public function salesAnalytics(Request $request)
    {
        $admin = Auth::user();

        if (!$admin) {
            return response()->json([
                'success' => false,
                'message' => 'Admin profile is not found'
            ]);
        }

        $year = $request->get('year', Carbon::now()->year);
        $cacheKey = "sales_analytics_{$year}";

        return Cache::remember($cacheKey, 3600, function () use ($year) {
            return response()->json([
                'total_revenue' => SalesAnalyticsHelper::getTotalRevenue($year),
                'average_monthly_revenue' => SalesAnalyticsHelper::getAverageMonthlyRevenue($year),
                'target_achievement' => SalesAnalyticsHelper::getTargetAchievement($year),
                'monthly_revenue_overview' => SalesAnalyticsHelper::getMonthlyRevenueOverview($year),
                'sales_by_category' => SalesAnalyticsHelper::getSalesByCategory($year)
            ]);
        });
    }

    // public function dividendAnalytics(Request $request) {
    //      $year = $request->get('year', date('Y'));
    //     $cacheKey = "dividend_analytics_{$year}";
        
    //     return Cache::remember($cacheKey, 3600, function () use ($year) {
    //         return response()->json([
    //             'total_dividends_paid' => $this->getTotalDividendsPaid(),
    //             'latest_annual_dividend' => $this->getLatestAnnualDividend(),
    //             'average_yield' => $this->getAverageYield(),
    //             'quarterly_dividends' => $this->getQuarterlyDividends($year),
    //             'annual_dividend_yield' => $this->getAnnualDividendYield()
    //         ]);
    //     });
    // }

    public function loanAnalytics(Request $request)
    {
        $period = $request->get('period', 'last_6_months');
        $cacheKey = "loan_analytics_{$period}";

        return Cache::remember($cacheKey, 3600, function () use ($period) {
            return response()->json([
                'total_loan_amount' => LoanAnalyticsHelper::getTotalLoanAmount($period),
                'total_applications' => LoanAnalyticsHelper::getTotalApplications($period),
                'approved_count' => LoanAnalyticsHelper::getApprovedLoansCount($period),
                'pending_count' => LoanAnalyticsHelper::getPendingLoansCount($period),
                'loan_performance' => LoanAnalyticsHelper::getLoanPerformance($period),
                'loan_distribution' => LoanAnalyticsHelper::getLoanDistribution($period)
            ]);
        });
    }

    public function memberAnalytics(Request $request)
    {
        $period = $request->get('period', 'last_6_months');
        $cacheKey = "member_analytics_{$period}";
        
        return Cache::remember($cacheKey, 3600, function () use ($period) {
            return response()->json([
                'total_members' => MemberAnalyticsHelper::getTotalMembers($period),
                'growth_rate' => MemberAnalyticsHelper::getMemberGrowthRate($period),
                'new_members' => MemberAnalyticsHelper::getNewMembers($period),
                'churn_rate' => MemberAnalyticsHelper::getChurnRate($period),
                'membership_growth' => MemberAnalyticsHelper::getMembershipGrowth($period),
                'member_activity' => MemberAnalyticsHelper::getMemberActivity($period)
            ]);
        });
    }

    private function getCurrentMonthRevenue()
    {
        return LoanPayment::whereYear('payment_date', date('Y'))
            ->whereMonth('payment_date', date('n'))
            ->sum('amount_paid');
    }
}
