<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoanClerk extends Model
{
    /** @use HasFactory<\Database\Factories\LoanClerkFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'clerk_id',
        'full_name',
        'contact_number',
        'gender',
        'address',
        'job_title',
        'date_hired',
        'status'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
