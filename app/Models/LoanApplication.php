<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class LoanApplication extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'loan_type_id',
        'product_id',
        'user_name',
        'applied_amount',
        'term_months',
        'phone',
        'age',
        'address',
        'tin_number',
        'employer',
        'position',
        'monthly_income',
        'other_income_source',
        'monthly_disposable_income',
        'birth_month',
        'place_of_birth',
        'no_of_dependents',
        'education_expense',
        'food_expense',
        'house_expense',
        'transportation_expense',
        'date_granted',
        'monthly_installment',
        'SMPC_regular_loan',
        'SMPC_petty_cash_loan',
        'total_amortization',
        'applicant_photo',
        'certificate_of_employment',
        'bragy_certificate',
        'valid_id_front',
        'valid_id_back',
        'birth_certificate',
        'preferred_meeting_date',
        'preferred_meeting_time',
        'application_date',
        'status',
        'processed_by',
        'rejection_reason',
    ];

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function loanType(): BelongsTo
    {
        return $this->belongsTo(LoanType::class, 'loan_type_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function processedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function loan(): HasOne
    {
        return $this->hasOne(Loan::class);
    }

    public function notifications(): MorphMany
    {
        return $this->morphMany(Notification::class, 'notifiable');
    }
}
