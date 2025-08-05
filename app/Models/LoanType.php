<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoanType extends Model
{
    use HasFactory;
    
    protected $fillable =[
        'type_name',
        'description',
        'min_amount',
        'max_amount',
        'interest_rate',
        'max_term_months',
        'collateral_required',
    ];

    public function loanApplications()
    {
        return $this->hasMany(LoanApplication::class);
    }

    public function memberExistingLoans()
    {
        return $this->hasMany(MemberExistingLoan::class);
    }
}
