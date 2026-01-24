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

          $services = $this->serviceProviderService->addService($data,$hall);
          
          // إضافة حالة under_review للـ service الجديد
          $underReviewStatus = \App\Models\Status::where('name_en', 'under_review')->first();
          if ($underReviewStatus) {
              \App\Models\OrderStatus::create([
                  'orderable_id' => $services->id,
                  'orderable_type' => get_class($services),
                  'status_id' => $underReviewStatus->id,
                  'change_description' => null,
                  'last_modified_at' => null,
              ]);
          }
          
          $services->load('orderStatusAble');

        //   $services = is_array($services) ? $services : [$services];
            // dd($services);
            // foreach ($services as $service) {
                $currentImageCount = $services->getMedia('service_image')->count();
                $maxAllowedImages = 1;

                $this->handler->attachImagesToModel(
                    $services,
                    $request->file('image'),
                    'service_image',
                    $currentImageCount,
                    $maxAllowedImages
                );
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
            
            // إدارة الصور باستخدام manageImagesOnModel
            $this->handler->manageImagesOnModel(
                $service,
                'service_image',
                $request->file('image'),
                1,
                $data['replace_all'] ?? false,
                $data['image_id'] ?? null
            );
            $service->refresh();
            $service->load('orderStatusAble.status');

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
