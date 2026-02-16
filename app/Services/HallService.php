<?php

// namespace App\Services;

// use App\Exceptions\Handler;
// use App\Models\Permission;
// use App\Models\Room;
// use App\Models\Service;
// use App\Models\ServiceStatus;
// use App\Models\User;
// use App\Services\Owner\UserService;
// use Illuminate\Support\Facades\Auth;
// use Illuminate\Validation\ValidationException;
// use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

// class HallService
// {
//     private $userService;
//     protected $handler;

//     public function __construct(Handler $handler)
//     {
//         $this->userService=new UserService();
//         $this->handler = $handler;


//     }


//     public function getHallById($id)
//       {
//         $hall = User::where('id', '=', $id)
//             ->with(['rooms.serviceStatusAble','services.serviceStatusAble']) // Eager load rooms and their serviceStatusAble
//             ->whereHas('role', function ($query) {
//                 $query->where('name', 'halls');
//             })
//             ->first();

//         if ($hall) {
//             return $hall;
//         }

//         throw new NotFoundHttpException('The Hall not found');
//     }

//     public function getHalls(){
//         $halls = User::withRole('halls')->get();
//         if($halls){
//             return $halls;
//         }
//         throw new NotFoundHttpException('not found any Halls');
 
//     }
//     public function addRoom(array $data){
//         $user_id=Auth::guard('api')->id();
//         $user=$this->userService->findUser($user_id);
//         // $user=User::find($user_id);
//         // dd($user);
//         if($user->checkRoomIfExist($data['name'])==true){
//             //first
//             // $e= ValidationException::withMessages(['the Role has this permission']);
//             // return $this->handler->errorResponse(false,$e->getMessage(),null,422); 

//             //second
//             // $e=ValidationException::withMessages(['The Hall Has this room']);
//             // throw $e->withMessages(['The Hall Has this room']);
//             return false;
//         }
//         $room_create=Room::create($data);
//         $statusRoom=new ServiceStatus();
//         $statusRoom->statusable_type=get_class($room_create);
//         $statusRoom->statusable_id=$room_create->id;
//         $statusRoom->status_id=3;
//         $statusRoom->save();

//             foreach ($data['image'] as $image) {
//                 $roomImage=$room_create->addMedia($image)
//                 ->usingFileName($image->getClientOriginalName())  
//                 ->toMediaCollection('images');
//                 $roomImage->image_type='photo';
//                 $roomImage->save();
//             }
//         $room_create->user_id=$user_id;
//         $room_create->save();
//         return $room_create;

//         }

//         public function addService(array $data){
//             $user_id=Auth::guard('api')->id();
//             $user=$this->userService->findUser($user_id);
//             // $user=User::find($user_id);
//             // dd($user);
//             if($user->checkServiceIfExist($data['name'])==true){
//                 // $e= ValidationException::withMessages(['the Role has this permission']);
//                 // return $this->handler->errorResponse(false,$e->getMessage(),null,422); 
//                 return false;
//             }
//             $service_create=Service::create([
//                 'name'=>$data['name'],
//                 'name_ar'=>$data['name_ar'],
//                 'description'=>$data['description'],
//                 'description_ar'=>$data['description_ar']?? null,

//                 'price'=>$data['price'],
//                 // 'user_id'=>Auth::guard('sanctum')->id(),
//             ]);
//             $service_create->user_id=$user_id;
//             $service_create->save();
//             $statusService=new ServiceStatus();

//             $statusService->statusable_type=get_class($service_create);
//             $statusService->statusable_id=$service_create->id;
//             $statusService->status_id=3;
//             $statusService->save();

//                 foreach ($data['image'] as $image) {
//                     $serviceImage=$service_create->addMedia($image)
//                     ->usingFileName($image->getClientOriginalName())  
//                     ->toMediaCollection('images');
//                     $serviceImage->image_type='photo';
//                     $serviceImage->save();
//                 }

//             return $service_create;
    
//             }

//             public function updateService(array $data,$id){
//                 $user_id=Auth::guard('api')->id();
//                 $user=$this->userService->findUser($user_id);
//                 $userService=$user->services()->where('id',$id)->first();
//                 if(!$userService){
//                     throw new NotFoundHttpException('not found any Service in this id');
//                 }
//                 if(isset($data['name']) && $user->checkServiceIfExist($data['name'])==true){
//                     // $e= ValidationException::withMessages(['the Role has this permission']);
//                     // return $this->handler->errorResponse(false,$e->getMessage(),null,422); 
//                     return false;
//                 }
//                 // dd($data);
//                 $userService->update($data);
//                 // create([
//                 //     'name'=>$data['name'],
//                 //     'description'=>$data['description'],
//                 //     'price'=>$data['price'],
//                 //     'user_id'=>Auth::guard('sanctum')->id(),
//                 // ]);
//                 ////in here 
//                 if(isset($data['image'])){
//                         foreach ($data['image'] as $image) {
//                             $serviceImage=$userService->addMedia($image)
//                             ->usingFileName($image->getClientOriginalName())  
//                             ->toMediaCollection('images');
//                             $serviceImage->image_type='photo';
//                             $serviceImage->save();
//                         }
//                     }
//                 return $userService;
//                 }

//             public function addImageForRoom($data){
//                 $userObject=new UserService();
//                 $user=$userObject->findUser($data['user_id']);
//                 foreach ($data['image'] as $image) {
//                     $mediaItem=$user->addMedia($image)
//                     ->usingFileName($image->getClientOriginalName())  
//                     ->toMediaCollection('images');
//                     $mediaItem->update(['image_type' => 'photo']);
//                 }
//             }

//             public function addImageForService($data){
//                 $user=$this->getHallById($data['user_id']);
//                 $service=$user->services()->where('id',$data['id'])->first();
//                 if($service){
//                     $arrayPhoto=[];
//                     foreach ($data['image'] as $image) {
//                         $mediaItem=$service->addMedia($image)
//                         ->usingFileName($image->getClientOriginalName())  
//                         ->toMediaCollection('images');
//                         $mediaItem->update(['image_type' => 'photo']);
//                         $arrayPhoto[] = $mediaItem->original_url;
//                     }
//                     return $arrayPhoto;
//                 }else{
//                     throw new NotFoundHttpException('this service not found');                }
//             }
//     }

