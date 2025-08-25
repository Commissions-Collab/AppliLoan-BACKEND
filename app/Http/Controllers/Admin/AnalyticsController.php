<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\DashboardDataHelper;
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
    public function dashboardData(Request $request)
    {
        $admin = Auth::user();

        if (!$admin) {
            return response()->json([
                'success' => false,
                'message' => 'Admin profile is not found'
            ]);
        }

        $year = $request->get('year', Carbon::now()->year);
        $memberCount = Member::where('status', 'active')->count();
        $loansCount = Loan::where('status', 'active')->count();
        $inventoryItems = Product::where('status', 'active')->count();

        $cacheKey = "dashboard_overview_{$year}";

        $data = Cache::remember($cacheKey, 3600, function () use ($memberCount, $loansCount, $inventoryItems, $year) {
            return [
                'total_members' => $memberCount,
                'active_loans' => $loansCount,
                'inventory_items' => $inventoryItems,
                'monthly_revenue' => DashboardDataHelper::getCurrentMonthRevenue(),
                'monthly_sales' => DashboardDataHelper::getMonthlySalesOverview($year),
                'notifications' => DashboardDataHelper::getRecentNotications()
            ];
        });

        return response()->json($data);
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

        $data = Cache::remember($cacheKey, 3600, function () use ($year) {
            return [
                'total_revenue' => SalesAnalyticsHelper::getTotalRevenue($year),
                'average_monthly_revenue' => SalesAnalyticsHelper::getAverageMonthlyRevenue($year),
                'target_achievement' => SalesAnalyticsHelper::getTargetAchievement($year),
                'monthly_revenue_overview' => SalesAnalyticsHelper::getMonthlyRevenueOverview($year),
                'sales_by_category' => SalesAnalyticsHelper::getSalesByCategory($year)
            ];
        });

        return response()->json($data);
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

        $data = Cache::remember($cacheKey, 3600, function () use ($period) {
            return [
                'total_loan_amount' => LoanAnalyticsHelper::getTotalLoanAmount($period),
                'total_applications' => LoanAnalyticsHelper::getTotalApplications($period),
                'approved_count' => LoanAnalyticsHelper::getApprovedLoansCount($period),
                'pending_count' => LoanAnalyticsHelper::getPendingLoansCount($period),
                'loan_performance' => LoanAnalyticsHelper::getLoanPerformance($period),
                'loan_distribution' => LoanAnalyticsHelper::getLoanDistribution($period)
            ];
        });

        return response()->json($data);
    }

    public function memberAnalytics(Request $request)
    {
        $period = $request->get('period', 'last_6_months');
        $activityPeriod = $request->get('activity_period', 'this_week');

        $cacheKey = "member_analytics_{$period}_{$activityPeriod}";

        $data = Cache::remember($cacheKey, 3600, function () use ($period, $activityPeriod) {
            // Get the helper data
            $totalMembers = MemberAnalyticsHelper::getTotalMembers($period);
            $growthRate = MemberAnalyticsHelper::getMemberGrowthRate($period);
            $newMembers = MemberAnalyticsHelper::getNewMembers($period);
            $churnRate = MemberAnalyticsHelper::getChurnRate($period);
            $membershipGrowth = MemberAnalyticsHelper::getMembershipGrowth($period);

            // Get activity data with separate period
            $memberActivity = MemberAnalyticsHelper::getMemberActivity($activityPeriod);

            // Calculate additional stats
            $activeMembers = Member::where('status', 'active')->count();
            $inactiveMembers = Member::where('status', 'inactive')->count();

            // Format growth chart data
            $growthChartLabels = [];
            $growthChartActiveMembers = [];
            $growthChartNewMembers = [];
            $growthChartChurnedMembers = [];

            foreach ($membershipGrowth as $item) {
                $growthChartLabels[] = $item['month'];
                $growthChartActiveMembers[] = $item['active_members'];
                $growthChartNewMembers[] = $item['new_members'];
                $growthChartChurnedMembers[] = $item['churned_members'];
            }

            // Format activity chart data
            $activityChartLabels = [];
            $activityChartLogins = [];
            $activityChartEngagement = [];

            foreach ($memberActivity as $item) {
                $activityChartLabels[] = $item['day'];
                $activityChartLogins[] = $item['logins'];
                $activityChartEngagement[] = $item['engagement_score'];
            }

            return [
                // Stats for MembershipStat component
                'total_members' => $totalMembers['count'],
                'active_members' => $activeMembers,
                'inactive_members' => $inactiveMembers,
                'growth_rate' => $growthRate['growth'],

                // Additional stats
                'new_members_count' => $newMembers,
                'churn_rate' => $churnRate['rate'],

                // Chart data for MembershipGrowth component
                'growth_chart' => [
                    'labels' => $growthChartLabels,
                    'active_members' => $growthChartActiveMembers,
                    'new_members' => $growthChartNewMembers,
                    'churned_members' => $growthChartChurnedMembers,
                ],

                // Chart data for MemberActivityChart component
                'activity_chart' => [
                    'labels' => $activityChartLabels,
                    'logins' => $activityChartLogins,
                    'engagement_score' => $activityChartEngagement,
                ],

                // Current filters
                'current_period' => $period,
                'current_activity_period' => $activityPeriod,
            ];
        });

        return response()->json($data);
    }
}
