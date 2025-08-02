<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Loan extends Model
{
    protected $fillable = [
        'loan_number',
        'member_id',
        'loan_type_id',
        'principal_amount',
        'interest_rate',
        'term_months',
        'monthly_payment',
        'application_date',
        'approval_date',
        'release_date',
        'maturity_date',
        'status',
        'approved_by', // admin
        'purpose',

    ];


    public function user(){
        return $this->belongsTo(User::class);
    }
    public function member(){
        return $this->belongsTo(Member::class);
    }
    public function loanType(){
        return $this->belongsTo(LoanType::class);
    }

}
