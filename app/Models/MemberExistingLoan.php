<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MemberExistingLoan extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'member_id',
        'loan_type_id',
        'creditor_name',
        'date_granted',
        'original_amount',
        'outstanding_balance',
        'monthly_installment',
        'status'
    ];

    public function member()
    {
        return $this->belongsTo(Member::class);
    }

    public function loanType()
    {
        return $this->belongsTo(LoanType::class);
    }
}
