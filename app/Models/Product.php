<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
   protected $fillable = [
    'product_code',
    'product_name',
    'brand',
    'model',
    'category_id',
    'cost_price',
    'selling_price',
    'stock_quantity',
    'reorder_level',
    'status',
   ];


public function category(){
    return $this -> hasMany (category::class);
}
}


