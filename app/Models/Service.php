<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Service extends Model implements HasMedia
{
    use HasFactory,InteractsWithMedia;

    protected $fillable = [
        'name',
        'description',
        'name_en',
        'description_en',
        'price',
        'image[]',
    ];

    public $timestamps = false;


    public function serviceable()
    {
        return $this->morphTo('serviceable');
    }

    public function orderStatusAble(){
        return $this->morphOne(OrderStatus::class,'orderable');
    }
}
