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
        Schema::create('member_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained('members')->onDelete('cascade');
            $table->decimal('original_share_capital', 12, 2)->default(0);
            $table->decimal('current_share_capital', 12, 2)->default(0);
            $table->decimal('savings_balance', 12, 2)->default(0);
            $table->decimal('regular_loan_balance', 12, 2)->default(0);
            $table->decimal('petty_cash_balance', 12, 2)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('member_accounts');
    }
};
