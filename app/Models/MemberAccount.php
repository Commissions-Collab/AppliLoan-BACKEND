<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MemberAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'member_id',
        'original_share_capital',
        'current_share_capital',
        'savings_balance',
        'regular_loan_balance',
        'petty_cash_balance'
    ];

    protected $casts = [
        'original_share_capital' => 'decimal:2',
        'current_share_capital' => 'decimal:2',
        'savings_balance' => 'decimal:2',
        'regular_loan_balance' => 'decimal:2',
        'petty_cash_balance' => 'decimal:2',
    ];

    public function member()
    {
        return $this->belongsTo(Member::class);
    }
}
