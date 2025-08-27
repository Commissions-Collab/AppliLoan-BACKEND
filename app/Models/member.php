<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Member extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'member_number',
        'full_name',
        'phone_number',
        'street_address',
        'city',
        'province',
        'postal_code',
        'tin_number',
        'date_of_birth',
        'place_of_birth',
        'age',
        'dependents',
        'employer',
        'position',
        'monthly_income',
        'other_income',
        'monthly_disposable_income_range',
        'status'
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'monthly_income' => 'decimal:2',
        'other_income' => 'decimal:2',
    ];

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function account(): HasOne
    {
        return $this->hasOne(MemberAccount::class);
    }

    public function loanApplications(): HasMany
    {
        return $this->hasMany(LoanApplication::class);
    }

    public function loans(): HasManyThrough
    {
        return $this->hasManyThrough(Loan::class, LoanApplication::class);
    }

    public function memberLogins(): HasMany
    {
        return $this->hasMany(MemberLogin::class);
    }

    public function memberEngagements(): HasMany
    {
        return $this->hasMany(MemberEngagement::class);
    }
    // Accessors
    public function getTotalIncomeAttribute()
    {
        return $this->monthly_income + $this->other_income;
    }
}
