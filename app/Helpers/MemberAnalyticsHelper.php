<?php

namespace App\Helpers;

use App\Models\Member;
use App\Models\MemberEngagement;
use App\Models\MemberLogin;
use Carbon\Carbon;

class MemberAnalyticsHelper
{
    private static function getDateRange($period)
    {
        $now = Carbon::now();

        switch ($period) {
            case 'current_year':
                $startCurrent = $now->copy()->startOfYear();
                $startPrevious = $now->copy()->subYear()->startOfYear();
                $endPrevious = $now->copy()->subYear()->endOfYear();
                break;
            case 'last_6_months':
                $startCurrent = $now->copy()->subMonths(5)->startOfMonth();
                $startPrevious = $now->copy()->subMonths(11)->startOfMonth();
                $endPrevious = $now->copy()->subMonths(6)->endOfMonth();
                break;
            case 'last_12_months':
            default:
                $startCurrent = $now->copy()->subMonths(11)->startOfMonth();
                $startPrevious = $now->copy()->subMonths(23)->startOfMonth();
                $endPrevious = $now->copy()->subMonths(12)->endOfMonth();
                break;
        }

        return [
            'current_start'  => $startCurrent,
            'current_end'    => $now,
            'previous_start' => $startPrevious,
            'previous_end'   => $endPrevious
        ];
    }

    private static function getActivityDateRange($period)
    {
        $now = Carbon::now();

        switch ($period) {
            case 'this_week':
                return [
                    'start' => $now->copy()->startOfWeek(),
                    'end' => $now->copy()->endOfWeek()
                ];
            case 'last_week':
                return [
                    'start' => $now->copy()->subWeek()->startOfWeek(),
                    'end' => $now->copy()->subWeek()->endOfWeek()
                ];
            case 'this_month':
                return [
                    'start' => $now->copy()->startOfMonth(),
                    'end' => $now->copy()->endOfMonth()
                ];
            case 'last_month':
                return [
                    'start' => $now->copy()->subMonth()->startOfMonth(),
                    'end' => $now->copy()->subMonth()->endOfMonth()
                ];
            default:
                return [
                    'start' => $now->copy()->startOfWeek(),
                    'end' => $now->copy()->endOfWeek()
                ];
        }
    }

    public static function getTotalMembers($period)
    {
        $range = self::getDateRange($period);

        $total = Member::where('status', 'active')->count();

        $activeRecent = Member::where('status', 'active')
            ->whereBetween('updated_at', [$range['current_start'], $range['current_end']])
            ->count();

        return [
            'count' => $total,
            'growth_percentage' => $total > 0
                ? round(($activeRecent / $total) * 100, 1)
                : 0
        ];
    }

    public static function getMemberGrowthRate($period)
    {
        $range = self::getDateRange($period);

        $current = Member::whereBetween('created_at', [$range['current_start'], $range['current_end']])->count();
        $previous = Member::whereBetween('created_at', [$range['previous_start'], $range['previous_end']])->count();

        $growth = $previous > 0 ? round((($current - $previous) / $previous) * 100, 1) : 0;

        return [
            'current' => $current,
            'previous' => $previous,
            'growth' => $growth
        ];
    }

    public static function getNewMembers($period)
    {
        $range = self::getDateRange($period);

        return Member::whereBetween('created_at', [$range['current_start'], $range['current_end']])
            ->count();
    }

    public static function getChurnRate($period)
    {
        $range = self::getDateRange($period);

        $totalMembers = Member::count();
        $inactiveMembers = Member::where('status', 'inactive')
            ->whereBetween('updated_at', [$range['current_start'], $range['current_end']])
            ->count();

        $rate = $totalMembers > 0 ? round(($inactiveMembers / $totalMembers) * 100, 1) : 0;

        return [
            'rate' => $rate,
            'inactive_count' => $inactiveMembers,
            'total_count' => $totalMembers
        ];
    }

    public static function getMembershipGrowth($period)
    {
        $data = [];
        
        if ($period === 'current_year') {
            $year = Carbon::now()->year;

            for ($month = 1; $month <= 12; $month++) {
                $activeMembers = Member::whereYear('created_at', $year)
                    ->whereMonth('created_at', $month)
                    ->where('status', 'active')
                    ->count();

                $newMembers = Member::whereYear('created_at', $year)
                    ->whereMonth('created_at', $month)
                    ->count();

                $churned = Member::where('status', 'inactive')
                    ->whereYear('updated_at', $year)
                    ->whereMonth('updated_at', $month)
                    ->count();

                $data[] = [
                    'month' => Carbon::create()->month($month)->format('M'),
                    'active_members' => $activeMembers,
                    'new_members' => $newMembers,
                    'churned_members' => $churned
                ];
            }
        } else {
            $months = $period === 'last_6_months' ? 6 : 12;

            for ($i = $months - 1; $i >= 0; $i--) {
                $date = Carbon::now()->subMonths($i);

                $activeMembers = Member::whereYear('created_at', $date->year)
                    ->whereMonth('created_at', $date->month)
                    ->where('status', 'active')
                    ->count();

                $newMembers = Member::whereYear('created_at', $date->year)
                    ->whereMonth('created_at', $date->month)
                    ->count();

                $churned = Member::where('status', 'inactive')
                    ->whereYear('updated_at', $date->year)
                    ->whereMonth('updated_at', $date->month)
                    ->count();

                $data[] = [
                    'month' => $date->format('M'),
                    'active_members' => $activeMembers,
                    'new_members' => $newMembers,
                    'churned_members' => $churned
                ];
            }
        }

        return $data;
    }

    public static function getMemberActivity($period = 'this_week')
    {
        $range = self::getActivityDateRange($period);

        // For weekly periods, group by day of week
        if (in_array($period, ['this_week', 'last_week'])) {
            $logins = MemberLogin::selectRaw('DAYNAME(login_at) as day, COUNT(*) as total')
                ->whereBetween('login_at', [$range['start'], $range['end']])
                ->groupBy('day')
                ->pluck('total', 'day')
                ->toArray();

            $engagements = MemberEngagement::selectRaw('DAYNAME(engagement_at) as day, COUNT(*) as total')
                ->whereBetween('engagement_at', [$range['start'], $range['end']])
                ->groupBy('day')
                ->pluck('total', 'day')
                ->toArray();

            $daysOfWeek = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];

            $data = [];
            foreach ($daysOfWeek as $day) {
                $data[] = [
                    'day' => substr($day, 0, 3), // Mon, Tue...
                    'logins' => $logins[$day] ?? 0,
                    'engagement_score' => $engagements[$day] ?? 0
                ];
            }
        } 
        // For monthly periods, group by weeks
        else {
            $start = $range['start'];
            $end = $range['end'];
            
            $data = [];
            $weekNumber = 1;
            
            while ($start->lte($end)) {
                $weekStart = $start->copy();
                $weekEnd = $start->copy()->endOfWeek();
                
                // Don't go beyond the end date
                if ($weekEnd->gt($end)) {
                    $weekEnd = $end->copy();
                }
                
                $logins = MemberLogin::whereBetween('login_at', [$weekStart, $weekEnd])
                    ->count();
                    
                $engagements = MemberEngagement::whereBetween('engagement_at', [$weekStart, $weekEnd])
                    ->count();
                
                $data[] = [
                    'day' => 'Week ' . $weekNumber,
                    'logins' => $logins,
                    'engagement_score' => $engagements
                ];
                
                $start->addWeek();
                $weekNumber++;
            }
        }

        return $data;
    }
}