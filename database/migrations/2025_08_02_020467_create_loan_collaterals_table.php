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
        Schema::create('loan_collaterals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_id')->constrained('loans')->onDelete('cascade');
            $table->string('collateral_type'); // appliance, property, vehicle, etc.
            $table->string('description');
            $table->decimal('appraised_value', 12, 2);
            $table->string('location')->nullable();
            $table->text('additional_details')->nullable();
            $table->enum('status', ['active', 'released', 'repossessed'])->default('active');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loan_collaterals');
    }
};
