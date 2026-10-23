<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InteractionCounter extends Model
{
    protected $fillable = [
        'interactable_type',
        'interactable_id',
        'views_count',
        'likes_count',
    ];

    protected $casts = [
        'views_count' => 'integer',
        'likes_count' => 'integer',
    ];

    public function interactable()
    {
        return $this->morphTo();
    }
}
