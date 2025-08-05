<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Member extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'user_id',
        'full_name',
        'phone_number',
        'street_address',
        'city',
        'province',
        'postal_code',
        'member_number',
        'tin_number',
        'date_of_birth',
        'place_of_birth',
        'age',
        'dependants',
        'employer',
        'position',
        'monthly_income',
        'other_income',
        'monthly_disposable_income_range',
        'status'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function spouse()
    {
        return $this->hasOne(MemberSpouse::class);
    }

    public function loanApplications()
    {
        return $this->hasMany(LoanApplication::class);
    }

    public function existingLoans()
    {
        return $this->hasMany(MemberExistingLoan::class);
    }

    public function expenses()
    {
        return $this->hasOne(MemberExpense::class);
    }

    public function account()
    {
        return $this->hasOne(MemberAccount::class);
    }

    public function sales()
    {
        return $this->hasMany(Sales::class);
    }
}
