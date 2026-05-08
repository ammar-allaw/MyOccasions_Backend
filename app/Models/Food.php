<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Food extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;
    protected $table = 'foods';
    protected $fillable = [
        'name',
        'name_en',
        'main_key_id',
        'service_provider_id',
        'slug',
        'description',
        'description_en',
        'short_description',
        'short_description_en',
        'price',
        'discount_price',
        'is_available',
        'is_active',
        'preparation_time',
        'calories',
        'ingredients',
        'portion_size',
    ];

    protected $casts = [
        'is_available' => 'boolean',
        'is_active' => 'boolean',
        'price' => 'decimal:2',
        'discount_price' => 'decimal:2',
    ];

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('food_image')->singleFile();
    }

    public function mainKey()
    {
        return $this->belongsTo(MainKey::class);
    }

    public function serviceProvider()
    {
        return $this->belongsTo(ServiceProvider::class);
    }

    public function orderStatusAble()
    {
        return $this->morphOne(OrderStatus::class, 'orderable');
    }
}
