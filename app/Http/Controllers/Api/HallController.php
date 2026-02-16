<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\Handler;
use App\Http\Controllers\Controller;
use App\Http\Requests\Hall\AddRoomRequest;
use App\Http\Requests\Hall\AddServiceRequest;
use App\Http\Requests\Hall\UpdateRoomRequest;
use App\Http\Requests\Hall\UpdateServiceRequest;
use App\Http\Resources\Hall\RoomResource;
use App\Http\Resources\Hall\ServiceResource;
use App\Http\Resources\User\ServiceProvideResource;
use App\Http\Resources\User\UserResource;
use App\Models\Room;
use App\Models\Service;
use App\Models\User;
use App\Services\Auth\AuthService;
use App\Services\ServiceProvider\ServiceProviderServiceInterface;
use App\Services\User\UserServiceInterface;
use App\Traits\TracksChanges;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
//this controller for hall الصالات
class HallController extends Controller
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
    public function addRoom(AddRoomRequest $request, $serviceProviderId = null)
    {
        DB::beginTransaction();
        try {
            $data = $request->validated();
            $isOwner = Auth::guard('owner')->check();
            // إذا كان owner وبعت serviceProviderId
            if ($isOwner && $serviceProviderId) {
                $user = $this->userService->findUserById($serviceProviderId);
                
                // التأكد من أن المستخدم هو hall
                if ($user->role->name_en !== 'halls') {
                    return $this->handler->errorResponse(
                        false,
                        'The specified user is not a hall',
                        null,
                        400
                    );
                }
                
                $hall = $user->userable;
            } else {
                // إذا كان hall مصادق عليه
                $authUser = $this->authService->authUser();
                $hall = $this->authService->userable($authUser);
            }

            $room = $this->serviceProviderService->addRoom($data, $hall);
            
            // إضافة حالة under_review للـ room الجديد
            $underReviewStatus = \App\Models\Status::where('name_en', 'under_review')->first();
            if ($underReviewStatus) {
                \App\Models\OrderStatus::create([
                    'orderable_id' => $room->id,
                    'orderable_type' => get_class($room),
                    'status_id' => $underReviewStatus->id,
                    'change_description' => null,
                    'last_modified_at' => null,
                ]);
            }
            
            $room->load('orderStatusAble');

            $currentImageCount = $room->getMedia('room_image')->count();
            $maxAllowedImages = 4;

            $this->handler->attachImagesToModel(
                $room,
                $request->file('image'),
                'room_image',
                $currentImageCount,
                $maxAllowedImages
            );

            $room->refresh();

            DB::commit();

            return $this->handler->successResponse(
                true,
                'success add room',
                ['room' => new RoomResource($room)],
                201
            );
        } catch (Exception $e) {
            DB::rollBack();
            return $this->handler->errorResponse(
                false,
                $e->getMessage(),
                null,
                400
            );
        }
    }
    
    // for dashboard of hall auth
    //not used now
    public function getRoom()
    {
        $user=$this->authService->authUser();
        $hall=$this->authService->userable($user);
        $rooms=$this->serviceProviderService->getRoom($hall);
        return $this->handler->successResponse(
                true,
                'success add room',
                ['rooms' => RoomResource::collection($rooms)],
                200);
    }


    //
    public function updateRoom(UpdateRoomRequest $request,$roomId)
    {
        try{
            $data=$request->validated();
            $isOwner = Auth::guard('owner')->check();
            
            if (isset($data['status_id'])) {
                if (!$isOwner) {
                    return $this->handler->errorResponse(
                        false,
                        'Only owner can update room status',
                        null,
                        403
                    );
                }
                $room = $this->serviceProviderService->findRoom($roomId);
                $room->load('orderStatusAble');
                if ($room && $room->orderStatusAble) {
                    $room->orderStatusAble->status_id = $data['status_id'];
                    
                    // إضافة rejection_reason إذا كانت الحالة rejected (status_id = 2)
                    if ($data['status_id'] == 2 && isset($data['rejection_reason'])) {
                        $room->orderStatusAble->rejection_reason = $data['rejection_reason'];
                    } else {
                        $room->orderStatusAble->rejection_reason = null;
                    }
                    
                    $room->orderStatusAble->save();
                }
            }
            
            // إذا كان owner، نبحث عن الـ room مباشرة
            if ($isOwner) {
                $room = $this->serviceProviderService->findRoom($roomId);
            } else {
                $authUser=$this->authService->authUser();
                $hall = $this->authService->userable($authUser);
                $room = $this->serviceProviderService->findRoomForHall($hall, $roomId);
                
                // تتبع التغييرات للـ hall فقط
                $originalRoom = $this->getOriginalModel(Room::class, $roomId);
                $this->trackChangesAndUpdateStatus($originalRoom, $data, $request, [
                    'name' => 'الاسم',
                    'name_en' => 'الاسم الإنجليزي',
                    'description' => 'الوصف',
                    'description_en' => 'الوصف الإنجليزي',
                    'capacity' => 'السعة',
                ]);
            }
            
            $this->serviceProviderService->updateRoom($data,$room);
            $room->refresh();
            
            // إدارة الصور باستخدام manageImagesOnModel
            $this->handler->manageImagesOnModel(
                $room,
                'room_image',
                $request->file('image'),
                4,
                $data['replace_all'] ?? false,
                $data['image_id'] ?? null
            );
            $room->refresh();
            $room->load('orderStatusAble.status','media');
            
            return $this->handler->successResponse(
                    true,
                    'success update room',
                    ['room' => new RoomResource($room)],
                    201);
            
        }catch(Exception $e)
        {
            return $this->handler->errorResponse(
                false,
                $e->getMessage(),
                null
            ,400);
        }
    }

    public function getRoomsByHallId($hallId)
    {
        try{
            $user=$this->userService->findUserById($hallId);
            $user->load('userable');
            $hall=$user->userable;
            $rooms=$this->serviceProviderService->getRoomsByHallId($hall);
            return $this->handler->successResponse(
                        true,
                        'success get rooms',
                        ['room' =>RoomResource::collection($rooms)], // <--- single resource, not collection
                        201);
        }catch(Exception $e)
        {
            return $this->handler->errorResponse(
                false,
                $e->getMessage(),
                null
            ,400);
        }

    }

    public function getDetailsOfHall($userId = null)
    {
        $authUser=$this->authService->authUser();
        // إذا لم يكن موجود في api، نحاول guard owner
        if (!$authUser) {
            if(Auth::guard('owner')->check())
            {
                $authUser = Auth::guard('owner')->user();

            }
        }
        
        if (!$authUser) {
            return $this->handler->errorResponse(
                false,
                'Unauthorized',
                null,
                401
            );
        }
        
        $authUser->load('role');
        $isClient = $authUser->role->name_en === 'client';
        
        if ($authUser->role->name_en === 'halls') {
            $user = $authUser;
        } else {
            // إذا لم يكن hall، نحتاج userId
            if (!$userId) {
                return $this->handler->errorResponse(
                    false,
                    'Hall ID is required',
                    null,
                    400
                );
            }
            $user = $this->userService->findUserById($userId);
            if($user->role->name_en != 'halls') {
                return $this->handler->errorResponse(
                    false,
                    'The specified user is not a hall',
                    null,
                    400
                );
            }

        }
        
        $user->load('userable');
        
        $hall = $user->userable;
        
        // إذا كان client، نرجع فقط الصالات والقاعات والخدمات المقبولة
        if ($isClient) {
            // الحصول على status_id للحالة accepted
            $acceptedStatus = \App\Models\Status::where('name_en', 'accepted')->first();
            
            if ($acceptedStatus) {
                $hall->load([
                    'orderStatusAble.status',
                    'rooms' => function($query) use ($acceptedStatus) {
                        $query->whereHas('orderStatusAble', function($q) use ($acceptedStatus) {
                            $q->where('status_id', $acceptedStatus->id);
                        });
                    },
                    'rooms.orderStatusAble.status',
                    'rooms.services' => function($query) use ($acceptedStatus) {
                        $query->whereHas('orderStatusAble', function($q) use ($acceptedStatus) {
                            $q->where('status_id', $acceptedStatus->id);
                        });
                    },
                    'rooms.services.orderStatusAble.status',
                    'rooms.services.media',
                    'rooms.media',
                    'services' => function($query) use ($acceptedStatus) {
                        $query->whereHas('orderStatusAble', function($q) use ($acceptedStatus) {
                            $q->where('status_id', $acceptedStatus->id);
                        });
                    },
                    'services.orderStatusAble.status',
                    'services.media',
                    'media',
                ]);
                
                // التحقق من أن الصالة نفسها مقبولة
                if (!$hall->orderStatusAble || $hall->orderStatusAble->status_id != $acceptedStatus->id) {
                    return $this->handler->errorResponse(
                        false,
                        'This hall is not accepted yet',
                        null,
                        403
                    );
                }
            }
        } else {
            // للـ owner والـ hall نرجع كل شيء
            $hall->load([
                'orderStatusAble.status',
                'rooms.orderStatusAble.status',
                'rooms.services.orderStatusAble.status',
                'rooms.services.media',
                'rooms.media',
                'services.orderStatusAble.status',
                'services.media',
                'media',
            ]);
        }
        
        return $this->handler->successResponse(
            true,
            'get details of hall',
            ['hall' => new ServiceProvideResource($hall)],
            200
        );
    }

    //for service 
    public function addService(AddServiceRequest $request, $serviceProviderId = null)
    {
        try {
            $data = $request->validated();
            $isOwner = Auth::guard('owner')->check();
            // إذا كان owner وبعت serviceProviderId
            if ($isOwner) {
                if(!$serviceProviderId) {
                    return $this->handler->errorResponse(
                        false,
                        'Service provider ID is required',
                        null,
                        400
                    );
                }
                $user = $this->userService->findUserById($serviceProviderId);
                
                $serviceProvider = $user->userable;
            } else {
                // إذا كان hall مصادق عليه
                $user = $this->authService->authUser();
                if(!$user->is_provider=true)
                {
                    return $this->handler->errorResponse(
                        false,
                        'Only service providers can add services',
                        null,
                        403
                    );
                }
                $serviceProvider = $this->authService->userable($user);
            }

          $services = $this->serviceProviderService->addService($data,$serviceProvider);
          
          $services->load('orderStatusAble');

        //   $services = is_array($services) ? $services : [$services];
            // dd($services);
            // foreach ($services as $service) {
                // 1. الصورة الرسمية للخدمة (Cover Image) - صورة واحدة للجميع
                $currentImageCount = $services->getMedia('service_image')->count();
                $maxAllowedImages = 1;
                $this->handler->attachImagesToModel(
                    $services,
                    $request->file('image'),
                    'service_image',
                    $currentImageCount,
                    $maxAllowedImages
                );

                // 2. منطق خاص للمصورين ومنسقي الحفلات (Gallery OR Promo Video)
                // الاعتماد على Role الخاص بالمستخدم
                $userRole = $user->role->name_en ?? '';
                
                $acceptedRoles = ['photographers', 'banquet coordinator', 'coordinator', 'planner', 'owner'];
                
                $isVisualProvider = false;
                foreach ($acceptedRoles as $role) {
                    if (str_contains($userRole, $role)) {
                        $isVisualProvider = true;
                        break;
                    }
                }

                if ($isVisualProvider || $isOwner) {
                    // 1. YouTube Link (youtube_link)
                    if ($request->filled('youtube_link')) {
                        // Create youtube link in media
                        $services->media()->create([
                            'collection_name' => 'service_link_youtube',
                            'name' => 'youtube_link',
                            'file_name' => 'youtube_link', 
                            'disk' => 'public', 
                            'size' => 0,
                            'manipulations' => [],
                            'custom_properties' => [],
                            'generated_conversions' => [],
                            'responsive_images' => [],
                            'mime_type' => 'text/url',
                            'youtube_link' => $request->youtube_link
                        ]);
                    }
                    // 2. Gallery (gallery)
                    elseif ($request->hasFile('gallery')) {
                        $currentGalleryCount = $services->getMedia('gallery')->count();
                        $this->handler->attachImagesToModel(
                            $services,
                            $request->file('gallery'),
                            'gallery',
                            $currentGalleryCount,
                            8 
                        );
                    }
                }
          
                $services->refresh();
            // }

            return $this->handler->successResponse(
                true,
                'success add service',
                ['services' => new ServiceResource($services)],
                201
            );
        } catch (\Exception $e) {
            return $this->handler->errorResponse(false, $e->getMessage(), null, 400);
        }
    }


    public function updateService(UpdateServiceRequest $request,$serviceId)
    {
        try {
            $data = $request->validated();
            $isOwner = Auth::guard('owner')->check();

            // التحقق من صلاحية تعديل الـ status
            if (isset($data['status_id'])) {
                if (!$isOwner) {
                    return $this->handler->errorResponse(
                        false,
                        'Only owner can update service status',
                        null,
                        403
                    );
                }
                
                $service = $this->serviceProviderService->findService($serviceId);
                $service->load('orderStatusAble');
                if ($service->orderStatusAble) {
                    $service->orderStatusAble->status_id = $data['status_id'];
                    
                    // إضافة rejection_reason إذا كانت الحالة rejected (status_id = 2)
                    if ($data['status_id'] == 2 && isset($data['rejection_reason'])) {
                        $service->orderStatusAble->rejection_reason = $data['rejection_reason'];
                    } else {
                        $service->orderStatusAble->rejection_reason = null;
                    }
                    
                    $service->orderStatusAble->save();
                }
            }
            
            // إذا كان owner، نبحث عن الـ service مباشرة
            if ($isOwner) {
                $service = $this->serviceProviderService->findService($serviceId);
            } else {
                // إذا كان hall، نتحقق من أن الـ service يخصه
                $authUser = $this->authService->authUser();
                $hall = $this->authService->userable($authUser);
                $service = $this->serviceProviderService->findService($serviceId);
                $service->load(['serviceable', 'media']);
                $this->serviceProviderService->checkServiceable($service, $hall);
                // تتبع التغييرات للـ hall فقط
                $originalService = $this->getOriginalModel(Service::class, $serviceId);
                $this->trackChangesAndUpdateStatus($originalService, $data, $request, [
                    'name' => 'الاسم',
                    'name_en' => 'الاسم الإنجليزي',
                    'description' => 'الوصف',
                    'description_en' => 'الوصف الإنجليزي',
                ]);
            }
            
            $this->serviceProviderService->updateService($data, $service);
            $service->refresh();
            
            // إدارة الصور باستخدام manageImagesOnModel للصور الأساسية (service_image)
            $this->handler->manageImagesOnModel(
                $service,
                'service_image',
                $request->file('image'),
                1,
                $data['replace_all'] ?? false,
                $data['image_id'] ?? null
            );

            // --- تحديث: ميزات خاصة للمصورين ومنسقي الحفلات ---
            $serviceProvider = $service->serviceable;
            if ($serviceProvider instanceof \App\Models\ServiceProvider) {
                // الاعتماد على Role للمستخدم المرتبط بمزود الخدمة
                $providerUser = $serviceProvider->user;
                if ($providerUser) {
                    $userRole = $providerUser->role->name_en ?? '';
                    $acceptedRoles = ['photographers', 'banquet coordinator', 'coordinator', 'planner', 'owner'];
                    
                    $isVisualProvider = false;
                    foreach ($acceptedRoles as $role) {
                        if (str_contains($userRole, $role)) {
                            $isVisualProvider = true;
                            break;
                        }
                    }

                    if ($isVisualProvider || $isOwner) {
                        // Check existing media types
                        $hasGallery = $service->getMedia('gallery')->count() > 0;
                        $hasYoutubeLink = $service->getMedia('service_link_youtube')->count() > 0;

                        // 1. YouTube Link Request
                        if ($request->filled('youtube_link')) {
                            // Cannot add youtube link if gallery exists
                            if ($hasGallery) {
                                return $this->handler->errorResponse(
                                    false,
                                    'Cannot add YouTube link because this service has a gallery. Please delete gallery images first.',
                                    null,
                                    400
                                );
                            }

                            // Update/Set YouTube Link
                            $service->clearMediaCollection('service_link_youtube');
                            $service->media()->create([
                                'collection_name' => 'service_link_youtube',
                                'name' => 'youtube_link',
                                'file_name' => 'youtube_link', 
                                'disk' => 'public', 
                                'size' => 0,
                                'manipulations' => [],
                                'custom_properties' => [],
                                'generated_conversions' => [],
                                'responsive_images' => [],
                                'mime_type' => 'text/url',
                                'youtube_link' => $request->youtube_link
                            ]);
                        }
                        // 2. Gallery Request (Add/Edit/Delete)
                        elseif ($request->hasFile('gallery') || $request->input('deleted_gallery_ids')) {
                            // Cannot manage gallery if youtube link exists
                            if ($hasYoutubeLink) {
                                return $this->handler->errorResponse(
                                    false,
                                    'Cannot add or edit gallery because this service has a YouTube link. Please remove the YouTube link first.',
                                    null,
                                    400
                                );
                            }

                            $this->handler->manageImagesOnModel(
                                $service,
                                'gallery',
                                $request->file('gallery'),
                                8, // الحد الأقصى 8 صور
                                $request->boolean('replace_all_gallery'),
                                $request->input('deleted_gallery_ids') // مصفوفة الـ IDs المراد حذفها
                            );
                        }
                    }
                }
            }
            // ----------------------------------------------------

            $service->refresh();
            $service->load(['orderStatusAble.status', 'media']);

            return $this->handler->successResponse(
                true,
                'success update service',
                ['service' => new ServiceResource($service)],
                201
            );
        } catch (\Exception $e) {
            return $this->handler->errorResponse(false, $e->getMessage(), null, 400);
        }
    }

    public function deleteService($serviceId)
    {
        try {
            if (Auth::guard('owner')->check()) {
                $service = $this->serviceProviderService->findService($serviceId);
            } else {
                $authUser = $this->authService->authUser();
                $hall = $this->authService->userable($authUser);
                $service = $this->serviceProviderService->findService($serviceId);
                $service->load(['serviceable', 'media']);
                $this->serviceProviderService->checkServiceable($service, $hall);
            }
            
            $this->serviceProviderService->deleteService($service);
            return $this->handler->successResponse(
                true,
                'success delete service',
                null,
                200
            );
        } catch (\Exception $e) {
            return $this->handler->errorResponse(false, $e->getMessage(), null, 400);
        }
    }

    public function deleteRoom($roomId)
    {
        try {
            if (Auth::guard('owner')->check()) {
                $room = $this->serviceProviderService->findRoom($roomId);
            } else {
                $authUser=$this->authService->authUser();
                $hall = $this->authService->userable($authUser);
                $room = $this->serviceProviderService->findRoomForHall($hall, $roomId);
            }
            $room->load(['media']);
            $this->serviceProviderService->deleteRoom($room);
            return $this->handler->successResponse(
                true,
                'success delete room',
                null,
                200
            );
        } catch (\Exception $e) {
            return $this->handler->errorResponse(false, $e->getMessage(), null, 400);
        }
    }
}
