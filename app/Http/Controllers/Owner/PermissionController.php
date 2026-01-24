<?php

// namespace App\Http\Controllers\Owner;

// use App\Exceptions\Handler;
// use App\Http\Controllers\Controller;
// use App\Http\Requests\Owner\AddPermissionRequest;
// use App\Http\Requests\Owner\AddPermissionToRoleRequest;
// use App\Http\Requests\Owner\AddPermissionToUserRequest;
// use App\Models\Permission;
// use App\Services\Owner\PermissionService;
// use App\Services\Owner\RoleService;
// use App\Services\Owner\UserService;
// use Exception;
// use Illuminate\Validation\ValidationException;
// use Illuminate\Http\Request;
// use Illuminate\Support\Facades\Auth;

// class PermissionController extends Controller
// {
//     private $permissionService;
//     private $userService;
//     private $roleService;

//     protected $handler;
//     public function __construct(Handler $handler)
//     {
//         $this->permissionService= new PermissionService();
//         $this->userService = new UserService();
//         $this->roleService=new RoleService();
//         $this->handler =$handler;

//     }

//     public function addPermission(AddPermissionRequest $request){
//         try{

//             $data=$request->all();
//             $addPermission=$this->permissionService->create_permission($data);
//             return $this->handler->successResponse($addPermission,'Added Permission successfully', 201); 
//         }catch(Exception $e){
//             return $this->handler->errorResponse(
//                 false,
//                 $e->getMessage(),
//                 null
//             ,401);
//         }    
//     }

//     //for update
//     public function addPermissionForRole(AddPermissionToRoleRequest $request){
//         try{
//             $role_id=$request->role_id;
//             $permission_id=$request->permission_id;
//             $permission=$this->permissionService->find_permission_by_id($permission_id);
//             $role=$this->roleService->find_role_by_id($role_id);
//             if($role->checkRoleIfHasPermission($role,$permission->id)==true){
//                 $e= ValidationException::withMessages(['the Role has this permission']);
//                 return $this->handler->errorResponse(false,$e->getMessage(),null,422); 
//             //     throw ValidationException::withMessages([
//             //         'the Role has this permission ',
//             //    ])->status(422);
//             }
//             $data=[
//                 // 'role_id'=>$request->id,
//                 'permission_id'=>$permission_id,
//                 'allowed'=>true,
//             ];
//             $addPermissionToUser=$this->permissionService->add_permission_to_role($role,$data);
//             return $this->handler->successResponse($addPermissionToUser,'Added Permission to role successfully', 201); 
//         }catch(Exception $e){
//             return $this->handler->errorResponse(
//                 false,
//                 $e->getMessage(),
//                 null
//             ,401);
//         }    
//     }
// }
