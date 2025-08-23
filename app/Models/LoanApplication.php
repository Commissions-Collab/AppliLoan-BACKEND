<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoanApplication extends Model
{
    use HasFactory;

    protected $fillable = [
        'member_id',
        'loan_type_id',
        'product_id',
        'applied_amount',
        'term_months',
        'application_date',
        'status',
        'processed_by',
        'purpose',
        'rejection_reason'
    ];

    protected $casts = [
        'applied_amount' => 'decimal:2',
        'application_date' => 'date',
    ];

    // Relationships
    public function member()
    {
        return $this->belongsTo(Member::class);
    }

    public function loanType()
    {
        return $this->belongsTo(LoanType::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function processedBy()
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function loan()
    {
        return $this->hasOne(Loan::class);
    }

    public function notifications()
    {
        return $this->morphMany(Notification::class, 'notifiable');
    }
}
