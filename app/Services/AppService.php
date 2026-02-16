<?php

// namespace App\Services;

// use App\Models\Permission;
// use App\Models\Room;
// use App\Models\Service;
// use App\Models\User;
// use App\Services\Owner\UserService;
// use Illuminate\Support\Facades\Auth;
// use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

// class AppService
// {

//     public function getHallById($id){
//         $hall=User::where('id','=',$id)->whereHas('role', function($query) {
//             $query->where('name','hall');
//         })->first();
//         if($hall){
//             return $hall;
//         }
//         throw new NotFoundHttpException('The Hall not found');
//     }

//     public function getHalls(){

//         $halls = User::whereHas('role', function($query) {
//             $query->where('name','hall');
//         })->get();        
//         if($halls){
//             return $halls;
//         }
//         throw new NotFoundHttpException('not found any Halls');
 
//     }
//     public function addRoom(array $data){
//         $room_create=Room::create($data);
//             foreach ($data['image'] as $image) {
//                 $room_create->addMedia($image)
//                 ->usingFileName($image->getClientOriginalName())  
//                 ->toMediaCollection('images');
//             }
//             // dd(Auth::guard('sanctum')->user()->id);
//         $room_create->user_id=Auth::guard('sanctum')->user()->id;
//         $room_create->save();
//         return $room_create;

//         }

//         public function addService(array $data){
//             $service_create=Service::create([
//                 'name'=>$data['name'],
//                 'description'=>$data['description'],
//                 'price'=>$data['price'],
//                 'user_id'=>Auth::guard('sanctum')->id(),
//             ]);
//                 foreach ($data['image'] as $image) {
//                     $service_create->addMedia($image)
//                     ->usingFileName($image->getClientOriginalName())  
//                     ->toMediaCollection('images');
//                 }

//             return $service_create;
    
//             }

//             public function addImageForRoom($data){
//                 $userObject=new UserService();
//                 $user=$userObject->findUser($data['user_id']);
//                 foreach ($data['image'] as $image) {
//                     $user->addMedia($image)
//                     ->usingFileName($image->getClientOriginalName())  
//                     ->toMediaCollection('images');
//                 }
//             }
//     }

