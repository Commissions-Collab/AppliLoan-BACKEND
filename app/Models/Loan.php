<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

class Loan extends Model
{
    use HasFactory;

    protected $fillable = [
        'loan_application_id',
        'loan_number',
        'principal_amount',
        'monthly_payment',
        'interest_rate',
        'term_months',
        'application_date',
        'approval_date',
        'release_date',
        'maturity_date',
        'approved_by',
        'purpose',
        'status'
    ];

    protected $casts = [
        'principal_amount' => 'decimal:2',
        'monthly_payment' => 'decimal:2',
        'interest_rate' => 'decimal:2',
        'application_date' => 'date',
        'approval_date' => 'date',
        'release_date' => 'date',
        'maturity_date' => 'date',
    ];

    // Relationships
    public function application(): BelongsTo
    {
        return $this->belongsTo(LoanApplication::class, 'loan_application_id');
    }

    public function member(): HasOneThrough
    {
        return $this->hasOneThrough(
            Member::class, 
            LoanApplication::class, 
            'id',           // Foreign key on LoanApplication table (loan_application_id)
            'user_id',      // Foreign key on Member table  
            'loan_application_id', // Local key on Loan table
            'user_id'       // Local key on LoanApplication table
        );
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(LoanSchedule::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(LoanPayment::class);
    }

    // Accessors
    public function getTotalPaidAttribute()
    {
        return $this->payments()->sum('amount_paid');
    }

    public function getRemainingBalanceAttribute()
    {
        return $this->principal_amount - $this->total_paid;
    }

    public function getProgressPercentageAttribute()
    {
        if ($this->principal_amount == 0) return 0;
        return round(($this->total_paid / $this->principal_amount) * 100, 2);
    }

    public function getNextDueDateAttribute()
    {
        return $this->schedules()
            ->where('status', 'unpaid')
            ->orderBy('due_date')
            ->first()?->due_date;
    }
}
