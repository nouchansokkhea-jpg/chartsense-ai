<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReferralEvidence extends Model
{
    use HasFactory;

    protected $table = 'referral_evidence';

    protected $fillable = [
        'referral_verification_id',
        'file_path',
        'file_name',
        'file_size',
        'mime_type',
        'description',
    ];

    public function verification(): BelongsTo
    {
        return $this->belongsTo(ReferralVerification::class, 'referral_verification_id');
    }
}
