<?php

// namespace App\Http\Controllers\User;

// use App\Exceptions\Handler;
// use App\Http\Controllers\Controller;
// use App\Http\Requests\User\AddImageForRoomRequest;
// use App\Http\Requests\User\AddImageForServiceRequest;
// use App\Http\Requests\User\AddRoomrequest;
// use App\Http\Requests\User\AddServiceRequest;
// use App\Http\Requests\User\UpdateServiceRequest;
// use App\Http\Resources\Hall\GetAllHallsResource;
// use App\Http\Resources\Hall\GetHallByIdResource;
// use App\Services\HallService;
// use Exception;
// use Illuminate\Http\Request;
// use Illuminate\Support\Facades\Auth;
// use Illuminate\Validation\ValidationException;

// class HallController extends Controller
// {


//     private $hallService;
//     protected $handler;

//     public function __construct(Handler $handler)
//     {
//         $this->hallService=new HallService($handler);
//         $this->handler =$handler;

//     }
    
//     public function getHalls(){
//         $halls=$this->hallService->getHalls();
//         // $getHalls=new GetAllHallsResource($halls);
//         return $this->handler->successResponse(GetAllHallsResource::collection($halls),'get hall success',200);
//     }

//     public function getHallById($id){
//         $halls=$this->hallService->getHallById($id);
//         $getHallById=new GetHallByIdResource($halls);
//         return $this->handler->successResponse(['hall'=>$getHallById],'get hall by id success',200);
//     }

//     public function addRoom(AddRoomRequest $request){
//         try{
//             $data=$request->all();
//             $add_hall=$this->hallService->addRoom($data);
//             if($add_hall==false){
//                 $e= ValidationException::withMessages(['the Room is already exist']);
//                 return $this->handler->errorResponse(false,$e->getMessage(),null,422); 
//             }
//             return $this->handler->successResponse(['room'=>$add_hall],'Add Room success',201);
//         }catch(Exception $e){
//             return $this->handler->errorResponse(false,$e->getMessage(),null,400); 

//         }
//     }

//     public function addService(AddServiceRequest $request){
//         try{
//             $data=$request->all();
//             $add_service=$this->hallService->addService($data);
//             if($add_service==false){
//                 $e= ValidationException::withMessages(['the Service is already exist']);
//                 return $this->handler->errorResponse(false,$e->getMessage(),null,422); 
//             }
//             return $this->handler->successResponse(['service'=>$add_service],'Add service success',201);
//         }catch(Exception $e){
//             return $this->handler->errorResponse(false,$e->getMessage(),null,400); 

//         }
//     }

//     public function updateService(UpdateServiceRequest $request,$id){
//         $data=$request->all();
//         $add_service=$this->hallService->updateService($data,$id);
//         if($add_service==false){
//             $e= ValidationException::withMessages(['the Service is already exist']);
//             return $this->handler->errorResponse(false,[$e->getMessage()],null,422); 
//         }
//         return $this->handler->successResponse(['service'=>$add_service],'update service success',201);
//     }
// ///in here  I modified the 
//     public function addImageForRoom(AddImageForRoomRequest $request){
//         $data=$request->all();
//         $data['user_id']=Auth::guard('sanctum')->id();
//         $add_image=$this->hallService->addImageForRoom($data);
//         return $this->handler->successResponse(['image'=>$add_image],'Add images success',201);
//     }

//     public function addImageForService(AddImageForServiceRequest $request,$id){
//         $data=$request->all();
//         $data['id']=$id;
//         $data['user_id']=Auth::guard('sanctum')->id();
//         $add_image=$this->hallService->addImageForService($data);
//         return $this->handler->successResponse(['images'=>$add_image],'Add images success',201);
//     }
// }
