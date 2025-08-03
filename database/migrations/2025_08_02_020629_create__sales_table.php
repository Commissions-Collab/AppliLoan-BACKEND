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
        Schema::create('sales', function (Blueprint $table) {
            $table->bigIncrements('id'); // Primary key, auto-increment
            $table->string('sale_number', 20)->unique(); // Unique sale number
            $table->unsignedBigInteger('member_id')->nullable(); // FK to members table
            $table->dateTime('sale_date'); // Sale date and time

            $table->decimal('subtotal', 10, 2); // Subtotal amount
            $table->decimal('discount_amount', 10, 2)->default(0.00); // Discount applied
            $table->decimal('tax_amount', 10, 2)->default(0.00); // Tax amount
            $table->decimal('total_amount', 10, 2); // Total sale amount

            $table->enum('payment_method', ['cash', 'credit', 'installment']); // Payment method
            $table->enum('payment_status', ['paid', 'partial', 'unpaid']); // Payment status

            $table->unsignedBigInteger('cashier_id'); // FK to admins table
            $table->text('notes')->nullable(); // Optional notes

            $table->timestamps(); // created_at and updated_at

           
            $table->foreign('member_id')->references('id')->on('members')->nullOnDelete();
            $table->foreign('cashier_id')->references('id')->on('users')->onDelete('cascade'); // assuming 'users' table for admins
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};
