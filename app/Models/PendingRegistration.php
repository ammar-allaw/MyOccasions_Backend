<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PendingRegistration extends Model
{
    protected $fillable = [
        'phone_number',
        'first_name',
        'last_name',
        'password',
        'government_id',
        'otp_hash',
        'attempts',
        'expires_at',
        'last_sent_at',
    ];

    protected $casts = [
        'attempts' => 'integer',
        'expires_at' => 'datetime',
        'last_sent_at' => 'datetime',
    ];
}
