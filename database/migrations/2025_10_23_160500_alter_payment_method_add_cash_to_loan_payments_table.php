<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Add 'cash' to the payment_method enum
        DB::statement("ALTER TABLE loan_payments MODIFY payment_method ENUM('gcash','check','bank_transfer','cash') NOT NULL");
    }

    public function down(): void
    {
        // Revert to original set without 'cash'
        DB::statement("ALTER TABLE loan_payments MODIFY payment_method ENUM('gcash','check','bank_transfer') NOT NULL");
    }
};
