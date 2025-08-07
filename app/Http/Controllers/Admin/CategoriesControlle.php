<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;

class CategoriesControlle extends Controller
{
    public function store(Request $request){
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
    public function update(Request $request,$id){
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
    public function delete(Request $request,$id){
        $category = Category::find($id);

        if (!$category) {
            return response()->json(['message' => 'Category not found'], 404);
        }

        $category->delete();

        return response()->json(['message' => 'Category deleted successfully'], 200);
    }

    public function index(){
        
        $categories = Category::all();

        return response()->json([
            'message' => 'Categories retrieved successfully',
            'categories' => $categories
        ], 200);
    }
}
