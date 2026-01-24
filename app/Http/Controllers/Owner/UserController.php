<?php

// namespace App\Http\Controllers\Owner;

// use App\Exceptions\Handler;
// use App\Http\Controllers\Controller;
// use App\Http\Requests\Owner\AddUserRequest;
// use App\Http\Requests\Owner\GetStatusableByNameOfStatus;
// use App\Http\Requests\Owner\UpdateUserRequest;
// use App\Http\Requests\Owner\WriteMessageForStatus;
// use App\Http\Resources\Owner\GetAllStatusAble;
// use App\Services\Owner\RoleService;
// use App\Services\Owner\UserService;
// use Exception;
// use Illuminate\Http\Request;

// class UserController extends Controller
// {

//     private $userService;
//     protected $handler;
//     private $roleService;
//     public function __construct(Handler $handler)
//     {
//          $this->userService= new UserService();
//          $this->handler =$handler;
//          $this->roleService=new RoleService;


//     }
    
//     //comp
//     public function add_user(AddUserRequest $request){
//         try{
//             $data = $request->except(['image','user_type']);
//             $user_type=$request->user_type;
//             $image=$request->image;
//             $addUser=$this->userService->add_user($data,$image,$user_type);
//             return $this->handler->successResponse($addUser,'Added User successfully', 201); 
//         }catch(Exception $e){
//             return $this->handler->errorResponse(
//                 false,
//                 $e->getMessage(),
//                 null
//             ,401);
//         }
//     }

//         //comp
//     public function update_user(UpdateUserRequest $request,$user_id){
//         try{
//             $data=$request->all();
//             $updateUser=$this->userService->update_user($data,$user_id);
//             return $this->handler->successResponse($updateUser,'Update User successfully', 200); 
//         }catch(Exception $e){
//             return $this->handler->errorResponse(
//                 false,
//                 $e->getMessage(),
//                 null
//             ,401);
//         }
//     }

//     /// comp
//     public function softDeleteUser($user_id){
//         try{
//             $deleteUser=$this->userService->softDeleteUser($user_id);
//             return $this->handler->successResponse($deleteUser,'soft delete User successfully', 200); 
//         }catch(Exception $e){
//             return $this->handler->errorResponse(
//                 false,
//                 $e->getMessage(),
//                 null
//             ,401);
//         }
//     }

//     //comp
//     public function getSoftDeleteUser(){
//         $getDeleteUsers=$this->userService->getSoftDeleteUser();
//         return $this->handler->successResponse($getDeleteUsers,'Get soft delete Users successfully', 200); 

//     }

//     //comp
//     public function restoreUser($user_id){
//         try{
//             $restoreUser=$this->userService->restoreUser($user_id);
//             return $this->handler->successResponse($restoreUser,'restore soft delete User successfully', 200); 
//         }catch(Exception $e){
//             return $this->handler->errorResponse(
//                 false,
//                 $e->getMessage(),
//                 null
//             ,401);
//         }
//     }
    
//     // comp
//     public function deleteUser($user_id){
//         try{
//             $deleteUser=$this->userService->deleteUser($user_id);
//             return $this->handler->successResponse($deleteUser,' delete User successfully', 200); 
//         }catch(Exception $e){
//             return $this->handler->errorResponse(
//                 false,
//                 $e->getMessage(),
//                 null
//             ,401);
//         }
//     }

//     //is comp if it used
//     public function getAllUser(){
//         $getAllUser=$this->userService->getAllUser();
//         return $this->handler->successResponse($getAllUser,'get users successfully',200);
//     }

//         //not used yet
//     public function getAllImageNotAllow(){
//         $getImage=$this->userService->getAllImageNotAllow();
//         return $this->handler->successResponse(['images'=>$getImage],' get images of user successfully', 200); 
//     }

//     //not used yet
//     public function acceptImage($image_id){
//         $acceptImage=$this->userService->acceptImage($image_id);
//         return $this->handler->successResponse(['image'=>$acceptImage],' get image by id successfully', 200); 
//     }

//     public function getStatusableByIdOfStatus(GetStatusableByNameOfStatus $request){
//         try{
//             $data=$request->all();
//             $getStatusAble=$this->userService->getAllStatusNotAccepted($data);
//             $getAllStatusAble=new GetAllStatusAble($getStatusAble);
//             return $this->handler->successResponse($getAllStatusAble,' get all status able successfully', 200); 
//         }catch(Exception $e){
//             return $this->handler->errorResponse(false,$e->getMessage(),null,400); 
//         }
//     }

//     public function acceptStatusAble($id){
//         try{
//             $acceptStatusAble=$this->userService->acceptStatusAble($id);
//             return $this->handler->successResponse($acceptStatusAble,'Accepted  successfully', 200); 
//         }catch(Exception $e){
//             return $this->handler->errorResponse(false,$e->getMessage(),null,400); 
//         }
//     }

//     public function rejectStatusAble(WriteMessageForStatus $request,$id){
//         try{
//             $data=$request->all();
//             $rejectedStatusAble=$this->userService->rejectStatusAble($data,$id);
//             return $this->handler->successResponse($rejectedStatusAble,'Rejected  successfully', 203); 
//         }catch(Exception $e){
//             return $this->handler->errorResponse(false,$e->getMessage(),null,400); 
//         }
//     }

//     public function getUsersWithTrashedByIdOfRole($role_id){
//         try{
//             $role=$this->roleService->find_role_by_id($role_id);
//             $users=$this->roleService->getUsersWithTrashedByIdOfRole($role);
//             return $this->handler->successResponse($users,'get hall with trashed success',200);
//         }catch(Exception $e){
//             return $this->handler->errorResponse(false,$e->getMessage(),null,400); 
//         }
//     }
    
// }
