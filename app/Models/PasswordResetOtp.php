<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PasswordResetOtp extends Model
{
    protected $hidden = [
        'otp_hash',
        'reset_token_hash',
    ];

    protected $fillable = [
        'user_id',
        'phone_number',
        'otp_hash',
        'attempts',
        'expires_at',
        'last_sent_at',
        'verified_at',
        'reset_token_hash',
        'reset_token_expires_at',
    ];

    protected $casts = [
        'attempts' => 'integer',
        'expires_at' => 'datetime',
        'last_sent_at' => 'datetime',
        'verified_at' => 'datetime',
        'reset_token_expires_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
