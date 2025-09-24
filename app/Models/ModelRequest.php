<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ModelRequest extends Model
{
     use HasFactory;

    protected $table = 'requests';

    protected $fillable = [
        'request_to',
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
    ];

    // Relationships
    public function userId(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    public function requestTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'request_to');
    }
}
