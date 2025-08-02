<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LoanPayment extends Model
{
    protected $fillable = [
        'loan_id',
        'payment_date',
        'amount_paid',
        'principal_payment',
        'interest_payment',
        'penalty_payment',
        'remaining_balance',
        'payment_method',
        'receipt_number',
        'received_by',
       
    ];


    public function user(){
        return $this ->belongsTo(User::class);
    }
    public function loan(){
        return $this ->belongsTo(Loan::class);
    }
}
