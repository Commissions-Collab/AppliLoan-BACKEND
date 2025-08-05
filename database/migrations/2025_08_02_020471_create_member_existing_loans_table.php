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
        Schema::create('member_existing_loans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained('members')->onDelete('cascade');
            $table->foreignId('loan_type_id')->constrained('loan_types')->onDelete('cascade');
            $table->string('creditor_name')->nullable();
            $table->date('date_granted');
            $table->decimal('original_amount', 12, 2);
            $table->decimal('outstanding_balance', 12, 2);
            $table->decimal('monthly_installment', 12, 2);
            $table->enum('status', ['active', 'paid', 'defaulted'])->default('active');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('member_existing_loans');
    }
};
