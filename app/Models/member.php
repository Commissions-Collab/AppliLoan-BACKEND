<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Member extends Model
{
    use HasFactory;

    protected $fillable = [
        'member_number',
        'user_id',
        'full_name',
        'phone_number',
        'address',
        'date_of_birth',
        'place_of_birth',
        'age',
        'civil_status',
        'religion',
        'tin_number',
        'status',
        'employer',
        'position',
        'monthly_income',
        'other_income',
        'share_capital',
        'fixed_deposit',
        'seminar_date',
        'venue',
        'brgy_clearance',
        'birth_cert',
        'certificate_of_employment',
        'applicant_photo',
        'valid_id_front',
        'valid_id_back',
        'number_of_children',
        'spouse_name',
        'spouse_employer',
        'spouse_monthly_income',
        'spouse_birth_day',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'is_member' => 'boolean',
        'monthly_income' => 'decimal:2',
        'share_capital' => 'decimal:2',
        'fixed_deposit' => 'decimal:2',
        'spouse_monthly_income' => 'decimal:2',
    ];

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function account(): HasOne
    {
        return $this->hasOne(MemberAccount::class, 'member_id');
    }

    public function loanApplications(): HasMany
    {
        return $this->hasMany(LoanApplication::class, 'user_id', 'user_id');
    }

    public function loans(): HasManyThrough
    {
        return $this->hasManyThrough(Loan::class, LoanApplication::class);
    }

    public function memberLogins(): HasMany
    {
        return $this->hasMany(MemberLogin::class);
    }

    public function memberEngagements(): HasMany
    {
        return $this->hasMany(MemberEngagement::class);
    }
    // Accessors
    public function getTotalIncomeAttribute()
    {
        return $this->monthly_income + $this->other_income;
    }
    public function userId(): BelongsTo
    {
        return $this->belongsTo(Request::class, 'user_id');
    }
}
