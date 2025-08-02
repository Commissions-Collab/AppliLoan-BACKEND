<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LoanType extends Model
{
    protected $fillable =[
        'type_name',
        'description',
        'min_amount',
        'max_amount',
        'interest_rate',
        'max_term_months',
        'collateral_required',
    ];
}
