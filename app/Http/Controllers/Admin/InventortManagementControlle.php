<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class InventortManagementControlle extends Controller
{
     public function storeCategory(Request $request){
        $validated = $request -> validate([
            'name'=>'required|string|max:255',
            'description'=>'required|string|max:255'
        ]);

        $categories = Category ::create([
            'name'=>$validated['name'],
            'description'=>$validated['description'],
        ]);

        return response()->json([
            'message' => 'categories successfully',
            'categories' => $categories,
        ], 201);
    }


    //update
    public function updateCategory(Request $request,$id){
        $categories = Category::find($id);
        if (!$categories) {
            return response()->json(['message' => 'Category not found'], 404);
        }

        $validated = $request -> validate([
            'name'=>'required|string|max:255',
            'description'=>'required|string|max:255'
        ]);

        $categories->update($validated);

        return response()->json([
            'message' => 'categories successfully',
            'categories' => $categories,
        ], 201);
    }


    // delete
    public function deleteCategory(Request $request,$id){
        $category = Category::find($id);

        if (!$category) {
            return response()->json(['message' => 'Category not found'], 404);
        }

        $category->delete();

        return response()->json(['message' => 'Category deleted successfully'], 200);
    }

    public function indexCategory(){
        
        $categories = Category::all();

        return response()->json([
            'message' => 'Categories retrieved successfully',
            'categories' => $categories
        ], 200);
    }

    //product
    public function storeProduct(Request $request){
         $validated = $request->validate([
        'category_id' => 'required|exists:categories,id',
        'name' => 'required|string|max:255',
        'description' => 'nullable|string',
        'unit' => 'required|string|max:50',
        'price' => 'required|numeric|min:0',
        'stock_quantity' => 'required|integer|min:0',
        'image' => 'nullable|image|mimes:jpg,jpeg,png,gif|max:2048',
        'status' => 'in:active,discontinued',
    ]);     

    // Handle image upload if exists
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
        'description' => 'nullable|string',
        'unit' => 'required|string|max:50',
        'price' => 'required|numeric|min:0',
        'stock_quantity' => 'required|integer|min:0',
        'image' => 'nullable|image|mimes:jpg,jpeg,png,gif|max:2048',
        'status' => 'in:active,discontinued',
    ]);

    // Handle image upload if exists
    if ($request->hasFile('image')) {
        // Optionally delete the old image
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
        $product = Product::find($id);

        if (!$product) {
            return response()->json(['message' => 'Product not found'], 404);
        }

        // Delete image from storage
        if ($product->image && Storage::disk('public')->exists($product->image)) {
            Storage::disk('public')->delete($product->image);
        }

        $product->delete();

        return response()->json(['message' => 'Product deleted successfully'], 200);
    }

    public function indexProduct()
    {
        $products = Product::with('category')->get();

        // Append image URL
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
            $products = Product::where('name', 'like', '%' . $name . '%')->get();

            if ($products->isEmpty()) {
                return response()->json(['message' => 'No products found with that name'], 404);
            }

            // Add image URLs
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

        return response()->json([
            'category' => $category->category_name,
            'products' => $category->products,
        ]);
    }


    //filter producs

    public function filterProducts(Request $request)
    {
        $query = Product::query();

        // Optional Filters
        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->has('name')) {
            $query->where('name', 'like', '%' . $request->name . '%');
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Sorting
        $sortField = $request->get('sort_by', 'name'); // default to 'name'
        $sortOrder = $request->get('order', 'asc');    // default to 'asc'

        if (in_array($sortField, ['name', 'price', 'id']) && in_array($sortOrder, ['asc', 'desc'])) {
            $query->orderBy($sortField, $sortOrder);
        }

        $products =$query->paginate(10);

        return response()->json($products);
    }

}
