<?php

namespace Database\Seeders;

use App\Models\Loan;
use App\Models\LoanApplication;
use App\Models\LoanSchedule;
use App\Models\LoanPayment;
use App\Models\User;
use App\Models\Product;
use App\Models\LoanType;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class LoanDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get or create necessary data
        $loanType = LoanType::firstOrCreate(['type_name' => 'Appliance Loan'], [
            'type_name' => 'Appliance Loan',
            'description' => 'Loan for purchasing appliances',
            'min_amount' => 5000,
            'max_amount' => 50000,
            'interest_rate' => 5.0,
            'max_term_months' => 24
        ]);
        
        // Create some products if they don't exist
        $products = [
            ['name' => 'Refrigerator', 'price' => 25000, 'category_id' => 1, 'unit' => 'piece', 'status' => 'active', 'stock_quantity' => 10],
            ['name' => 'Washing Machine', 'price' => 18000, 'category_id' => 1, 'unit' => 'piece', 'status' => 'active', 'stock_quantity' => 15],
            ['name' => 'Television', 'price' => 15000, 'category_id' => 1, 'unit' => 'piece', 'status' => 'active', 'stock_quantity' => 20],
            ['name' => 'Air Conditioner', 'price' => 30000, 'category_id' => 1, 'unit' => 'piece', 'status' => 'active', 'stock_quantity' => 8],
            ['name' => 'Microwave Oven', 'price' => 8000, 'category_id' => 1, 'unit' => 'piece', 'status' => 'active', 'stock_quantity' => 25],
        ];

        foreach ($products as $productData) {
            Product::firstOrCreate(['name' => $productData['name']], $productData);
        }

        // Get users who are members
        $memberUsers = User::where('is_member', true)->get();
        
        if ($memberUsers->isEmpty()) {
            $this->command->info('No member users found. Creating sample loan data requires members.');
            return;
        }

        $appliances = Product::all();

        // Create sample loan applications and loans
        foreach ($memberUsers->take(3) as $user) {
            $appliance = $appliances->random();
            
            // Create loan application
            $application = LoanApplication::create([
                'user_id' => $user->id,
                'loan_type_id' => $loanType->id,
                'product_id' => $appliance->id,
                'user_name' => $user->name,
                'applied_amount' => $appliance->price,
                'term_months' => 12,
                'phone' => '09123456789',
                'age' => 30,
                'address' => 'Sample Address, City',
                'monthly_income' => 25000,
                'application_date' => Carbon::now()->subDays(rand(30, 90)),
                'status' => 'approved'
            ]);

            // Create approved loan
            $loan = Loan::create([
                'loan_application_id' => $application->id,
                'loan_number' => 'LOAN-' . str_pad($application->id, 6, '0', STR_PAD_LEFT),
                'principal_amount' => $appliance->price,
                'monthly_payment' => round($appliance->price / 12, 2),
                'interest_rate' => 5.0,
                'term_months' => 12,
                'application_date' => $application->application_date,
                'approval_date' => $application->application_date->addDays(3),
                'release_date' => $application->application_date->addDays(7),
                'maturity_date' => $application->application_date->addMonths(12),
                'approved_by' => User::where('role', 'admin')->first()?->id ?? 1,
                'purpose' => 'Appliance Purchase',
                'status' => rand(1, 3) === 1 ? 'closed' : 'active' // Some completed loans
            ]);

            // Create payment schedule
            $monthlyPayment = $loan->monthly_payment;
            $releaseDate = Carbon::parse($loan->release_date);
            
            for ($i = 1; $i <= $loan->term_months; $i++) {
                $dueDate = $releaseDate->copy()->addMonths($i);
                $principalAmount = round($loan->principal_amount / $loan->term_months, 2);
                $interestAmount = round($monthlyPayment - $principalAmount, 2);

                LoanSchedule::create([
                    'loan_id' => $loan->id,
                    'due_date' => $dueDate,
                    'amount_due' => $monthlyPayment,
                    'principal_amount' => $principalAmount,
                    'interest_amount' => $interestAmount,
                    'status' => 'unpaid'
                ]);
            }

            // Create some payments for the loan
            $schedules = $loan->schedules()->orderBy('due_date')->get();
            $paidSchedules = rand(2, min(8, $schedules->count()));
            
            $remainingBalance = $loan->principal_amount;
            
            for ($j = 0; $j < $paidSchedules; $j++) {
                $schedule = $schedules[$j];
                $remainingBalance -= $schedule->principal_amount;
                
                LoanPayment::create([
                    'loan_id' => $loan->id,
                    'schedule_id' => $schedule->id,
                    'payment_date' => $schedule->due_date->subDays(rand(0, 5)),
                    'amount_paid' => $schedule->amount_due,
                    'remaining_balance' => max(0, $remainingBalance),
                    'payment_method' => collect(['cash', 'bank_transfer', 'check'])->random(),
                    'receipt_number' => 'RCP-' . str_pad(rand(1000, 9999), 4, '0', STR_PAD_LEFT),
                    'received_by' => User::where('role', 'clerk')->first()?->id ?? 1,
                    'notes' => 'Monthly payment',
                    'status' => 'approved'
                ]);

                // Mark schedule as paid
                $schedule->update(['status' => 'paid']);
            }

            // If loan is closed, mark all schedules as paid
            if ($loan->status === 'closed') {
                $loan->schedules()->update(['status' => 'paid']);
                
                // Create payments for remaining schedules
                $unpaidSchedules = $schedules->slice($paidSchedules);
                foreach ($unpaidSchedules as $schedule) {
                    $remainingBalance -= $schedule->principal_amount;
                    
                    LoanPayment::create([
                        'loan_id' => $loan->id,
                        'schedule_id' => $schedule->id,
                        'payment_date' => $schedule->due_date->subDays(rand(0, 5)),
                        'amount_paid' => $schedule->amount_due,
                        'remaining_balance' => max(0, $remainingBalance),
                        'payment_method' => collect(['cash', 'bank_transfer', 'check'])->random(),
                        'receipt_number' => 'RCP-' . str_pad(rand(1000, 9999), 4, '0', STR_PAD_LEFT),
                        'received_by' => User::where('role', 'clerk')->first()?->id ?? 1,
                        'notes' => 'Monthly payment',
                        'status' => 'approved'
                    ]);
                }
            }
        }

        $this->command->info('Loan data seeded successfully!');
    }
}
