<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'name',
        'description',
        'unit',
        'price',
        'stock_quantity',
        'image',
        'status'
    ];

    protected $casts = [
        'price' => 'decimal:2',
    ];

    // Relationships
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function loanApplications()
    {
        return $this->hasMany(LoanApplication::class);
    }
}
