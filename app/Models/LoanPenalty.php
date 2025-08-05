<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoanPenalty extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'loan_id',
        'penalty_rate',
        'penalty_amount',
        'due_date',
        'penalty_date',
        'days_overdue',
        'status',
        'remarks'
    ];
    
    public function loan()
    {
        return $this->belongsTo(Loan::class);
    }

    public function schedule()
    {
        return $this->belongsTo(LoanSchedule::class);
    }
}
