<?php

namespace Database\Seeders;

use App\Models\LoanClerk;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class LoanClerkSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        LoanClerk::factory()->count(30)->create();
    }
}
