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
        Schema::create('loan_types', function (Blueprint $table) {
            $table->id();
            $table->string('type_name');
            $table->string('description');
            $table->decimal('min_amount')->default(0.00);
            $table->decimal('max_amount')->default(0.00);
            $table->decimal('interest_ratet');
            $table->integer('max_term_months')->default(12);
            $table->boolean('collateral_required')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('_loan_types');
    }
};
