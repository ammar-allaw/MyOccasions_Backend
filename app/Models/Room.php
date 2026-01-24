<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Room extends Model implements HasMedia
{
    use HasFactory,InteractsWithMedia;


    protected $fillable = [
        'name',
        'description',
        'name_en',
        'description_en',
        'rent_price',
        'service_provider_id',
        'image[]',
        'capacity',
    ];


    public function serviceProvider(){
        return $this->belongsTo(ServiceProvider::class);
    }


    public function orderStatusAble(){
        return $this->morphOne(OrderStatus::class,'orderable');
    }

    public function services(){
        return $this->morphMany(Service::class,'serviceable');
    }
    
}
