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
            $userToken = $user->createToken($dive_name);
            return $this->handler->successResponse(
                true,
                'success registration',
                ['user' => new UserResource($user), 'token' => $userToken->plainTextToken],
                200,);
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
            // if(!$user){
            //     return $this->handler->errorResponse(
            //         false,
            //         'User not found',
            //         null,404);     
            // }
            if(Hash::check($dataRequest['password'],$user->password)){
                $user->load('role');
                $role_name=$user->role->name_en;
                $dive_name = $request->post('dive_name',$request->userAgent('sanctum.expiration'));
                $userToken=$user->createToken($dive_name);
                return $this->handler->successResponse(
                    true,
                    'success login',
                    // ['user'=>$user->makeHidden(['role']),'role_en'=>$role_name,'token'=>$userToken->plainTextToken],
                    ['user'=>new UserResource($user),'token'=>$userToken->plainTextToken],

                    200,);
            }else{
                $e=new AuthenticationException ();
                return $this->handler->errorResponse(
                    false,
                    $e->getMessage(),
                    null
                ,401);
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
                $userToken=$owner->createToken($dive_name);
                return $this->handler->successResponse(
                    true,
                    'success owner login',
                    ['owner'=>$owner->makeHidden(['role']),'role_en'=>$role_name,'token'=>$userToken->plainTextToken],
                    200,);
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


    // public function addImageForServiceProvider(AddImageForServiceProvider $request,$serviceProviderId)
    // {
    //     $data=$request->validated();
    //     $user=$this->userService->findUserById($serviceProviderId);
    //     $serviceProvider=$user->userable;
        
    //     // استخدام manageImagesOnModel بدلاً من attachImagesToModel
    //     $images = $this->handler->manageImagesOnModel(
    //         $serviceProvider,
    //         'service_provider_image',
    //         $request->file('image'),
    //         $maxAllowedImages = 3,
    //         $replaceAll = $request->input('replace_all', false),
    //         $replaceIds = $request->input('image_id', null)
    //     );
        
    //     // تحديث حالة الصالة إلى under_review عند إضافة أو تعديل الصور
    //     $underReviewStatus = \App\Models\Status::where('name_en', 'under_review')->first();
    //     if ($underReviewStatus) {
    //         $orderStatus = $serviceProvider->orderStatusAble;
    //         if ($orderStatus) {
    //             $orderStatus->update([
    //                 'status_id' => $underReviewStatus->id,
    //                 'change_description' => 'Service provider images updated',
    //                 'last_modified_at' => now(),
    //             ]);
    //         } else {
    //             // إنشاء OrderStatus جديد إذا لم يكن موجود
    //             \App\Models\OrderStatus::create([
    //                 'orderable_id' => $serviceProvider->id,
    //                 'orderable_type' => get_class($serviceProvider),
    //                 'status_id' => $underReviewStatus->id,
    //                 'change_description' => 'Service provider images updated',
    //                 'last_modified_at' => now(),
    //             ]);
    //         }
    //     }
        
    //     return $this->handler->successResponse(
    //                 true,
    //                 'success add image for service provider',
    //                 ['images'=>$images],
    //                 201,);            
    // }

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

}
