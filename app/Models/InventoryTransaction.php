<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryTransaction extends Model
{
    use HasFactory;
    
    protected $table = 'inventory_transactions';

    protected $fillable = [
        'product_id',
        'transaction_type',
        'quantity',
        'reference_type',
        'reference_id',
        'transaction_date',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'transaction_date' => 'datetime',
    ];

    /**
     * Get the product related to this transaction.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the admin/user who created this transaction.
     */
    public function creator(): BelongsTo                 
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
