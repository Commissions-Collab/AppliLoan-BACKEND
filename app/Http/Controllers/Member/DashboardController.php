<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\MemberExistingLoan;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function dashboardData()
    {
        $member = Auth::user()->member;

        if (!$member) {
            return response()->json([
                'success' => false,
                'message' => 'Member profile not found'
            ], 404);
        }

        $loans = $member->loans()
            ->with([
                'application.product',
                'schedules' => function ($query) {
                    $query->orderBy('due_date');
                },
                'payments'
            ])
            ->orderByDesc('created_at')
            ->get();

        $activeLoans = $loans->where('status', 'active')->count();
        $closedLoans = $loans->where('status', 'closed')->count();

        $overdueLoans = $loans->filter(function ($loan) {
            if ($loan->status !== 'active') return false;

            $nextSchedule = $loan->schedules
                ->where('status', 'unpaid')
                ->sortBy('due_date')
                ->first();

            return $nextSchedule && Carbon::parse($nextSchedule->due_date)->isPast();
        })->count();

        $recentLoans = $loans->take(5)->map(function ($loan) {
            $nextSchedules =  $loan->schedules
                ->where('status', 'unpaid')
                ->sortBy('due_date')
                ->first();

            return [
                'id' => $loan->id,
                'appliance' => $loan->application->product->name ?? 'N/A',
                'price' => number_format($loan->principal_amount, 0),
                'start_date' => $loan->release_date ? Carbon::parse($loan->release_date)->format('Y-m-d') : 'N/A',
                'due_date' => $nextSchedules ? Carbon::parse($nextSchedules->due_date)->format('Y-m-d') : 'N/A',
                'status' => $this->getLoanStatus($loan, $nextSchedules),
                'loan' => $loan->loan_number
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'member' => [
                    'name' => $member->full_name,
                    'member_number' => $member->member_number
                ],
                'summary' => [
                    'active_loans' => $activeLoans,
                    'overdue_loans' => $overdueLoans,
                    'completed_loans' => $closedLoans
                ],
                'recent_loans' => $recentLoans
            ]
        ]);
    }

    private function getLoanStatus($loan, $nextSchedule = null)
    {
        if ($loan->status === 'closed') {
            return 'Completed';
        }

        if ($loan->status === 'defaulted') {
            return 'Defaulted';
        }

        if ($loan->status === 'active') {
            if ($nextSchedule && Carbon::parse($nextSchedule->due_date)->isPast()) {
                return 'Overdue';
            }
            return 'Active';
        }

        return ucfirst($loan->status);
    }
}
