<?php

// namespace App\Http\Controllers\User;

// use App\Exceptions\Handler;
// use App\Http\Controllers\Controller;
// use App\Http\Requests\Owner\OwnerLoginRequest;
// use App\Http\Requests\User\LoginRequest;
// use App\Models\Owner;
// use App\Models\User;
// use Exception;
// use Illuminate\Auth\AuthenticationException;
// use Illuminate\Http\Request;
// use Illuminate\Support\Facades\Auth;
// use Illuminate\Support\Facades\Config;
// use Illuminate\Support\Facades\Hash;

// class AuthController extends Controller
// {

//     ////////// this are finished

//     protected $handler;
//     public function __construct(Handler $handler)
//     {
//         $this->handler =$handler;
//     }
    
//     public function login(LoginRequest $request){
//         try{
//             $dataRequest=$request->all();
//             $user=User::where('phone_number','=',$dataRequest['phone_number'])->first();
//             if($user && Hash::check($dataRequest['password'],$user->password)){
//                 $role_name=$user->role->name;
//                 $dive_name = $request->post('dive_name',$request->userAgent('sanctum.expiration'));
//                 $userToken=$user->createToken($dive_name);
//                 return $this->handler->successResponse(
//                     ['token'=>$userToken->plainTextToken,'name'=>$user->name,'role'=>$role_name],
//                     'The user login successfully', 
//                     200,
//                     true);
//             }else{
//                 $e=new AuthenticationException ();
//                 return $this->handler->errorResponse(
//                     false,
//                     $e->getMessage(),
//                     null
//                 ,401);
//             }
//         }catch(Exception $e){
//             return $this->handler->errorResponse(
//                 false,
//                 $e->getMessage(),
//                 null
//             ,401);
//         }
//     }

//     public function ownerLogin(OwnerLoginRequest $request){
//         try{
//             $dataRequest=$request->all();
//             $owner=Owner::where('email','=',$dataRequest['email'])->first();
//             if($owner && Hash::check($dataRequest['password'],$owner->password)){
//                 $role_name=$owner->role->name;
//                 $dive_name = $request->post('dive_name',$request->userAgent('sanctum.expiration'));
//                 $userToken=$owner->createToken($dive_name);
//                 return $this->handler->successResponse(
//                     ['token'=>$userToken->plainTextToken,'name'=>$owner->name,'role'=>$role_name],
//                     'The user login successfully', 
//                     200,
//                     true);
//             }else{
//                 $e=new AuthenticationException ();
//                 return $this->handler->errorResponse(
//                     false,
//                     $e->getMessage(),
//                     null
//                 ,401);
//             }
//         }catch(Exception $e){
//             return $this->handler->errorResponse(
//                 false,
//                 $e->getMessage(),
//                 null
//             ,401);
//         }    
//     }

// }
