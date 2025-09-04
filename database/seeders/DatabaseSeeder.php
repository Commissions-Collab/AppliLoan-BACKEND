<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Loan;
use App\Models\LoanApplication;
use App\Models\LoanType;
use App\Models\Member;
use App\Models\MemberAccount;
use App\Models\Product;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run()
    {
        // Create admin users
        $admin = User::create([
            'email' => 'admin@coop.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'is_verified' => true,
        ]);

        $clerk = User::create([
            'email' => 'clerk@coop.com',
            'password' => Hash::make('password'),
            'role' => 'loan_clerk',
            'is_verified' => true,
        ]);

        // Create categories
        $categories = [
            ['name' => 'Kitchen Appliances', 'description' => 'Refrigerators, microwaves, rice cookers'],
            ['name' => 'Laundry Equipment', 'description' => 'Washing machines, dryers'],
            ['name' => 'Electronics', 'description' => 'Televisions, audio systems'],
            ['name' => 'Climate Control', 'description' => 'Air conditioners, fans'],
            ['name' => 'Small Appliances', 'description' => 'Blenders, coffee makers, toasters'],
        ];

        foreach ($categories as $categoryData) {
            $category = Category::create($categoryData);

            // Create 5-8 products per category
            Product::factory(rand(5, 8))->create(['category_id' => $category->id]);
        }

        // Create loan types
        $loanTypes = [
            [
                'type_name' => 'Appliance Loan',
                'description' => 'Loan for purchasing home and kitchen appliances',
                'min_amount' => 5000.00,
                'max_amount' => 100000.00,
                'interest_rate' => 12.00,
                'max_term_months' => 24,
                'collateral_required' => false,
            ],
            [
                'type_name' => 'Personal Loan',
                'description' => 'General purpose personal loan',
                'min_amount' => 10000.00,
                'max_amount' => 200000.00,
                'interest_rate' => 15.00,
                'max_term_months' => 36,
                'collateral_required' => false,
            ],
            [
                'type_name' => 'Emergency Loan',
                'description' => 'Quick approval loan for emergency expenses',
                'min_amount' => 5000.00,
                'max_amount' => 50000.00,
                'interest_rate' => 18.00,
                'max_term_months' => 12,
                'collateral_required' => false,
            ],
        ];

        foreach ($loanTypes as $loanTypeData) {
            LoanType::create($loanTypeData);
        }

        // Create test members with complete data
        $testMembers = [
            [
                'email' => 'mark@member.com',
                'full_name' => 'Mark Johnson',
                'member_number' => 'MEM-0001',
                'phone_number' => '+639123456789',
                'monthly_income' => 50000,
            ],
            [
                'email' => 'jane@member.com',
                'full_name' => 'Jane Smith',
                'member_number' => 'MEM-0002',
                'phone_number' => '+639234567890',
                'monthly_income' => 45000,
            ],
            [
                'email' => 'john@member.com',
                'full_name' => 'John Doe',
                'member_number' => 'MEM-0003',
                'phone_number' => '+639345678901',
                'monthly_income' => 60000,
            ],
        ];

        foreach ($testMembers as $testMemberData) {
            // Create user
            $user = User::create([
                'email' => $testMemberData['email'],
                'password' => Hash::make('password'),
                'role' => 'member',
                'is_verified' => true,
            ]);

            // Create member
            $member = Member::create([
                'user_id' => $user->id,
                'member_number' => $testMemberData['member_number'],
                'full_name' => $testMemberData['full_name'],
                'phone_number' => $testMemberData['phone_number'],
                'address' => fake()->streetAddress(), // merged street, city, province, postal_code
                'date_of_birth' => fake()->dateTimeBetween('-45 years', '-25 years')->format('Y-m-d'),
                'place_of_birth' => fake()->city(),
                'age' => fake()->numberBetween(25, 45),
                'civil_status' => fake()->randomElement(['single', 'married', 'widowed', 'separated']),
                'religion' => fake()->randomElement(['Catholic', 'Christian', 'Muslim', 'Other']),
                'tin_number' => fake()->numerify('#########'),
                'employer' => fake()->company(),
                'position' => fake()->jobTitle(),
                'monthly_income' => $testMemberData['monthly_income'],
                'other_income' => fake()->word(),
                'dependents' => fake()->numberBetween(1, 3),
                'share_capital' => 20.00,
                'fixed_deposit' => fake()->randomFloat(2, 0, 10000),
                'seminar_date' => now()->format('Y-m-d'),
                'venue' => 'Barangay Hall',
                'status' => 'pending', // changed from active to match migration enum
                'brgy_clearance' => null,
                'birth_cert' => null,
                'certificate_of_employment' => null,
                'applicant_photo' => null,
                'valid_id' => null,
            ]);

            // Create member account
            MemberAccount::create([
                'member_id' => $member->id,
                'original_share_capital' => 5000.00,
                'current_share_capital' => 5000.00,
                'savings_balance' => fake()->numberBetween(1000, 50000),
                'regular_loan_balance' => 0.00,
                'petty_cash_balance' => fake()->numberBetween(0, 5000),
            ]);
        }

        // Create additional random members
        User::factory(20)
            ->member()
            ->create()
            ->each(function ($user) {
                $member = Member::factory()->create(['user_id' => $user->id]);
                MemberAccount::factory()->create(['member_id' => $member->id]);
            });

        // Create sample loan applications and loans
        $applianceLoanType = LoanType::where('type_name', 'Appliance Loan')->first();
        $members = Member::all();
        $products = Product::take(10)->get();

        foreach ($members->take(15) as $member) {
            // Create 1-3 loan applications per member
            $applicationCount = rand(1, 3);

            for ($i = 0; $i < $applicationCount; $i++) {
                $product = $products->random();
                $appliedAmount = min($product->price, rand(10000, 80000));

                $application = LoanApplication::create([
                    'member_id' => $member->id,
                    'loan_type_id' => $applianceLoanType->id,
                    'product_id' => $product->id,
                    'applied_amount' => $appliedAmount,
                    'term_months' => [12, 18, 24][array_rand([12, 18, 24])],
                    'application_date' => fake()->dateTimeBetween('-1 year', 'now'),
                    'status' => ['pending', 'approved', 'rejected'][array_rand(['pending', 'approved', 'rejected'])],
                    'processed_by' => [$admin->id, $clerk->id][array_rand([$admin->id, $clerk->id])],
                    'purpose' => 'Purchase of ' . $product->name,
                ]);

                // Create loan if approved
                if ($application->status === 'approved') {
                    $interestRate = 12.00;
                    $monthlyPayment = ($appliedAmount * (1 + ($interestRate / 100))) / $application->term_months;

                    $approvalDate = fake()->dateTimeBetween($application->application_date, 'now');
                    $releaseDate = fake()->dateTimeBetween($approvalDate, $approvalDate->format('Y-m-d') . ' +15 days');
                    $maturityDate = (clone $releaseDate)->modify("+{$application->term_months} months");

                    $loan = Loan::create([
                        'loan_application_id' => $application->id,
                        'loan_number' => 'LN-' . date('Y') . '-' . str_pad(fake()->unique()->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT),
                        'principal_amount' => $appliedAmount,
                        'monthly_payment' => round($monthlyPayment, 2),
                        'interest_rate' => $interestRate,
                        'term_months' => $application->term_months,
                        'application_date' => $application->application_date,
                        'approval_date' => $approvalDate,
                        'release_date' => $releaseDate,
                        'maturity_date' => $maturityDate,
                        'approved_by' => $application->processed_by,
                        'purpose' => $application->purpose,
                        'status' => ['active', 'closed'][array_rand(['active', 'closed'])],
                    ]);

                    // Create payment schedules
                    $this->createLoanSchedules($loan);

                    // Create some payments
                    if ($loan->status === 'active' || rand(0, 1)) {
                        $this->createLoanPayments($loan, $admin, $clerk);
                    }
                }
            }
        }

        echo "Database seeded successfully!\n";
        echo "Admin: admin@coop.com / password\n";
        echo "Clerk: clerk@coop.com / password\n";
        echo "Members: mark@member.com, jane@member.com, john@member.com / password\n";
    }

    private function createLoanSchedules(Loan $loan)
    {
        $startDate = $loan->release_date;
        $monthlyPayment = $loan->monthly_payment;
        $interestRate = $loan->interest_rate / 100 / 12; // Monthly interest rate
        $remainingBalance = $loan->principal_amount;

        for ($month = 1; $month <= $loan->term_months; $month++) {
            $dueDate = (clone $startDate)->modify("+{$month} months");
            $interestAmount = $remainingBalance * $interestRate;
            $principalAmount = $monthlyPayment - $interestAmount;
            $remainingBalance -= $principalAmount;

            \App\Models\LoanSchedule::create([
                'loan_id' => $loan->id,
                'due_date' => $dueDate,
                'amount_due' => $monthlyPayment,
                'principal_amount' => max(0, $principalAmount),
                'interest_amount' => $interestAmount,
                'status' => 'unpaid',
            ]);
        }
    }

    private function createLoanPayments(Loan $loan, User $admin, User $clerk)
    {
        $schedules = $loan->schedules()->orderBy('due_date')->get();
        $totalPaid = 0;
        $remainingBalance = $loan->principal_amount;

        // Pay 30-80% of schedules
        $scheduleCount = $schedules->count();
        $paymentCount = rand(max(1, (int)($scheduleCount * 0.3)), (int)($scheduleCount * 0.8));

        foreach ($schedules->take($paymentCount) as $schedule) {
            $paymentDate = fake()->dateTimeBetween($schedule->due_date, $schedule->due_date->format('Y-m-d') . ' +7 days');
            $amountPaid = $schedule->amount_due;
            $totalPaid += $amountPaid;
            $remainingBalance -= $amountPaid;

            \App\Models\LoanPayment::create([
                'loan_id' => $loan->id,
                'schedule_id' => $schedule->id,
                'payment_date' => $paymentDate,
                'amount_paid' => $amountPaid,
                'remaining_balance' => max(0, $remainingBalance),
                'payment_method' => ['cash', 'check', 'bank_transfer'][array_rand(['cash', 'check', 'bank_transfer'])],
                'receipt_number' => 'OR-' . fake()->unique()->numerify('######'),
                'received_by' => [$admin->id, $clerk->id][array_rand([$admin->id, $clerk->id])],
                'notes' => fake()->optional()->sentence(),
            ]);

            // Mark schedule as paid
            $schedule->update(['status' => 'paid']);
        }

        // Update loan status if fully paid
        if ($paymentCount >= $scheduleCount) {
            $loan->update(['status' => 'closed']);
        }

        $this->call([
            NotificationSeeder::class,
            RequestSeeder::class,
            DividendSettingSeeder::class,
            LoanClerkSeeder::class
        ]);
    }
}
