<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class User extends Authenticatable implements HasMedia
{
    use HasApiTokens,HasFactory, Notifiable,InteractsWithMedia,SoftDeletes;

    
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'phone_number',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'password' => 'hashed',
        'email_verified_at' => 'datetime',
    ];

    public function userable(){
        return $this->morphTo('userable');
    }

    public function role(){
        return $this->belongsTo(Role::class);
    }

    // public function rooms(){
    //     return $this->hasMany(Room::class,'user_id');
    // }

    // public function services(){
    //     return $this->hasMany(Service::class,'user_id');
    // }

    public function types(){
        return $this->belongsToMany(Type::class,'type_user','user_id','type_id');
    }
    // public function isAdmin(){
    //     return $this->is_admin == true;
    // }

    // public function medias()
    // {
    //     return $this->hasMany(Media::class);
    // }


    public function isHall(){
        return $this->role()->where('name', 'hall');
    }
    
    public function scopeIsHall($query)
    {
        return $query->whereHas('role', function ($q) {
            $q->where('name', 'hall');
        });
    }

    public function hasPermission($permissionName)
    {
        if ($this->role->permissions()->where('name', $permissionName)->where('allowed', true)->exists()) {
            return true;
        }
        return false;
    }

    public function checkRoomIfExist($roomName){
        if($this->rooms()->where('name','=',$roomName)->exists()){
            return true;
        }
        return false;
    }

    public function checkServiceIfExist($serviceName){
        if($this->services()->where('name','=',$serviceName)->exists()){
            return true;
        }
        return false;
    }
    public function checkServiceId($service_id){
        if($this->services()->where('id',$service_id)->exists()){
            return true;
        }
        return false;
    }

    public function scopeWithRole($query, $roleName)
    {
        return $query->whereHas('role', function($q) use ($roleName) {
            $q->where('name', $roleName);
        });
    }


}
