<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\Handler;
use App\Http\Controllers\Controller;
use App\Http\Requests\Owner\AddServiceProviderRequest;
use App\Http\Requests\Owner\UpdateRoomStatusRequest;
use App\Http\Requests\User\AddServiceRequest;
use App\Http\Resources\Hall\RoomResource;
use App\Http\Resources\User\UserResource;
use App\Services\Auth\AuthService;
use App\Services\Owner\OwnerService;
use App\Services\ServiceProvider\ServiceProviderServiceInterface;
use App\Services\User\UserServiceInterface;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class OwnerController extends Controller
{
    public $handler;
    private $userService;
    private $serviceProviderService;
    private $ownerService;
    private $authService;
    public function __construct(Handler $handler,UserServiceInterface $userService
    ,ServiceProviderServiceInterface $serviceProviderService,OwnerService $ownerService
    ,AuthService $authService)
    {
        $this->handler=$handler;
        $this->userService=$userService;
        $this->serviceProviderService=$serviceProviderService;
        $this->ownerService=$ownerService;
        $this->authService=$authService;
    }

    public function addServiceProvider(AddServiceProviderRequest $request)
    {
        try{   
            $data=$request->validated();
            $serviceProvider=$this->serviceProviderService->createServiceProvider($data);
            $user=$this->userService->createUser($data,$serviceProvider);
            
            // إضافة حالة under_review للـ service provider الجديد
            $underReviewStatus = \App\Models\Status::where('name_en', 'under_review')->first();
            if ($underReviewStatus) {
                \App\Models\OrderStatus::create([
                    'orderable_id' => $serviceProvider->id,
                    'orderable_type' => get_class($serviceProvider),
                    'status_id' => $underReviewStatus->id,
                    'change_description' => null,
                    'last_modified_at' => null,
                ]);
            }
            
            $user->load('userable.orderStatusAble.status');
            
            return $this->handler->successResponse(
                true,
                'success add service provider',
                ['user'=>new UserResource($user)],
                201,);
        }catch(Exception $e){
            return $this->handler->errorResponse(
                false,
                $e->getMessage(),
                null
            ,400);
        }
    }


    public function getRolesForOwner()
    {
        $roles=$this->ownerService->getRolesForOwner();
        return $this->handler->successResponse(
                true,
                'success get roles service provider',
                ['roles'=>$roles],
                200);   
    }

    // public function getServiceProvidersByRoleIdForOwner($roleId)
    // {
    //     $role = $this->authService->findRoleById($roleId);

    //     if($roleId){
    //         $query = $this->userService->getUserByRoleId($role);

    //     }else{
    //         $quary=$this->userService->getAllUser();
    //     }
    //     // يفضل أن هذه الدالة تُرجع query object حتى نقدر نطبق paginate

    //     // هنا نضيف pagination
    //     $perPage = request()->get('per_page', 10); // عدد النتائج في الصفحة (قابل للتعديل من الـ request)
    //     $serviceProviders = $query->paginate($perPage);

    //     return $this->handler->successResponse(
    //         true,
    //         'success get serviceProviders',
    //         [
    //             'serviceProviders' => UserResource::collection($serviceProviders),
    //             'pagination' => [
    //                 'current_page' => $serviceProviders->currentPage(),
    //                 'last_page' => $serviceProviders->lastPage(),
    //                 'per_page' => $serviceProviders->perPage(),
    //                 'total' => $serviceProviders->total(),
    //             ],
    //         ],
    //         200
    //     );
    // }


    public function getServiceProvidersByRoleIdForOwner($roleId = null)
    {
        if ($roleId) {
            $role = $this->authService->findRoleById($roleId);
            $collection = $this->userService->getUserByRoleIdForOwner($role);
        } else {
            $collection = $this->userService->getAllUser();
        }

        $collection = $collection->sortByDesc('id');

        // تحويل الـ Collection إلى Paginator يدويًا
        $perPage = request()->get('per_page', 10);
        $page = request()->get('page', 1);
        $offset = ($page - 1) * $perPage;
        $paginated = new LengthAwarePaginator(
            $collection->slice($offset, $perPage)->values(),
            $collection->count(),
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );

        return $this->handler->successResponse(
            true,
            'success get serviceProviders',
            [
                'serviceProviders' => UserResource::collection($paginated),
                'pagination' => [
                    'current_page' => $paginated->currentPage(),
                    'last_page' => $paginated->lastPage(),
                    'per_page' => $paginated->perPage(),
                    'total' => $paginated->total(),
                ],
            ],
            200
        );
    }

    // public function updateRoomStatus(UpdateRoomStatusRequest $request, $roomId)
    // {
    //     try {
    //         $data = $request->validated();
            
    //         // البحث عن الـ room
    //         $room = $this->serviceProviderService->findRoom($roomId);
    //         if (!$room) {
    //             return $this->handler->errorResponse(
    //                 false,
    //                 'Room not found',
    //                 null,
    //                 404
    //             );
    //         }

    //         // التحقق من وجود OrderStatus للـ room
    //         $orderStatus = $room->orderStatusAble;
            
    //         if (!$orderStatus) {
    //             return $this->handler->errorResponse(
    //                 false,
    //                 'No status record found for this room',
    //                 null,
    //                 404
    //             );
    //         }

    //         // تحديث الـ status
    //         $orderStatus->status_id = $data['status_id'];
    //         $orderStatus->save();

    //         $orderStatus->load('status');

    //         return $this->handler->successResponse(
    //             true,
    //             'Room status updated successfully',
    //             [
    //                 'order_status' => [
    //                     'id' => $orderStatus->id,
    //                     'status' => [
    //                         'id' => $orderStatus->status->id,
    //                         'name' => $orderStatus->status->name,
    //                         'name_en' => $orderStatus->status->name_en,
    //                     ],
    //                     'orderable_id' => $orderStatus->orderable_id,
    //                     'orderable_type' => $orderStatus->orderable_type,
    //                 ]
    //             ],
    //             200
    //         );
    //     } catch (Exception $e) {
    //         return $this->handler->errorResponse(
    //             false,
    //             $e->getMessage(),
    //             null,
    //             400
    //         );
    //     }
    // }


    //not used now by ammar
    // public function getAllRoomsWithStatus()
    // {
    //     try {
    //         // الحصول على جميع الـ rooms مع الـ status
    //         $rooms = $this->serviceProviderService->getAllRoomsWithStatus();

    //         return $this->handler->successResponse(
    //             true,
    //             'success get all rooms with status',
    //             ['rooms' => RoomResource::collection($rooms)],
    //             200
    //         );
    //     } catch (Exception $e) {
    //         return $this->handler->errorResponse(
    //             false,
    //             $e->getMessage(),
    //             null,
    //             400
    //         );
    //     }
    // }

    public function softDeleteServiceProvider($serviceProviderId)
    {
        $serviceProvider=$this->userService->findServiceProviderById($serviceProviderId);
        $serviceProvider=$this->userService->softDeleteServiceProvider($serviceProvider);
        return $this->handler->successResponse(
                true,
                'success delete service provider',
                null,
                200);
    }

    public function getServiceProvidersWithTrashed()
    {
        $serviceProviders=$this->userService->getServiceProviderWithTrashed();
        return $this->handler->successResponse(
                true,
                'success get service providers with trashed',
                ['service_providers'=>UserResource::collection($serviceProviders)],
                200);
    }

    public function forceDeleteServiceProvider($serviceProviderId)
    {
        $serviceProvider=$this->userService->findServiceProviderWithTrashedById($serviceProviderId);
        $this->userService->forceDeleteServiceProvider($serviceProvider);
        return $this->handler->successResponse(
                true,
                'success force delete service provider',
                null,
                200);
    }
}
