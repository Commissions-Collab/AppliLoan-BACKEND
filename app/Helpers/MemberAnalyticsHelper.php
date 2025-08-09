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

        if ($period === 'current_year') {
            $startCurrent = $now->copy()->startOfYear();
            $startPrevious = $now->copy()->subYear()->startOfYear();
            $endPrevious = $now->copy()->subYear()->endOfYear();
        } else {
            $months = $period === 'last_6_months' ? 6 : 12;
            $startCurrent = $now->copy()->subMonths($months - 1)->startOfMonth();
            $startPrevious = $now->copy()->subMonths($months * 2 - 1)->startOfMonth();
            $endPrevious = $now->copy()->subMonths($months)->endOfMonth();
        }

        return [
            'current_start'  => $startCurrent,
            'current_end'    => $now,
            'previous_start' => $startPrevious,
            'previous_end'   => $endPrevious
        ];
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

        $current = Member::whereBetween('updated_at', [$range['current_start'], $range['current_end']])->count();
        $previous = Member::whereBetween('updated_at', [$range['previous_start'], $range['previous_end']])->count();

        $growth = $previous > 0 ? round((($current - $previous) / $previous) * 100, 1) : 0;

        return [
            'current' => $current,
            'growth' => $growth
        ];
    }

    public static function getNewMembers($period)
    {
        $range = self::getDateRange($period);

        return Member::whereBetween('updated_at', [$range['current_start'], $range['current_end']])
            ->count();
    }

    public static function getChurnRate($period)
    {
        $range = self::getDateRange($period);

        $totalMembers = Member::count();
        $inactiveMembers = Member::where('status', 'inactive')
            ->whereHas('account', function ($q) use ($range) {
                $q->whereBetween('updated_at', [$range['current_start'], $range['current_end']]);
            })
            ->count();

        $rate = $totalMembers > 0 ? round(($inactiveMembers / $totalMembers) * 100, 1) : 0;

        return [
            'rate' => $rate,
            'monthly_change' => 0 // You can compare with previous period here
        ];
    }


    public static function getMembershipGrowth($period)
    {
        $data = [];
        if ($period === 'current_year') {
            $year = Carbon::now()->year;

            for ($month = 1; $month <= 12; $month++) {
                $activeMembers = Member::whereYear('updated_at', $year)
                    ->whereMonth('updated_at', $month)
                    ->where('status', 'active')
                    ->count();

                $newMembers = Member::whereYear('updated_at', $year)
                    ->whereMonth('updated_at', $month)
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
                $date = now()->subMonths($i);

                $activeMembers = Member::whereYear('updated_at', $date->year)
                    ->whereMonth('updated_at', $date->month)
                    ->where('status', 'active')
                    ->count();

                $newMembers = Member::whereYear('updated_at', $date->year)
                    ->whereMonth('updated_at', $date->month)
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

    public static function getMemberActivity($period)
    {
        $range = self::getDateRange($period);

        $logins = MemberLogin::selectRaw('DAYNAME(login_at) as day, COUNT(*) as total')
            ->whereBetween('login_at', [$range['current_start'], $range['current_end']])
            ->groupBy('day')
            ->pluck('total', 'day');

        $engagements = MemberEngagement::selectRaw('DAYNAME(engagement_at) as day, COUNT(*) as total')
            ->whereBetween('engagement_at', [$range['current_start'], $range['current_end']])
            ->groupBy('day')
            ->pluck('total', 'day');

        $daysOfWeek = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];

        $data = [];
        foreach ($daysOfWeek as $day) {
            $data[] = [
                'day' => substr($day, 0, 3), // Mon, Tue...
                'logins' => $logins[$day] ?? 0,
                'engagement_score' => $engagements[$day] ?? 0
            ];
        }

        return $data;
    }
}
