<?php

namespace App\Services\Owner;

use App\Exceptions\Handler;
use App\Models\Note;
use App\Models\Permission;
use App\Models\Room;
use App\Models\Service;
use App\Models\ServiceStatus;
use App\Models\User;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class UserService
{
    // protected $handler=new Handler;
    // public function __construct()
    // {
    //      $this->handler =$handler;
    // }
    public function findUser($user_id){
        $user=User::find($user_id);
        if($user){
            return $user;
        }
        throw new NotFoundHttpException('User not found');
 
    }

    public function add_user(array $data,$image,$user_type_id)
    {
            $create_user=User::create($data);
            $image = $image;
            $mediaItem=$create_user->addMedia($image)
            ->usingFileName($image->getClientOriginalName())  
            ->toMediaCollection('logo_user');
            $mediaItem->update(['allowed' => true]);
            $create_user->role_id=$data['role_id'];
            $create_user->save();
            $user_type=$create_user->types()->attach($user_type_id);
            // $user_type->save();
            return ['user'=>$create_user];
    }

    public function update_user(array $data, $user_id)
    {
        $user = $this->findUser($user_id);
    
        if (isset($data['image'])) {
            $image = $data['image'];
            unset($data['image']);
        }    
        $user->update($data);
        $user->role_id=$data['role_id'] ?? $user->role_id;
        $user->is_admin=$data['is_admin'] ?? $user->is_admin;
        $user->save();
        if (isset($image)) {
            $user->clearMediaCollection('logo_user')->where('image_type','=','logo');    
            $mediaItem = $user->addMedia($image)
                ->usingFileName($image->getClientOriginalName())
                ->toMediaCollection('images');    
            $mediaItem->update(['allowed' => true]);
        }    
        return ['user'=>$user];
    }

    public function softDeleteUser($user_id){
        $user=$this->findUser($user_id);
        $user->delete();
    }

    public function getSoftDeleteUser(){
        $softDeletedUsers = User::onlyTrashed()->get();
        return $softDeletedUsers;
    }

    public function findSoftDeleteUser($user_id){
        $user = User::onlyTrashed()->find($user_id);
        if($user){
            return $user;
        }
        throw new NotFoundHttpException('User not found');
    }

    public function restoreUser($user_id){
        $user=$this->findSoftDeleteUser($user_id);
        $user->restore();
        return $user;
    }

    public function deleteUser($user_id){
        $user=$this->findSoftDeleteUser($user_id);
        $user->forceDelete();
        return $user;
    }

    public function getAllImageNotAllow(){
        $mediaImages = Media::where('collection_name', 'images')->where('image_type','=','photo')->where('allowed', false)->get();
        $data = [];
    
        foreach ($mediaImages as $image) {
            // $model_name = '';
            // $service_name='';
            // $room_name='';
            // if ($image->model_type == 'App\Models\User') {
            //     $model_name = User::where('id', '=', $image->model_id)->value('name');
            // } else if ($image->model_type == 'App\Models\Room') {
            //     $room = Room::find($image->model_id);
            //     if ($room) {
            //         $room_name=$room->name;
            //         $model_name = $room->user->name;
            //     }
            // } else if ($image->model_type == 'App\Models\Service') {
            //     $service = Service::find($image->model_id);
            //     if ($service) {
            //         $service_name=$service->name;
            //         $model_name = $service->user->name;
            //     }
            // }
    
            // $data[] = [
            //     'image_id' => $image->id,
            //     'model_id' => $image->model_id,
            //     'model_type' => $image->model_type,
            //     'model_name' => $model_name,
            //     'service_name'=>$service_name ?? null,
            //     'room_name'=>$room_name ?? null,
            //     'image' => url('storage/' . $image->id . '/' . $image->file_name)
            // ];

            if($image->model){
                $hallRoom=$image->model->user->name ?? null;
            }

            $data[] = [
                'image_id' => $image->id,
                'model_id' => $image->model_id,
                'model_type'=>get_class($image->model),
                'model_name' => $image->model->name ?? null,
                'hall_name' => $hallRoom,
                // 'service_name'=>$service_name ?? null,
                // 'room_name'=>$room_name ?? null,
                'image' => url('storage/' . $image->id . '/' . $image->file_name)
            ];

            // if($service_name!=null){
            //     $data['service_name']=$service_name;
            // }
            // if($room_name!=null){
            //     $data['room_name']=$room_name;
            // }
        }
    
        return $data;
    }

    public function getAllUser(){
        $users=User::get();
        return $users;
    }

    public function getImageById($imageId){
        $image=Media::find($imageId);
        if($image){
            return $image;
        }
        throw new NotFoundHttpException('image not found');
    }

    public function acceptImage($imageId){
        $image=$this->getImageById($imageId);
        $image->update(['allowed'=>true]);
        return $image;
    }
    
    public function findServiceStatusById($id){
        $getStatusAble=ServiceStatus::where('id',$id)->first();
        if($getStatusAble){
            return $getStatusAble;
        }
    }

    public function getAllStatusNotAccepted($data){
        $getStatusAble=ServiceStatus::where('status_id',$data['status_id'])->get();
        if($getStatusAble){
            return $getStatusAble;
        }
    }


    public function acceptStatusAble($id){
        $getStatusAble = $this->findServiceStatusById($id);
        if ($getStatusAble) {
            // dd($getStatusAble->statusAble->has('media'));
            if ($getStatusAble->statusAble->has('media')) {
                // Assuming media is a relationship, iterate through media and set allow = true
                foreach ($getStatusAble->statusAble->getMedia('images') as $mediaItem) {
                    $mediaItem->allowed = true;
                    $mediaItem->save(); // Save the change for each media item
                }
            }
            $getStatusAble->status_id = 1;
            $getStatusAble->save(); // Save the updated status
            return $getStatusAble;
        }
    }

    public function rejectStatusAble($data,$id){
        $getStatusAble=$this->findServiceStatusById($id);
        if($getStatusAble->status_id==3){
            $getStatusAble->statusAble->name;
            // dd($getStatusAble->statusAble->user->name);
            $getStatusAble->status_id=2;
            $getStatusAble->save();
            $note=new Note();
            $note->message=$data['message'];
            $note->service_status_id=$getStatusAble->id;
            $note->save();
            return $getStatusAble;
        }
    }
}
