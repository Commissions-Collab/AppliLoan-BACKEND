<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Carbon\Carbon;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'email',
        'password',
        'role',
        'otp',
        'is_verified',
        'is_member',
        'verification_code',
        'verification_code_expires_at',
        'email_verified_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'otp',
        'verification_code',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'is_verified' => 'boolean',
            'verification_code_expires_at' => 'datetime',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::ADMIN;
    }

    public function isLoanClerk(): bool
    {
        return $this->role === UserRole::LOAN_CLERK;
    }

    public function isMember(): bool
    {
        return $this->role === UserRole::MEMBER;
    }

    /**
     * Generate a new OTP for the user
     */
    public function generateOtp(): string
    {
        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $this->update([
            'verification_code' => $otp,
            'verification_code_expires_at' => Carbon::now()->addMinutes(10),
        ]);
        
        return $otp;
    }

    /**
     * Verify the provided OTP
     */
    public function verifyOtp(string $inputOtp): bool
    {
        if (!$this->verification_code || !$this->verification_code_expires_at) {
            return false;
        }

        if (Carbon::now()->gt($this->verification_code_expires_at)) {
            return false; // OTP expired
        }

        if ($this->verification_code !== $inputOtp) {
            return false; // Invalid OTP
        }

        return true;
    }

    /**
     * Clear the OTP from the user record
     */
    public function clearOtp(): void
    {
        $this->update([
            'verification_code' => null,
            'verification_code_expires_at' => null,
        ]);
    }

    /**
     * Check if user is a verified member
     */
    public function isVerifiedMember(): bool
    {
        return $this->role === UserRole::MEMBER && $this->is_verified;
    }

    public function member(): HasOne
    {
        return $this->hasOne(Member::class);
    }

    public function clerk(): HasOne
    {
        return $this->hasOne(LoanClerk::class);
    }

    public function processedApplications()
    {
        return $this->hasMany(LoanApplication::class, 'processed_by');
    }

    public function approvedLoans(): HasMany
    {
        return $this->hasMany(Loan::class, 'approved_by');
    }

    public function receivedPayments(): HasMany
    {
        return $this->hasMany(LoanPayment::class, 'received_by');
    }
}
