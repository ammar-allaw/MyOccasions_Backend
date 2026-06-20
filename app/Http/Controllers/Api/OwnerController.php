<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\Handler;
use App\Http\Controllers\Controller;
use App\Http\Resources\User\UserResource;
use App\Services\Auth\AuthService;
use App\Services\User\Interface\UserServiceInterface;
use Exception;
use Illuminate\Http\Request;

class OwnerController extends Controller
{
    public $handler;
    private $userService;
    private $authService;
    public function __construct(Handler $handler, UserServiceInterface $userService, AuthService $authService)
    {
        $this->handler=$handler;
        $this->userService=$userService;
        $this->authService=$authService;
    }

    // public function getServiceProvidersByRoleIdForOwner($roleId = null)
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

    /**
     * Get role_permissions and user_permissions for a user.
     * - Owner: pass {userId} to get any user's permissions.
     * - Service provider (auth:api): omit {userId} to get own permissions.
     */
    public function getUserPermissions($userId = null)
    {
        if ($userId !== null) {
            $user = $this->userService->findUserById($userId);
        } else {
            $user = $this->authService->authUser();
            if (!$user) {
                return $this->handler->errorResponse(false, 'Unauthenticated', null, 401);
            }
        }

        // Eager load to prevent N+1
        $user->loadMissing(['userPermissions', 'role.permissions']);

        $rolePermissions = $user->role
            ? $user->role->permissions->map(fn($p) => [
                'id'      => $p->id,
                'name'    => $p->name,
                'allowed' => (bool) $p->pivot->allowed,
            ])->values()
            : collect([]);

        $userPermissions = $user->userPermissions->map(fn($p) => [
            'id'      => $p->id,
            'name'    => $p->name,
            'allowed' => (bool) $p->pivot->allowed,
        ])->values();

        return $this->handler->successResponse(
            [
                'role_permissions' => $rolePermissions,
                'user_permissions' => $userPermissions,
            ],
            true,
            'success get user permissions',
            200
        );
    }

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
                null,
                true,
                'success delete service provider',
                200);
    }

    public function getServiceProvidersWithTrashed()
    {
        $serviceProviders=$this->userService->getServiceProviderWithTrashed();
        return $this->handler->successResponse(
                ['service_providers'=>UserResource::collection($serviceProviders)],
                true,
                'success get service providers with trashed',
                200);
    }

    public function forceDeleteServiceProvider($serviceProviderId)
    {
        $serviceProvider=$this->userService->findServiceProviderWithTrashedById($serviceProviderId);
        $this->userService->forceDeleteServiceProvider($serviceProvider);
        return $this->handler->successResponse(
                null,
                true,
                'success force delete service provider',
                200);
    }
}
