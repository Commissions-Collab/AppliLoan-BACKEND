<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoanSchedule extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'loan_id',
        'due_date',
        'amount_due',
        'status',
    ];

    public function loan()
    {
        return $this->belongsTo(Loan::class);
    }

    public function penalties()
    {
        return $this->hasMany(LoanPenalty::class);
    }

    public function payments()
    {
        return $this->hasMany(LoanPayment::class, 'schedule_id');
    }
}
