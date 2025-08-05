<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
        'status',
    ];

    public function application()
    {
        return $this->belongsTo(LoanApplication::class, 'loan_application_id');
    }

    public function schedules()
    {
        return $this->hasMany(LoanSchedule::class);
    }

    public function payments()
    {
        return $this->hasMany(LoanPayment::class);
    }

    public function collaterals()
    {
        return $this->hasMany(LoanCollateral::class);
    }

    public function penalties()
    {
        return $this->hasMany(LoanPenalty::class);
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
