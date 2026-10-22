<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'account_status',
        'email_verified_at',
        'google_id',
        'avatar',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
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
        ];
    }

    /**
     * User's referral verification attempts.
     */
    public function referralVerifications(): HasMany
    {
        return $this->hasMany(ReferralVerification::class)->orderByDesc('id');
    }

    /**
     * Most recent referral verification record.
     */
    public function latestReferralVerification(): HasOne
    {
        return $this->hasOne(ReferralVerification::class)->latestOfMany();
    }

    /**
     * User's chart scans.
     */
    public function scans(): HasMany
    {
        return $this->hasMany(Scan::class)->orderByDesc('id');
    }

    /**
     * User's audit logs.
     */
    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class)->orderByDesc('id');
    }

    /**
     * Check if user is an administrator.
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Check if account is suspended.
     */
    public function isSuspended(): bool
    {
        return $this->account_status === 'suspended';
    }

    /**
     * Get the current effective referral status.
     * If user is admin, they are automatically granted VERIFIED status for testing & management.
     */
    public function referralStatus(): string
    {
        if ($this->isAdmin()) {
            return 'VERIFIED';
        }

        $latest = $this->latestReferralVerification;
        return $latest ? $latest->status : 'NOT_STARTED';
    }

    /**
     * Server-side permission check: Can this user run the scanner?
     * Requirements:
     * 1. Account must be active (not suspended)
     * 2. Must be admin OR have referral status == 'VERIFIED'
     */
    public function canAccessScanner(): bool
    {
        if ($this->isSuspended()) {
            return false;
        }

        if ($this->isAdmin()) {
            return true;
        }

        return $this->referralStatus() === 'VERIFIED';
    }
}
