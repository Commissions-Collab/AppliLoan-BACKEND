<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MemberSpouse extends Model
{
    use HasFactory;

    protected $fillable = [
        'member_id',
        'full_name',
        'street_address',
        'city',
        'province',
        'postal_code',
        'tin_number',
        'contact_number',
        'date_of_birth',
        'place_of_birth',
        'age',
        'dependants',
        'employer',
        'position',
        'monthly_income',
        'other_income',
        'monthly_disposable_income_range',
        'status'
    ];

    public function member()
    {
        return $this->belongsTo(Member::class);
    }
}
