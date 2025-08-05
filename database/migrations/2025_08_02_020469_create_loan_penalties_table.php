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
        Schema::create('loan_penalties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_id')->constrained('loans')->onDelete('cascade');
            $table->decimal('penalty_rate', 5, 2)->default(2.00); // 2% per month as mentioned in form
            $table->decimal('penalty_amount', 12, 2);
            $table->date('due_date');
            $table->date('penalty_date');
            $table->integer('days_overdue');
            $table->enum('status', ['active', 'paid', 'waived'])->default('active');
            $table->text('remarks')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loan_penalties');
    }
};
