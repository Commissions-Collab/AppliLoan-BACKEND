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
        Schema::create('members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('member_number')->unique();
            $table->string('full_name');
            $table->string('phone_number');
            $table->string('address')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('place_of_birth')->nullable();
            $table->integer('age')->nullable(); 
            $table->enum('civil_status', ['single', 'married', 'widowed', 'separated'])->nullable();
            $table->string('religion')->nullable();
            $table->integer('tin_number')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->timestamps();

            //employment details
            $table->string('employer')->nullable();
            $table->string('position')->nullable();
            $table->decimal('monthly_income', 12, 2)->default(0.00);
            $table->string('other_income')->nullable();



            // financial details
            $table->decimal('share_capital', 12, 2)->default(20.00);
            $table->decimal('fixed_deposit', 12, 2)->default(0.00);

            //child details
            $table->integer('number_of_children')->nullable();

            // spouse details
            $table->string('spouse_name')->nullable();
            $table->string('spouse_employer')->nullable();
            $table->decimal('spouse_monthly_income', 12, 2)->nullable();
            $table->string('spouse_birth_day')->nullable();
            

            // seminar details
            $table->string('seminar_date')->nullable()  ;
            $table->string('venue')->nullable();

            // required documents
            $table->string('brgy_clearance')->nullable();
            $table->string('birth_cert')->nullable();
            $table->string('certificate_of_employment')->nullable();
            $table->string('applicant_photo')->nullable();
            $table->string('valid_id_front')->nullable();
            $table->string('valid_id_back')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('members');
    }
};
