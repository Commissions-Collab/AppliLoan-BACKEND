<?php

namespace Database\Seeders;

use App\Models\Loan;
use App\Models\LoanApplication;
use App\Models\LoanCollateral;
use App\Models\LoanPayment;
use App\Models\LoanPenalty;
use App\Models\LoanSchedule;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class LoanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $approvedApplications = LoanApplication::where('status', 'approved')->get();
        $approvers = User::whereIn('role', ['admin', 'manager'])->get();
        $cashiers = User::where('role', 'loan_clerk')->get();

        foreach ($approvedApplications as $application) {
            $loanType = $application->loanType;
            $applicationDate = Carbon::parse($application->application_date);
            $approvalDate = $applicationDate->copy()->addDays(rand(1, 15));
            $releaseDate = $approvalDate->copy()->addDays(rand(1, 7));
            $maturityDate = $releaseDate->copy()->addMonths($application->term_months);

            // Create loan
            $loan = Loan::create([
                'loan_application_id' => $application->id,
                'loan_number' => 'LN-' . str_pad($application->id, 8, '0', STR_PAD_LEFT),
                'principal_amount' => $application->applied_amount,
                'monthly_payment' => $this->calculateMonthlyPayment(
                    $application->applied_amount,
                    $loanType->interest_rate,
                    $application->term_months
                ),
                'interest_rate' => $loanType->interest_rate,
                'term_months' => $application->term_months,
                'application_date' => $applicationDate,
                'approval_date' => $approvalDate,
                'release_date' => $releaseDate,
                'maturity_date' => $maturityDate,
                'approved_by' => $approvers->random()->id,
                'purpose' => $application->item_name,
                'status' => 'active'
            ]);

            // Create loan schedules
            $this->createLoanSchedules($loan, $cashiers);

            // 60% chance of having collateral
            if (rand(1, 100) <= 60) {
                LoanCollateral::factory()
                    ->count(rand(1, 2))
                    ->create(['loan_id' => $loan->id]);
            }
        }
    }

    private function calculateMonthlyPayment($principal, $annualRate, $termMonths)
    {
        $monthlyRate = $annualRate / 100 / 12;
        if ($monthlyRate == 0) return $principal / $termMonths;

        return $principal * ($monthlyRate * pow(1 + $monthlyRate, $termMonths)) /
            (pow(1 + $monthlyRate, $termMonths) - 1);
    }

    private function createLoanSchedules($loan, $cashiers)
    {
        $releaseDate = Carbon::parse($loan->release_date);
        $monthlyPayment = $loan->monthly_payment;

        for ($i = 1; $i <= $loan->term_months; $i++) {
            $dueDate = $releaseDate->copy()->addMonths($i);

            // Determine status based on due date and randomness
            $status = 'unpaid';
            if ($dueDate->isPast()) {
                $status = rand(1, 100) <= 85 ? 'paid' : 'overdue';
            }

            $schedule = LoanSchedule::create([
                'loan_id' => $loan->id,
                'due_date' => $dueDate,
                'amount_due' => $monthlyPayment,
                'status' => $status,
            ]);

            // Create payment if status is paid
            if ($status === 'paid') {
                LoanPayment::create([
                    'loan_id' => $loan->id,
                    'schedule_id' => $schedule->id,
                    'payment_date' => $dueDate->copy()->subDays(rand(0, 5)),
                    'amount_paid' => $monthlyPayment,
                    'remaining_balance' => 0, // or calculate if partial payments are allowed
                    'payment_method' => collect(['cash', 'check', 'bank_transfer'])->random(),
                    'receipt_number' => 'RCPT-' . strtoupper(uniqid()),
                    'received_by' => $cashiers->random()->id,
                ]);
            }


            // Create penalty if overdue
            if ($status === 'overdue') {
                LoanPenalty::create([
                    'loan_id' => $loan->id,
                    'penalty_rate' => 3.00, // or match what you're calculating with
                    'penalty_amount' => $monthlyPayment * 0.03, // 3% penalty
                    'due_date' => $dueDate,
                    'penalty_date' => $dueDate->copy()->addDays(1),
                    'days_overdue' => 1, // since penalty_date is 1 day after due_date
                    'status' => rand(1, 100) <= 80 ? 'active' : 'paid',
                    'remarks' => 'Late payment penalty seeded',
                ]);
            }
        }
    }
}
