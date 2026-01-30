<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\Handler;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\AddImageForServiceProvider;
use App\Http\Requests\Image\AddImageRequest;
use App\Http\Requests\ServiceProvider\UpdateServiceProviderRequest;
use App\Http\Resources\Image\GetImageUrlResource;
use App\Http\Resources\User\UserResource;
use App\Models\ServiceProvider;
use App\Services\Auth\AuthService;
use App\Services\ServiceProvider\ServiceProviderServiceInterface;
use App\Services\User\UserServiceInterface;
use App\Traits\TracksChanges;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

//this controller for service provider بشكل عام 
class ServiceProviderController extends Controller
{
    use TracksChanges;
    
    public $handler;
    private $serviceProviderService;
    private $authService;
    private $userService;
    public function __construct(Handler $handler,ServiceProviderServiceInterface $serviceProviderService
    ,AuthService $authService
    ,UserServiceInterface $userService){
        $this->handler=$handler;
        $this->serviceProviderService=$serviceProviderService;
        $this->authService=$authService;
        $this->userService=$userService;
    }


    public function addImageForServiceProvider(AddImageRequest $request,$userId=null)
    {
        $data=$request->validated();
        if($userId!=null && Auth::guard('owner')->check())
        {
            try {
                $user=$this->userService->findUserById($userId);
            } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
                return $this->handler->errorResponse(false, 'User not found', [], 404);
            }

            if($user->role->name_en==='halls')
            {
                $maxImages=2;
            }
            else{
                $maxImages=1;
            }
        }else{
            $user=$this->authService->authUser();
        }
        $serviceProvider=$user->userable;
        
        // للتأكد من استلام الصور بشكل صحيح
        
        try {
            $images = $this->handler->manageImagesOnModel(
                $serviceProvider,
                'service_provider_image',
                $request->file('image'),
                3,
                $data['replace_all'] ?? false,
                $data['image_id'] ?? null
            );
        } catch (\Exception $e) {
            return $this->handler->errorResponse(false, $e->getMessage(), [], 400); 
        }
        
        // تحديث حالة الصالة إلى under_review عند إضافة أو تعديل الصور
        $underReviewStatus = \App\Models\Status::where('name_en', 'under_review')->first();
        if ($underReviewStatus) {
            $orderStatus = $serviceProvider->orderStatusAble;
            if ($orderStatus) {
                $orderStatus->update([
                    'status_id' => $underReviewStatus->id,
                    'change_description' => 'Service provider images updated',
                    'last_modified_at' => now(),
                ]);
            } else {
                \App\Models\OrderStatus::create([
                    'orderable_id' => $serviceProvider->id,
                    'orderable_type' => get_class($serviceProvider),
                    'status_id' => $underReviewStatus->id,
                    'change_description' => 'Service provider images updated',
                    'last_modified_at' => now(),
                ]);
            }
        }
        
        return $this->handler->successResponse(
                    true,
                    'success add image for service provider',
                    ['images'=>GetImageUrlResource::collection($images)],
                    201,);            

    }

    public function updateServiceProvider(UpdateServiceProviderRequest $request, $serviceProviderId=null)
    {
        $data=$request->validated();
        $isOwner = Auth::guard('owner')->check();
        // الحصول على User object
        if($serviceProviderId != null &&  $isOwner)
        {
            $user = $this->userService->findServiceProviderById($serviceProviderId);
        } else {
            $user = $this->authService->authUser();
        }
        
        // تحديث ServiceProvider والحصول على User محدّث
        $serviceProvider = $user->userable;
        $serviceProvider->load('orderStatusAble');

        
        // معالجة status_id للـ owner فقط
        if (isset($data['status_id'])) {
            if (!$isOwner) {
                return $this->handler->errorResponse(
                    false,
                    'Only owner can update service provider status',
                    null,
                    403
                );
            }
            
            if ($serviceProvider->orderStatusAble) {
                $serviceProvider->orderStatusAble->status_id = $data['status_id'];
                
                // إضافة rejection_reason إذا كانت الحالة rejected (status_id = 2)
                if ($data['status_id'] == 2 && isset($data['rejection_reason'])) {
                    $serviceProvider->orderStatusAble->rejection_reason = $data['rejection_reason'];
                } else {
                    $serviceProvider->orderStatusAble->rejection_reason = null;
                }
                
                $serviceProvider->orderStatusAble->save();
            }
        }
        
        // تتبع التغييرات للـ hall فقط (ليس للـ owner)
        if (!$isOwner) {
            $originalServiceProvider = $this->getOriginalModel(ServiceProvider::class, $serviceProvider->id);
            $this->trackChangesAndUpdateStatus($originalServiceProvider, $data, $request, [
                'name' => 'الاسم',
                'name_en' => 'الاسم الإنجليزي',
                'description' => 'الوصف',
                'description_en' => 'الوصف الإنجليزي',
                'location' => 'الموقع',
                'location_en' => 'الموقع الإنجليزي',
                'address_url' => 'رابط الموقع',
            ]);
        }
        
        $updatedUser = $this->userService->updateServiceProvider($serviceProvider, $data);
        $updatedUser->load('userable.orderStatusAble.status');
        
        // إدارة الصور
        if ($request->hasFile('image') || isset($data['image_id'])) {
            $this->handler->manageImagesOnModel(
                $serviceProvider,
                'service_provider_image',
                $request->file('image'),
                3,
                $data['replace_all'] ?? false,
                $data['image_id'] ?? null
            );
            
            // تحديث حالة الصالة إلى under_review عند إضافة أو تعديل الصور
            $underReviewStatus = \App\Models\Status::where('name_en', 'under_review')->first();
            if ($underReviewStatus) {
                $orderStatus = $serviceProvider->orderStatusAble;
                if ($orderStatus) {
                    $orderStatus->update([
                        'status_id' => $underReviewStatus->id,
                        'change_description' => 'Service provider images updated',
                        'last_modified_at' => now(),
                    ]);
                } else {
                    \App\Models\OrderStatus::create([
                        'orderable_id' => $serviceProvider->id,
                        'orderable_type' => get_class($serviceProvider),
                        'status_id' => $underReviewStatus->id,
                        'change_description' => 'Service provider images updated',
                        'last_modified_at' => now(),
                    ]);
                }
            }
            
            $updatedUser->refresh();
        }
        
        return $this->handler->successResponse(
            true,
            'success update service provider',
            ['serviceProvider' => new UserResource($updatedUser)],
            200
        );
    }

}
