<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoanCollateral extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'loan_id',
        'collateral_type',
        'description',
        'appraised_value',
        'location',
        'additional_details',
        'status'
    ];
    public function loan()
    {
        return $this->belongsTo(Loan::class);
    }
}
