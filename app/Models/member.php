<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function account()
    {
        return $this->hasOne(MemberAccount::class);
    }

    public function loanApplications()
    {
        return $this->hasMany(LoanApplication::class);
    }

    public function loans()
    {
        return $this->hasManyThrough(Loan::class, LoanApplication::class);
    }

    // Accessors
    public function getTotalIncomeAttribute()
    {
        return $this->monthly_income + $this->other_income;
    }
}
