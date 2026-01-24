<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderStatus extends Model
{
    use HasFactory;

    protected $fillable = [
        'status_id',
        'orderable_id',
        'orderable_type',
        'change_description',
        'last_modified_at',
    ];

    protected $casts = [
        'last_modified_at' => 'datetime',
    ];

    public function status(){
        return $this->belongsTo(Status::class,'status_id');
    }

    public function orderable(){
        return $this->morphTo('orderable');
    }

    public function note(){
        return  $this->hasOne(Note::class);
    }
}
