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
        Schema::create('loan_clerks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('clerk_id')->unique();
            $table->string('full_name');
            $table->string('contact_number');
            $table->string('address')->nullable();
            $table->enum('gender', ['Male', 'Female']);
            $table->string('job_title')->default('Loan Clerk');
            $table->date('date_hired')->nullable();
            $table->enum('status', ['active', 'inactive', 'terminated'])->default('active');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loan_clerks');
    }
};
