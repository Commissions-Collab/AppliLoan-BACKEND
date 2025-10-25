<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use App\Mail\LoanPaymentDueMail;
use App\Models\LoanSchedule;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class SendDueLoanReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * You will run this via: php artisan loans:send-due-reminders
     */
    protected $signature = 'loans:send-due-reminders';

    /**
     * The console command description.
     */
    protected $description = 'Send reminder emails to users whose loan payments are due soon';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $today = Carbon::today();
        $targetDate = $today->copy()->addDays(3); // 3 days before due

        $dueSchedules = LoanSchedule::whereDate('due_date', '<=', $targetDate)
            ->where('is_paid', false)
            ->with(['loan.user'])
            ->get();

        if ($dueSchedules->isEmpty()) {
            $this->info('No upcoming loan payments found.');
            return;
        }

        foreach ($dueSchedules as $schedule) {
            $user = $schedule->loan->user ?? null;
            $loan = $schedule->loan ?? null;
            $dueDate = Carbon::parse($schedule->due_date);
            $daysRemaining = $today->diffInDays($dueDate, false);

            if ($user && $user->email) {
                try {
                    Mail::to($user->email)->send(
                        new LoanPaymentDueMail($user, $loan, $dueDate, $daysRemaining)
                    );

                    $this->info("Reminder sent to {$user->email} for Loan #{$loan->loan_number}");
                } catch (\Exception $e) {
                    Log::error('Failed to send loan payment reminder', [
                        'email' => $user->email,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        $this->info('Loan due reminders processed successfully.');
    }
}
