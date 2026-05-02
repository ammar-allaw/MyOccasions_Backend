<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Status extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'name_en',
    ];

    public function orderStatuses(){
        return $this->hasMany(OrderStatus::class,'status_id');
    }
}
