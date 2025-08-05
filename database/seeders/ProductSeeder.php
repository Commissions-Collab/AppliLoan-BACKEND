<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\InventoryTransaction;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = Category::all();
        $admin = User::first(); // or assign specific user

        $productsByCategory = [
            'Rice & Grains' => [
                ['name' => 'White Rice 25kg', 'unit' => 'sack', 'price' => 1200, 'stock' => 50],
                ['name' => 'Brown Rice 25kg', 'unit' => 'sack', 'price' => 1350, 'stock' => 30],
                ['name' => 'Jasmine Rice 5kg', 'unit' => 'pack', 'price' => 280, 'stock' => 25],
                ['name' => 'Corn Grits 1kg', 'unit' => 'pack', 'price' => 65, 'stock' => 40],
            ],
            'Canned Goods' => [
                ['name' => 'Corned Beef 150g', 'unit' => 'can', 'price' => 45, 'stock' => 200],
                ['name' => 'Sardines 155g', 'unit' => 'can', 'price' => 22, 'stock' => 300],
                ['name' => 'Tuna Flakes 180g', 'unit' => 'can', 'price' => 35, 'stock' => 150],
                ['name' => 'Tomato Sauce 250g', 'unit' => 'can', 'price' => 18, 'stock' => 100],
                ['name' => 'Vienna Sausage 130g', 'unit' => 'can', 'price' => 28, 'stock' => 180],
            ],
            'Beverages' => [
                ['name' => 'Instant Coffee 100g', 'unit' => 'pack', 'price' => 85, 'stock' => 80],
                ['name' => 'Powdered Milk 900g', 'unit' => 'pack', 'price' => 320, 'stock' => 45],
                ['name' => 'Soft Drink 1.5L', 'unit' => 'bottle', 'price' => 65, 'stock' => 120],
                ['name' => 'Bottled Water 500ml', 'unit' => 'bottle', 'price' => 15, 'stock' => 200],
                ['name' => 'Energy Drink 250ml', 'unit' => 'can', 'price' => 45, 'stock' => 100],
            ],
            'Personal Care' => [
                ['name' => 'Bath Soap 135g', 'unit' => 'bar', 'price' => 25, 'stock' => 150],
                ['name' => 'Shampoo 180ml', 'unit' => 'bottle', 'price' => 55, 'stock' => 80],
                ['name' => 'Toothpaste 100g', 'unit' => 'tube', 'price' => 45, 'stock' => 120],
                ['name' => 'Deodorant 50ml', 'unit' => 'bottle', 'price' => 85, 'stock' => 60],
            ],
            'Household Items' => [
                ['name' => 'Laundry Soap 200g', 'unit' => 'bar', 'price' => 25, 'stock' => 200],
                ['name' => 'Dishwashing Liquid 500ml', 'unit' => 'bottle', 'price' => 45, 'stock' => 100],
                ['name' => 'Fabric Conditioner 1L', 'unit' => 'bottle', 'price' => 65, 'stock' => 75],
                ['name' => 'All-Purpose Cleaner 500ml', 'unit' => 'bottle', 'price' => 55, 'stock' => 85],
            ],
        ];

        foreach ($categories as $category) {
            if (isset($productsByCategory[$category->name])) {
                foreach ($productsByCategory[$category->name] as $productData) {
                    $product = Product::create([
                        'category_id' => $category->id,
                        'name' => $productData['name'],
                        'description' => 'High quality ' . strtolower($productData['name']),
                        'unit' => $productData['unit'],
                        'price' => $productData['price'],
                        'stock_quantity' => $productData['stock'],
                        'status' => 'active',
                        'image' => 'https://placehold.co/600x400',
                    ]);

                    InventoryTransaction::create([
                        'product_id' => $product->id,
                        'transaction_type' => 'in',
                        'quantity' => $productData['stock'],
                        'reference_type' => 'adjustment',
                        'reference_id' => null,
                        'transaction_date' => Carbon::now(),
                        'notes' => 'Initial stock from seeder',
                        'created_by' => $admin->id,
                    ]);
                }
            } else {
                Product::factory()
                    ->count(rand(3, 8))
                    ->create(['category_id' => $category->id])
                    ->each(function ($product) use ($admin) {
                        InventoryTransaction::create([
                            'product_id' => $product->id,
                            'transaction_type' => 'in',
                            'quantity' => $product->stock_quantity,
                            'reference_type' => 'adjustment',
                            'reference_id' => null,
                            'transaction_date' => Carbon::now(),
                            'notes' => 'Initial stock from factory',
                            'created_by' => $admin->id,
                        ]);
                    });
            }
        }
    }
}
