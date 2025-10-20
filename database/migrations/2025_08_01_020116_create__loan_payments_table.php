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
        Schema::create('loan_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_id')->constrained('loans')->onDelete('cascade');
            $table->foreignId('schedule_id')->nullable()->constrained('loan_schedules')->onDelete('cascade');
            $table->date('payment_date');
            $table->decimal('amount_paid', 12, 2);
            $table->decimal('remaining_balance', 10, 2);
            $table->enum('payment_method', ['gcash', 'check', 'bank_transfer']);
            $table->string('receipt_number');
            $table->foreignId('received_by')->constrained('users')->onDelete('cascade');
            $table->text('notes')->nullable();
            $table->string('payment_image')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('_loan_payments');
    }
};
