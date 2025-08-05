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
        Schema::create('member_spouses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained('members')->onDelete('cascade');
            $table->string('full_name');
            $table->string('street_address')->nullable();
            $table->string('city')->nullable();
            $table->string('province')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('tin_number')->nullable();
            $table->string('contact_number')->nullable();
            $table->date('date_of_birth');
            $table->string('place_of_birth');
            $table->integer('age');
            $table->integer('dependents')->nullable();
            $table->string('employer')->nullable();
            $table->string('position')->nullable();
            $table->decimal('monthly_income', 12, 2)->default(0.00);
            $table->decimal('other_income', 12, 2)->default(0.00);
            $table->enum('monthly_disposable_income_range', ['0-5000', '5001-10000', '10001-20000', '20001+'])->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('member_spouses');
    }
};
