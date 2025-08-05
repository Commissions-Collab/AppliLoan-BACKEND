<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MemberExpense extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'member_id',
        'living_expenses',
        'utilities',
        'transportation_expenses',
        'total_monthly_expenses'
    ];

    public function member()
    {
        return $this->belongsTo(Member::class);
    }
}
