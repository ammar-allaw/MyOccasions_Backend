<?php

namespace App\Models;

use App\Enums\LegalDocumentType;
use Illuminate\Database\Eloquent\Model;

class LegalDocument extends Model
{
    protected $fillable = [
        'type',
        'title',
        'title_en',
        'description',
        'description_en',
        'is_active',
    ];

    protected $casts = [
        'type' => LegalDocumentType::class,
        'is_active' => 'boolean',
    ];
}
