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
       Schema::create('inventory_transactions', function (Blueprint $table) {
            $table->bigIncrements('id'); // Primary key

            $table->unsignedBigInteger('product_id'); // FK to products
            $table->enum('transaction_type', ['in', 'out', 'adjustment']); // Type of transaction
            $table->integer('quantity'); // Quantity change
            $table->enum('reference_type', ['purchase', 'sale', 'adjustment', 'return']); // What caused the change
            $table->unsignedBigInteger('reference_id')->nullable(); // Related record ID
            $table->dateTime('transaction_date'); // Date of transaction
            $table->text('notes')->nullable(); // Optional notes

            $table->unsignedBigInteger('created_by'); // Admin/user who created the record

            $table->timestamps(); // created_at and updated_at

            // Foreign key constraints
            $table->foreign('product_id')->references('id')->on('products')->onDelete('restrict');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade'); // assuming 'users' table for admins
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_transactions');
    }
};
