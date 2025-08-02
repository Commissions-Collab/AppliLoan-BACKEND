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
            $table->string('loan_number')->unique();
            $table->foreignId('member_id')->constrained('members')->onDelete('cascade');
            $table->foreignId('loan_type_id')->constrained('loan_types')->onDelete('cascade');
            $table->decimal('principal_amount');
            $table->decimal('monthly_payment');
            $table->decimal('interest_rate');
            $table->integer('term_months');
            $table->date('application_date');
            $table->date('approval_date');
            $table->date('release_date');
            $table->date('maturity_datee');
            $table->foreignId('approved_by')->constrained('users')->onDelete('cascade'); // admin
            $table->string('purpose');
            $table->enum('status',['pending', 'approved', 'released', 'completed', 'defaulted']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('_loans');
    }
};
