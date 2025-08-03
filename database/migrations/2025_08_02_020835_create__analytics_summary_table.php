<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('analytics_summary', function (Blueprint $table) {
            $table->bigIncrements('id'); // Primary key
            $table->date('report_date'); // Report date
            $table->enum('period_type', ['daily', 'weekly', 'monthly', 'yearly']); // Period type

            $table->decimal('total_sales', 12, 2)->default(0.00); // Total sales amount
            $table->decimal('total_loans', 12, 2)->default(0.00); // Total loans disbursed
            $table->decimal('total_payments', 12, 2)->default(0.00); // Total loan payments

            $table->integer('active_members')->default(0); // Number of active members
            $table->integer('new_members')->default(0); // New members added

            $table->decimal('inventory_value', 12, 2)->default(0.00); // Total inventory value

            $table->timestamp('generated_at')->useCurrent(); // Default to CURRENT_TIMESTAMP
            $table->timestamps(); // created_at, updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('_analytics_summary');
    }
};
