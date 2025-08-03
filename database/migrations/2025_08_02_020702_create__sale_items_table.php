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
        Schema::create('sale_items', function (Blueprint $table) {
            $table->bigIncrements('id'); // Primary key
            $table->unsignedBigInteger('sale_id'); // Foreign key to sales
            $table->unsignedBigInteger('product_id'); // Foreign key to products
            $table->integer('quantity'); // Quantity sold
            $table->decimal('unit_price', 10, 2); // Price per unit
            $table->decimal('discount_rate', 5, 2)->default(0.00); // Discount rate (percentage)
            $table->decimal('line_total', 10, 2); // Line total amount

            $table->timestamps(); // created_at and updated_at

            // Foreign key constraints
            $table->foreign('sale_id')->references('id')->on('sales')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('_sale_items');
    }
};
