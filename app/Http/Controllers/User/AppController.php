<?php

// namespace App\Http\Controllers\User;

// use App\Exceptions\Handler;
// use App\Http\Controllers\Controller;
// use App\Http\Requests\Application\SearchRequest;
// use App\Http\Resources\Hall\GetAllHallsResource;
// use App\Models\User;
// use App\Services\AppService;
// use App\Services\HallService;
// use App\Services\Owner\RoleService;
// use Exception;
// use Illuminate\Http\Request;

// class AppController extends Controller
// {

//     private $hallService;
//     private $appService;
//     private $roleService;
//     protected $handler;

//     public function __construct(Handler $handler)
//     {
//         $this->hallService=new HallService($handler);
//         $this->appService=new AppService;
//         $this->roleService=new RoleService;
//         $this->handler =$handler;
//     }
    

//     public function search(SearchRequest $request){
       
//             $searchValue = $request->searchValue;
//             // Query users that have the "hall" role and apply the isHall scope
//             $query = User::query()->isHall();
        
//             if ($searchValue) {
//                 // Search for halls by name or location if searchValue is provided
//                 $halls = $query->where(function ($query) use ($searchValue) {
//                     $query->where('name', 'like', '%' . $searchValue . '%')
//                           ->orWhere('location', 'like', '%' . $searchValue . '%');
//                 })->get();
//             } else {
//                 // No searchValue provided, so apply additional filters if they exist
//                 if ($request->filled('location')) {
//                     $query->where('location', $request->location);
//                 }
        
//                 if ($request->filled('capacity')) {
//                     $query->whereHas('rooms', function ($query) use ($request) {
//                         $query->where('capacity', '<=', $request->capacity);
//                     });
//                 }
        
//                 if ($request->filled('price')) {
//                     $query->whereHas('rooms', function ($query) use ($request) {
//                         $query->where('rent_price', '<=', $request->price);
//                     });
//                 }
//                 $halls = $query->get();
//             }
//             $getHalls=new GetAllHallsResource($halls);
//             return $this->handler->successResponse(['halls'=>$getHalls], 'Get halls success', 200);
//         }

//         //get the roles for owner and for application
//         public function getRolesExceptOwner(){
//             $roles=$this->roleService->getRolesExceptOwner();
//             return $this->handler->successResponse($roles,'get Roles success',200);
//         }

//         //return the users (hall or store_clothes ......) by the id of role 
//         //for owner and application
//         public function getUsersByIdOfRole($role_id){
//             try{
//                 $role=$this->roleService->find_role_by_id($role_id);
//                 $users=$this->roleService->getUsersByIdOfRole($role);
//                 return $this->handler->successResponse($users,'get hall success',200);
//             }catch(Exception $e){
//                 return $this->handler->errorResponse(false,$e->getMessage(),null,400); 
//             }
//         }
// }
