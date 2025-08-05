<?php

namespace Database\Seeders;

use App\Models\Member;
use App\Models\MemberAccount;
use App\Models\MemberExistingLoan;
use App\Models\MemberExpense;
use App\Models\MemberSpouse;
use Illuminate\Database\Seeder;

class MemberSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create members with related data
        Member::factory()
            ->count(50)
            ->create()
            ->each(function ($member) {
                // 60% chance of having a spouse
                if (rand(1, 100) <= 60) {
                    MemberSpouse::factory()->create(['member_id' => $member->id]);
                }

                // Create member expenses
                MemberExpense::factory()->create(['member_id' => $member->id]);

                // Create member account
                MemberAccount::factory()->create(['member_id' => $member->id]);

                // 40% chance of having existing loans
                if (rand(1, 100) <= 40) {
                    MemberExistingLoan::factory()
                        ->count(rand(1, 3))
                        ->create(['member_id' => $member->id]);
                }
            });
    }
}
