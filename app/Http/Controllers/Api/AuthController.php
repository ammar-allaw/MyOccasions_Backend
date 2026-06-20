<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\Handler;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\AddImageForServiceProvider;
use App\Http\Requests\Auth\LoginOwnerRequest;
use App\Http\Requests\Auth\LoginUserRequest;
use App\Http\Requests\Auth\RegisterUserRequest;
use App\Http\Resources\User\UserResource;
use App\Models\User;
use App\Services\Owner\OwnerService;
use App\Services\User\UserServiceInterface;
use Exception;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

use function Laravel\Prompts\error;

class AuthController extends Controller
{
 
    public $handler;
    private $userService;
    private $ownerService;

    public function __construct(Handler $handler,UserServiceInterface $userService
    ,OwnerService $ownerService)
    {
        $this->handler=$handler;
        $this->userService=$userService;
        $this->ownerService=$ownerService;

    }

    public function register(RegisterUserRequest $dataRequest)
    {
        try{
            $data = $dataRequest->validated();
            $client = $this->userService->createClient($data);
            $user = $this->userService->createUser($data, $client);
            $user->load('role');
            $dive_name = $dataRequest->post('dive_name', $dataRequest->userAgent()) ?? 'web';
            $userToken = $user->createToken($dive_name, ['*'], null);
            return $this->handler->successResponse(
                ['user' => new UserResource($user), 'token' => $userToken->plainTextToken],
                true,
                'success registration user',
                200);
        }catch(Exception $e){
            return $this->handler->errorResponse(
                false,
                $e->getMessage(),
                null
            ,400);
        }
    }

    public function login(LoginUserRequest $request){
        try{
            $dataRequest=$request->validated();
            // $user=User::where('phone_number','=',$dataRequest['phone_number'])->first();
            $user=$this->userService->findUserByPhoneNumber($dataRequest['phone_number']);
            if(!$user){
                return $this->handler->errorResponse(
                    false,
                    'User not found',
                    null,404);     
            }
            if(Hash::check($dataRequest['password'],$user->password)){
                $user->load('role');
                $role_name=$user->role->name_en;
                $dive_name = $request->post('dive_name',$request->userAgent('sanctum.expiration'));
                if($user->is_provider){
                    $userToken=$user->createToken($dive_name, ['*'], now()->addDays(7));
                }else {
                    $userToken=$user->createToken($dive_name, ['*'], null);
                }
                return $this->handler->successResponse(
                    ['user'=>new UserResource($user),'token'=>$userToken->plainTextToken],
                    true,
                    'success login',
                    200);
            }else{
                return $this->handler->errorResponse(
                false,
                'بيانات تسجيل الدخول غير صحيحة  ',
                null
            ,422); ;
            }
        }catch(Exception $e){
            return $this->handler->errorResponse(
                false,
                $e->getMessage(),
                null
            ,404);
        }
    }


    //login owner 
    public function loginOwner(LoginOwnerRequest $request){
        try{
            $dataRequest=$request->validated();
            $owner=$this->ownerService->findByEmail($dataRequest['email']);
            if($owner && Hash::check($dataRequest['password'],$owner->password)){
                $owner->load('role');
                $role_name=$owner->role->name_en;
                $dive_name = $request->post('dive_name',$request->userAgent('sanctum.expiration'));
                $userToken=$owner->createToken($dive_name,['*'], now()->addDays(1));
                return $this->handler->successResponse(
                    ['owner'=>$owner->makeHidden(['role']),'role_en'=>$role_name,'token'=>$userToken->plainTextToken],
                    true,
                    'success owner login',
                    200);
            }else{
                $e=new NotFoundHttpException ();
                return $this->handler->errorResponse(
                    false,
                    'Invalid credentials',
                    null
                ,401);
            }
        }catch(Exception $e){
            return $this->handler->errorResponse(
                false,
                $e->getMessage(),
                null
            ,401);
        }
    }

    public function resetPassword(Request $request)
    {
        try{
            $data=$request->validate([
                'phone_number'=>'required|exists:users,phone_number',
                'new_password'=>'required|string|confirmed',
            ]);
            $user=User::where('phone_number','=',$data['phone_number'])->first();
            if(!$user){
                return $this->handler->errorResponse(
                    false,
                    'User not found',
                    null,404);     
            }
            $user->password=Hash::make($data['new_password']);
            $user->save();
            return $this->handler->successResponse(
                true,
                'success change password',
                null,
                200,); 
        }catch(Exception $e){
            return $this->handler->errorResponse(
                false,
                $e->getMessage(),
                null
            ,400);
        }
    }

    public function changePassword(Request $request)
    {
        try{
            $data=$request->validate([
                'current_password'=>'required|string',
                'new_password'=>'required|string|confirmed',
            ]);
            $user=$request->user();
            if(!Hash::check($data['current_password'],$user->password)){
                throw new AuthenticationException('Current password is incorrect');
            }
            $user->password=Hash::make($data['new_password']);
            $user->save();
            return $this->handler->successResponse(
                true,
                'success change password',
                null,
                200,); 
        }catch(Exception $e){
            return $this->handler->errorResponse(
                false,
                $e->getMessage(),
                null
            ,400);
        }
    }

    public function logout(Request $request)
    {
        try{
            $user=$request->user();
            // حذف التوكن الحالي فقط
            $user->currentAccessToken()->delete();
            $user->tokens()->where('expires_at','<',now())->delete();
            return $this->handler->successResponse(
                true,
                'success logout',
                null,
                200,); 
        }catch(Exception $e){
            return $this->handler->errorResponse(
                false,
                $e->getMessage(),
                null
            ,400);
        }
    }

}
