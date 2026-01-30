<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class ServiceProvider extends Model implements HasMedia
{
    use HasFactory,InteractsWithMedia;

       protected $fillable = [
        'name',
        'name_en',
        'description',
        'description_en',
        'location',
        'location_en',
        'government_id',
        'region_id',
        'address_url',
        // 'image[]',
        
    ];

    public function rooms(){
        return $this->hasMany(Room::class,'service_provider_id');
    }

    public function services(){
        return $this->morphMany(Service::class,'serviceable');
    }

    public function user(){
        return $this->morphOne(User::class, 'userable');
    }

    public function orderStatusAble(){
        return $this->morphOne(OrderStatus::class, 'orderable');
    }

    public function government()
    {
        return $this->belongsTo(Government::class);
    }

    public function region()
    {
        return $this->belongsTo(Region::class);
    }

}
