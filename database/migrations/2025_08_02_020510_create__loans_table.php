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
            
            $table->decimal('principal_amount', 12, 2);
            $table->decimal('monthly_payment', 12, 2);
            $table->decimal('interest_rate', 5, 2); // percentage, example: 5.25
            $table->integer('term_months');

            $table->date('application_date');
            $table->date('approval_date');
            $table->date('release_date');
            $table->date('maturity_date'); // fixed here

            $table->foreignId('approved_by')->constrained('users')->onDelete('cascade'); // Admin who approved
            $table->string('purpose');
            $table->enum('status', ['pending', 'approved', 'released', 'completed', 'defaulted']);

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
