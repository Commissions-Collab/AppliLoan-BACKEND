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
        Schema::create('loans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_application_id')->constrained('loan_applications')->onDelete('cascade');
            $table->string('loan_number')->unique();
            $table->decimal('principal_amount', 12, 2);
            $table->decimal('monthly_payment', 12, 2);
            $table->decimal('interest_rate', 5, 2);
            $table->integer('term_months');
            $table->date('application_date');
            $table->date('approval_date')->nullable();
            $table->date('release_date')->nullable();
            $table->date('maturity_date')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('purpose')->nullable();
            $table->enum('status', ['active', 'closed', 'defaulted'])->default('active');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loans');
    }
};
