<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Region extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'name_en',
        'government_id',
    ];

    public function government()
    {
        return $this->belongsTo(Government::class);
    }
}
