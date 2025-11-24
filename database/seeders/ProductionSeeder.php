<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class ProductionSeeder extends Seeder
{
    /**
     * Seed the production database with only admin and clerk accounts.
     */
    public function run(): void
    {
        // Create admin user
        $admin = User::create([
            'email' => 'admin@coop.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'is_verified' => true,
        ]);

        echo "✓ Admin account created: admin@coop.com\n";

        // Create clerk user
        $clerk = User::create([
            'email' => 'clerk@coop.com',
            'password' => Hash::make('password'),
            'role' => 'loan_clerk',
            'is_verified' => true,
        ]);

        echo "✓ Clerk account created: clerk@coop.com\n";

        echo "\n===========================================\n";
        echo "Production Database Seeded Successfully!\n";
        echo "===========================================\n";
        echo "Admin: admin@coop.com / password\n";
        echo "Clerk: clerk@coop.com / password\n";
        echo "\n⚠️  IMPORTANT: Change these passwords immediately after first login!\n";
        echo "===========================================\n";
    }
}
