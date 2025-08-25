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
        Schema::create('dividend_settings', function (Blueprint $table) {
            $table->id();
            $table->integer('year');
            $table->integer('quarter')->nullable(); // null means annual setting
            $table->decimal('total_dividend_pool', 15, 2);
            $table->enum('distribution_method', ['percentage_based', 'proportional', 'equal', 'hybrid']);
            $table->decimal('dividend_rate', 8, 6)->default(0.05); // e.g., 0.05 for 5%
            $table->boolean('is_approved')->default(false);
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->timestamp('approval_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['year', 'quarter']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dividend_settings');
    }
};
