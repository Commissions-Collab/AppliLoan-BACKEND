<?php

namespace App\Http\Controllers\Clerk;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class InventoryScannerController extends Controller
{
    // Categories
    public function storeCategory(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string|max:255'
        ]);

        $category = Category::create($validated);

        return response()->json([
            'message' => 'Category created successfully',
            'category' => $category,
        ], 201);
    }

    public function updateCategory(Request $request, $id)
    {
        $category = Category::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string|max:255'
        ]);

        $category->update($validated);

        return response()->json([
            'message' => 'Category updated successfully',
            'category' => $category,
        ], 200);
    }

    public function deleteCategory($id)
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

    // Products
    public function storeProduct(Request $request)
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'name' => 'required|string|max:255',
            'barcode' => 'required|string|unique:products,barcode',
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
            'name' => 'required|string|max:255',
            'barcode' => 'required|string|unique:products,barcode,' . $id,
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
        $product = Product::findOrFail($id);

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

    public function showByBarcode($barcode)
    {
        $product = Product::with('category')->where('barcode', $barcode)->firstOrFail();
        $product->image_url = $product->image ? asset('storage/' . $product->image) : null;

        return response()->json([
            'message' => 'Product retrieved successfully',
            'product' => $product
        ], 200);
    }

    public function updateStock($id, Request $request)
    {
        $product = Product::findOrFail($id);
        $validated = $request->validate([
            'stock_quantity' => 'required|integer|min:0',
        ]);

        $product->update($validated);

        return response()->json([
            'message' => 'Stock updated successfully',
            'product' => $product
        ], 200);
    }

    // Additional filters (similar to admin)
    public function filterProducts(Request $request)
    {
        $query = Product::query();

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

        return response()->json($products);
    }
}
