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
            $table->date('payment_date');
            $table->decimal('amount_paid',10, 2);
            $table->decimal('principal_payment',10, 2);
            $table->decimal('interest_payment',10, 2);
            $table->decimal('penalty_payment',10, 2)->default(0.00);
            $table->decimal('remaining_balance',10, 2);
            $table->enum('payment_method',['cash', 'check', 'bank_transfer']);
            $table->string('receipt_number');
            $table->foreignId('received_by')->constrained('users')->onDelete('cascade');//admin
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
