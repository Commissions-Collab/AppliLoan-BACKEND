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

    public function member()
    {
        return $this->belongsTo(Member::class);
    }
}
