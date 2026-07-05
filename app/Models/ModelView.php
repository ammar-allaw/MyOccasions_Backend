<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ModelView extends Model
{
    protected $fillable = [
        'viewable_type',
        'viewable_id',
        'user_id',
        'ip_address',
        'user_agent',
        'viewed_on',
    ];

    protected $casts = [
        'viewed_on' => 'date',
    ];

    public function viewable()
    {
        return $this->morphTo();
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
