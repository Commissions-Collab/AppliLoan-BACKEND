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
        Schema::create('member_expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained('members')->onDelete('cascade');
            $table->decimal('living_expenses', 12, 2)->default(0.00); // food, medicine, clothing
            $table->decimal('utilities', 12, 2)->default(0.00); // light, rent, water, telephone
            $table->decimal('educational_expenses', 12, 2)->default(0.00);
            $table->decimal('transportation_expenses', 12, 2)->default(0.00);
            $table->decimal('total_monthly_expenses', 12, 2)->default(0.00);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('member_expenses');
    }
};
