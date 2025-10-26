<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class InventoryManagementController extends Controller
{
    public function storeCategory(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string|max:255'
        ]);

        $category = Category::create($validated);

        return response()->json([
            'message' => 'Category created successfully',
            'category' => $category, // Fixed key
        ], 201);
    }

    public function updateCategory(Request $request, $id)
    {
        $category = Category::findOrFail($id); // Use findOrFail

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string|max:255'
        ]);

        $category->update($validated);

        return response()->json([
            'message' => 'Category updated successfully',
            'category' => $category, // Fixed key
        ], 200); // Fixed status
    }

    public function deleteCategory($id) // Removed unused $request
    {
        $category = Category::findOrFail($id);
        $category->delete();

        return response()->json(['message' => 'Category deleted successfully'], 200);
    }

    public function indexCategory()
    {
        $categories = Category::all();

        return response()->json([
            'message' => 'Categories retrieved successfully',
            'categories' => $categories
        ], 200);
    }

    public function storeProduct(Request $request)
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'barcode' => 'nullable|string|max:255|unique:products,barcode',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'unit' => 'required|string|max:50',
            'price' => 'required|numeric|min:0',
            'stock_quantity' => 'required|integer|min:0',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,gif|max:2048',
            'status' => 'in:active,discontinued',
        ]);

        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('product_images', 'public');
            $validated['image'] = $imagePath;
        }

        $product = Product::create($validated);

        return response()->json([
            'message' => 'Product created successfully',
            'product' => $product,
        ], 201);
    }

    public function updateProduct(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        $validated = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'barcode' => 'nullable|string|max:255|unique:products,barcode,' . $product->id,
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'unit' => 'required|string|max:50',
            'price' => 'required|numeric|min:0',
            'stock_quantity' => 'required|integer|min:0',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,gif|max:2048',
            'status' => 'in:active,discontinued',
        ]);

        if ($request->hasFile('image')) {
            if ($product->image) {
                Storage::disk('public')->delete($product->image);
            }
            $imagePath = $request->file('image')->store('product_images', 'public');
            $validated['image'] = $imagePath;
        }

        $product->update($validated);

        return response()->json([
            'message' => 'Product updated successfully',
            'product' => $product,
        ], 200);
    }


    public function destroyProduct($id)
    {
        $product = Product::findOrFail($id); // Use findOrFail

        if ($product->image) {
            Storage::disk('public')->delete($product->image);
        }

        $product->delete();

        return response()->json(['message' => 'Product deleted successfully'], 200);
    }

    public function indexProduct()
    {
        $products = Product::with('category')->get();

        $products->transform(function ($product) {
            $product->image_url = $product->image ? asset('storage/' . $product->image) : null;
            return $product;
        });

        return response()->json([
            'message' => 'Products retrieved successfully',
            'products' => $products
        ], 200);
    }

    public function showByName($name)
    {
        $products = Product::with('category')->where('name', 'like', '%' . $name . '%')->get();

        if ($products->isEmpty()) {
            return response()->json(['message' => 'No products found with that name'], 404);
        }

        $products->transform(function ($product) {
            $product->image_url = $product->image ? asset('storage/' . $product->image) : null;
            return $product;
        });

        return response()->json([
            'message' => 'Products retrieved successfully',
            'products' => $products
        ], 200);
    }

    public function productsByCategory($categoryId)
    {
        $category = Category::with('products')->findOrFail($categoryId);

        $category->products->transform(function ($product) {
            $product->image_url = $product->image ? asset('storage/' . $product->image) : null;
            return $product;
        });

        return response()->json([
            'category' => $category->name, // Fixed to use 'name'
            'products' => $category->products,
        ], 200);
    }

    public function filterProducts(Request $request)
    {
        $query = Product::with('category');

        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->has('name')) {
            $query->where('name', 'like', '%' . $request->name . '%');
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $sortField = $request->get('sort_by', 'name');
        $sortOrder = $request->get('order', 'asc');

        if (in_array($sortField, ['name', 'price', 'id']) && in_array($sortOrder, ['asc', 'desc'])) {
            $query->orderBy($sortField, $sortOrder);
        }

        $products = $query->paginate(10);

        $products->getCollection()->transform(function ($product) {
            $product->image_url = $product->image ? asset('storage/' . $product->image) : null;
            return $product;
        });

        return response()->json($products, 200);
    }

    public function decrementStock(Request $request)
    {
        $request->validate(['barcode' => 'required|string']);

        try {
            $result = DB::transaction(function () use ($request) {
                $product = Product::where('barcode', $request->barcode)
                    ->lockForUpdate()
                    ->first();

                if (!$product) {
                    return [
                        'success' => false,
                        'message' => 'Product not found. Please check the barcode.'
                    ];
                }

                if ($product->status !== 'active') {
                    return [
                        'success' => false,
                        'message' => "Product '{$product->name}' is discontinued."
                    ];
                }

                if ($product->stock_quantity <= 0) {
                    return [
                        'success' => false,
                        'message' => "Product '{$product->name}' is out of stock."
                    ];
                }

                // decrement stock
                $product->decrement('stock_quantity', 1);

                return [
                    'success' => true,
                    'message' => "Stock decremented successfully for '{$product->name}'.",
                    'product' => [
                        'name' => $product->name,
                        'remaining_stock' => $product->stock_quantity,
                    ],
                ];
            });

            return response()->json($result);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Server error: ' . $e->getMessage(),
            ], 500);
        }
    }
}
