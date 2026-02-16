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

    // public function registerMediaCollections(): void
    // {
    //     // 1. الصورة اللي بتظهر برا كغلاف للخدمة
    //     $this->addMediaCollection('main_image')
    //         ->singleFile();

    //     // 2. معرض الصور (Portfolio) - للمصورين يعرضوا لقطاتهم، وللمنسقين صور حفلات سابقة
    //     $this->addMediaCollection('gallery');

    //     // 3. فيديو ترويجي (اختياري)
    //     $this->addMediaCollection('promo_video')
    //         ->singleFile();
    // }

    public function serviceable()
    {
        return $this->morphTo('serviceable');
    }

    public function orderStatusAble(){
        return $this->morphOne(OrderStatus::class,'orderable');
    }
}
