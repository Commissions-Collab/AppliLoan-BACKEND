<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LoanType extends Model
{
    use HasFactory;

    protected $fillable = [
        'type_name',
        'description',
        'min_amount',
        'max_amount',
        'interest_rate',
        'max_term_months',
        'collateral_required',
    ];

    protected $casts = [
        'min_amount' => 'decimal:2',
        'max_amount' => 'decimal:2',
        'interest_rate' => 'decimal:2',
        'collateral_required' => 'boolean',
    ];

    public function loanApplications(): HasMany
    {
        return $this->hasMany(LoanApplication::class);
    }
}
