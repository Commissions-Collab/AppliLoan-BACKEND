<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Helpers\DashboardDataHelper;
use App\Helpers\DividendAnalyticsHelper;
use App\Helpers\LoanAnalyticsHelper;
use App\Helpers\MemberAnalyticsHelper;
use App\Helpers\SalesAnalyticsHelper;
use App\Http\Controllers\Controller;
use App\Models\Loan;
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
        $user = Auth::user();

        if ($user->role !== UserRole::ADMIN) {
            return response()->json(['success' => false, 'message' => 'Admin profile is not found'], 401);
        }

        $year = $request->get('year', Carbon::now()->year);
        $cacheKey = "dashboard_overview_{$year}";

        $data = Cache::remember($cacheKey, 3600, function () use ($year) {
            // Get current counts (not cached as they change frequently)
            $memberCount = Member::where('status', 'active')->count();
            $loansCount = Loan::where('status', 'active')->count();
            $inventoryItems = Product::where('status', 'active')->count();
            $monthlyRevenue = DashboardDataHelper::getCurrentMonthRevenue();

            // Get cached data
            $monthlySales = DashboardDataHelper::getMonthlySalesOverview($year);
            $notifications = DashboardDataHelper::getRecentNotications();

            // Format monthly sales for chart
            $chartLabels = [];
            $chartData = [];

            foreach ($monthlySales as $sale) {
                $chartLabels[] = $sale['month'];
                $chartData[] = $sale['sales'];
            }

            return [
                // Card stats
                'stats' => [
                    'total_members' => $memberCount,
                    'active_loans' => $loansCount,
                    'inventory_items' => $inventoryItems,
                    'monthly_revenue' => $monthlyRevenue
                ],

                // Chart data
                'monthly_sales_chart' => [
                    'labels' => $chartLabels,
                    'data' => $chartData,
                    'year' => $year
                ],

                // Raw monthly sales data
                'monthly_sales' => $monthlySales,

                // Notifications
                'notifications' => $notifications,

                // Metadata
                'current_year' => $year,
                'generated_at' => now()->toISOString()
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    public function salesAnalytics(Request $request)
    {
        $user = Auth::user();

        if ($user->role !== UserRole::ADMIN) {
            return response()->json([
                'success' => false,
                'message' => 'Admin profile is not found'
            ], 401);
        }

        $filter = $request->get('filter', 'per_year');   // yearly = per_year
        $year = $request->get('year', Carbon::now()->year);
        $month = $request->get('month', Carbon::now()->month);

        $cacheKey = "sales_analytics_{$filter}_{$year}_{$month}";

        $data = Cache::remember($cacheKey, 3600, function () use ($filter, $year, $month) {


            if ($filter === "weekly") {
                return [
                    "filter" => "weekly",
                    "year" => $year,
                    "month" => $month,
                    "weekly_revenue" => SalesAnalyticsHelper::getWeeklySales($year, $month)
                ];
            }


            if ($filter === "monthly") {
                return [
                    "filter" => "monthly",
                    "year" => $year,
                    "month" => $month,
                    "daily_revenue" => SalesAnalyticsHelper::getMonthlySales($year, $month)
                ];
            }


            return [
                "filter" => "per_year",
                "year" => $year,
                "total_revenue" => SalesAnalyticsHelper::getTotalRevenue($year),
                "average_monthly_revenue" => SalesAnalyticsHelper::getAverageMonthlyRevenue($year),
                "target_achievement" => SalesAnalyticsHelper::getTargetAchievement($year),
                "monthly_revenue_overview" => SalesAnalyticsHelper::getMonthlyRevenueOverview($year),
                "sales_by_category" => SalesAnalyticsHelper::getSalesByCategory($year)
            ];
        });

        return response()->json([
            "success" => true,
            "data" => $data
        ]);
    }


    public function dividendAnalytics(Request $request)
    {
        $user = Auth::user();

        if ($user->role !== UserRole::ADMIN) {
            return response()->json(['success' => false, 'message' => 'Admin profile is not found'], 401);
        }

        $year = $request->input('year', date('Y'));
        $quarter = $request->input('quarter'); // Null if not present
        $refresh = $request->input('refresh', false);
        $cacheKey = "dividend_analytics_{$year}" . ($quarter ? "_q{$quarter}" : '');

        if ($refresh) {
            Cache::forget($cacheKey);
        }

        $data = Cache::remember($cacheKey, 3600, function () use ($year, $quarter) {
            // Get the entire distribution payload once.
            $distributionData = DividendAnalyticsHelper::getDynamicDividendDistribution($year, $quarter);

            // Always get quarterly breakdown, but modify based on filter
            $quarterlyDividends = null;
            if (!$quarter) {
                // Show all quarters when no specific quarter filter
                $quarterlyDividends = DividendAnalyticsHelper::getQuarterlyDividends($year);
            } else {
                // Show only selected quarter data
                $quarterlyDividends = DividendAnalyticsHelper::getQuarterlyDividends($year, $quarter);
            }

            // Derive all other metrics from this single result.
            $totalDividends = collect($distributionData['distribution'])->sum('annual_dividend');

            // Calculate yield based on distribution method
            $averageYield = 0;
            $isPaymentBased = $distributionData['distribution_method'] === 'payments';

            if ($isPaymentBased) {
                // For payment-based, yield = dividend pool / total payments
                $totalPaid = $distributionData['total_paid_amount'];
                $averageYield = $totalPaid > 0 && $distributionData['total_dividend_pool'] > 0 ?
                    ($distributionData['total_dividend_pool'] / $totalPaid) * 100 : 0;
            } else {
                // For share capital-based methods
                $totalShare = $distributionData['total_share_capital'];
                $averageYield = $totalShare > 0 && $distributionData['total_dividend_pool'] > 0 ?
                    ($distributionData['total_dividend_pool'] / $totalShare) * 100 : 0;
            }

            return [
                'total_dividends_paid' => round($totalDividends, 2),
                'latest_annual_dividend' => round($totalDividends, 2),
                'average_yield' => round($averageYield, 2),
                'quarterly_dividends' => $quarterlyDividends,
                'annual_dividend_yield' => round($averageYield, 2),
                'dividend_distribution_table' => $distributionData,
                'member_dividend_breakdown' => $distributionData['distribution'],
                'dividend_settings' => DividendAnalyticsHelper::getDividendSettings($year, $quarter),
                'applied_filters' => [
                    'year' => $year,
                    'quarter' => $quarter,
                    'filter_description' => $quarter ? "Q{$quarter} {$year}" : "All quarters {$year}"
                ]
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    public function loanAnalytics(Request $request)
    {
        $user = Auth::user();

        if ($user->role !== UserRole::ADMIN) {
            return response()->json(['success' => false, 'message' => 'Admin profile is not found'], 401);
        }

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
        $user = Auth::user();

        if ($user->role !== UserRole::ADMIN) {
            return response()->json(['success' => false, 'message' => 'Admin profile is not found'], 401);
        }

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
