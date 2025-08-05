<?php

namespace Database\Seeders;

use App\Models\InventoryTransaction;
use App\Models\Member;
use App\Models\Product;
use App\Models\SaleItem;
use App\Models\Sales;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class SaleSeeder extends Seeder
{
    public function run(): void
    {
        $members = Member::all();
        $products = Product::all();
        $cashiers = User::where('role', 'loan_clerk')->get();

        foreach ($members->take(40) as $member) {
            $salesCount = rand(1, 8); // 1-8 sales per member

            for ($i = 0; $i < $salesCount; $i++) {
                $saleDate = now()->subDays(rand(1, 90));
                $cashier = $cashiers->random();

                $sale = Sales::create([
                    'sale_number' => strtoupper('SALE-' . Str::random(8)),
                    'member_id' => $member->id,
                    'sale_date' => $saleDate,
                    'subtotal' => 0, // To be calculated
                    'discount_amount' => 0,
                    'tax_amount' => 0,
                    'total_amount' => 0,
                    'payment_method' => fake()->randomElement(['cash', 'credit', 'installment']),
                    'payment_status' => fake()->randomElement(['paid', 'partial', 'unpaid']),
                    'cashier_id' => $cashier->id,
                    'notes' => fake()->optional()->sentence(5),
                ]);

                $subtotal = 0;

                $itemsCount = rand(2, 8);
                for ($j = 0; $j < $itemsCount; $j++) {
                    $product = $products->random();
                    $quantity = rand(1, 5);
                    $unitPrice = $product->price;
                    $itemSubtotal = $unitPrice * $quantity;
                    $discountRate = rand(0, 20);

                    $lineTotal = round($quantity * $unitPrice * ((100 - $discountRate) / 100), 2);
                    SaleItem::create([
                        'sale_id' => $sale->id,
                        'product_id' => $product->id,
                        'quantity' => $quantity,
                        'unit_price' => $unitPrice,
                        'discount_rate' => $discountRate,
                        'line_total' => $lineTotal,
                    ]);

                    InventoryTransaction::create([
                        'product_id' => $product->id,
                        'transaction_type' => 'out',
                        'quantity' => $quantity,
                        'reference_type' => 'sale',
                        'reference_id' => $sale->id,
                        'transaction_date' => $saleDate,
                        'notes' => 'Auto-generated from SaleSeeder',
                        'created_by' => $cashier->id,
                    ]);

                    // Update product stock
                    $product->decrement('stock_quantity', $quantity);
                    $subtotal += $itemSubtotal;
                }

                // Calculate discount and tax
                $discount = round($subtotal * 0.1, 2); // 10% discount
                $tax = round(($subtotal - $discount) * 0.12, 2); // 12% VAT
                $total = round($subtotal - $discount + $tax, 2);

                $sale->update([
                    'subtotal' => $subtotal,
                    'discount_amount' => $discount,
                    'tax_amount' => $tax,
                    'total_amount' => $total,
                ]);
            }
        }
    }
}
