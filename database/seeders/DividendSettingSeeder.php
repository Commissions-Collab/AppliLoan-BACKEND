<?php

namespace Database\Seeders;

use App\Models\DividendSetting;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DividendSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Ensure the table is empty before seeding
        DividendSetting::truncate();

        // --- Create Specific Scenarios ---

        // An approved annual setting for last year
        DividendSetting::factory()->create([
            'year' => 2024,
            'quarter' => null, // Annual
            'is_approved' => true,
            'distribution_method' => 'percentage_based',
            'dividend_rate' => 0.08, // 8%
            'total_dividend_pool' => 250000,
        ]);

        // An unapproved annual setting for the current year
        DividendSetting::factory()->create([
            'year' => 2025,
            'quarter' => null, // Annual
            'is_approved' => false,
        ]);

        // Create quarterly settings for the current year
        for ($q = 1; $q <= 4; $q++) {
            DividendSetting::factory()->create([
                'year' => 2025,
                'quarter' => $q,
                'is_approved' => $q < 2, // Approve Q1, leave others pending
            ]);
        }

        // --- Create Additional Random Data ---

        // Create 5 more specific settings for variety (past years with specific quarters to avoid conflicts)
        $pastSettings = [
            ['year' => 2022, 'quarter' => 1],
            ['year' => 2022, 'quarter' => 2],
            ['year' => 2023, 'quarter' => 1],
            ['year' => 2023, 'quarter' => null], // Annual
            ['year' => 2021, 'quarter' => 4],
        ];

        foreach ($pastSettings as $setting) {
            DividendSetting::factory()->create($setting);
        }
    }
}
