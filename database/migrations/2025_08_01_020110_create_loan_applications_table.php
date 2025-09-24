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
        Schema::create('loan_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');

            //loan details
            $table->foreignId('loan_type_id')->constrained('loan_types')->onDelete('cascade');
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->string('user_name')->nullable();
            $table->decimal('applied_amount', 12, 2);
            $table->integer('term_months');

            //personal details
            $table->string('phone');
            $table->integer('age')->nullable();
            $table->string('address');
            $table->string('tin_number')->nullable();
            $table->string('employer')->nullable();
            $table->string('position')->nullable();
            $table->decimal('monthly_income', 12, 2)->nullable();
            $table->string('other_income_source')->nullable();
            $table->string('monthly_disposable_income')->nullable();
            $table->string('birth_month')->nullable();
            $table->string('place_of_birth')->nullable();
            $table->string('no_of_dependents')->nullable();

            //estimated expenses
            $table->decimal('education_expense', 12, 2)->nullable();
            $table->decimal('food_expense', 12, 2)->nullable();
            $table->decimal('house_expense', 12, 2)->nullable();
            $table->decimal('transportation_expense', 12, 2)->nullable();

            //amortization details
            $table->string('date_granted')->nullable();
            $table->decimal('monthly_installment', 12, 2)->nullable();
            $table->decimal('SMPC_regular_loan', 12, 2)->nullable();
            $table->decimal('SMPC_petty_cash_loan', 12, 2)->nullable();
            $table->decimal('total_amortization', 12, 2)->nullable();

            //required documents
            $table->string('applicant_photo')->nullable();
            $table->string('certificate_of_employment')->nullable();
            $table->string('bragy_certificate')->nullable();
            $table->string('valid_id_front')->nullable();
            $table->string('valid_id_back')->nullable();
            $table->string('birth_certificate')->nullable();

            //scheduling details
            $table->string('preferred_meeting_date')->nullable();
            $table->string('preferred_meeting_time')->nullable();

            $table->date('application_date');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loan_applications');
    }
};
