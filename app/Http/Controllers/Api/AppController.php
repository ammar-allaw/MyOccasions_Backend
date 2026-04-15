<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\Handler;
use App\Http\Controllers\Controller;
use App\Http\Resources\Hall\HallSearchResource;
use App\Http\Resources\Hall\ServiceResource;
use App\Http\Resources\Services\SearchServiceResource;
use App\Http\Resources\User\UserResource;
use App\Models\Client;
use App\Services\Auth\AuthService;
use App\Services\Service\ServiceServiceInterface;
use App\Services\ServiceProvider\ServiceProviderServiceInterface;
use App\Services\User\UserServiceInterface;
use Illuminate\Pagination\LengthAwarePaginator;

class AppController extends Controller
{

    public $handler;
    private $serviceProviderService;
    private $authService;
    private $userService;
    private $serviceService;

    public function __construct(
        Handler $handler,
        ServiceProviderServiceInterface $serviceProviderService,
        AuthService $authService,
        UserServiceInterface $userService,
        ServiceServiceInterface $serviceService
    ) {
        $this->handler = $handler;
        $this->serviceProviderService = $serviceProviderService;
        $this->authService = $authService;
        $this->userService = $userService;
        $this->serviceService = $serviceService;
    }

    public function getRoles()
    {
        $user = auth()->user();
        $role_name=$user->role->name_en;
        if($role_name!='client')
        {
            return $this->handler->errorResponse(
                    false,
                    'Unauthorized',
                    null,
                    401
                );
        }
        $roles=$this->authService->getRole();
        return $this->handler->successResponse(
                    $roles,
                    true,
                    'success get roles',
                    200);

    }

    //we should make api get service provider for flutter

    public function getServiceProvidersByRoleId($roleId)
    {
        $user = auth()->user();
        $role_name=$user->role->name_en;
        if($role_name!='client')
        {
            return $this->handler->errorResponse(
                    false,
                    'Unauthorized',
                    null,
                    401
                );
        }
        $role = $this->authService->findRoleById($roleId);

        $filters = request()->all();
        
        // Default government filter for Client
        if ($user && $user->userable_type === Client::class && empty($filters['government_id'])) {
             $filters['government_id'] = $user->userable->government_id ?? null;
        }

        $isHall = false;
        if (isset($role->name_en) && strtolower($role->name_en) === 'halls') {
             $isHall = true;
        }

        if ($isHall && empty($filters['main_key_id'])) {
             if (request()->has('price')) {
                 $price = request('price');
                 $op = request('price_operator', 'less');
                 if ($op === 'more') {
                     $filters['min_price'] = $price;
                 } else {
                     $filters['max_price'] = $price;
                 }
             }

             if (request()->has('capacity')) {
                 $cap = request('capacity');
                 $op = request('capacity_operator', 'less');
                 if ($op === 'more') {
                     $filters['min_capacity'] = $cap;
                 } else {
                     $filters['max_capacity'] = $cap;
                 }
             }
        }

        if (!empty($filters['main_key_id'])) {
            $paginator = $this->serviceService->filterServicesByMainKey($role, $filters);

            return $this->handler->successResponse(
                [
                    'services' => SearchServiceResource::collection($paginator),
                    'pagination' => [
                        'current_page' => $paginator->currentPage(),
                        'last_page' => $paginator->lastPage(),
                        'per_page' => $paginator->perPage(),
                        'total' => $paginator->total(),
                    ],
                ],
                true,
                'success get services by main key',
                200
            );
        }

        $collection = $this->userService->getUserByRoleId($role, $filters);
        $collection->loadMissing(['userPermissions', 'role.permissions']); // تحويل الـ Collection إلى Paginator يدويًا
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

        // Switch Resource based on filters/role
        $resourceClass = UserResource::class;
        if ($isHall && empty($filters['main_key_id']) && (request()->has('price') || request()->has('capacity'))) {
             $resourceClass = HallSearchResource::class;
        }

        return $this->handler->successResponse(
            [
                'serviceProviders' => $resourceClass::collection($paginated),
                'pagination' => [
                    'current_page' => $paginated->currentPage(),
                    'last_page' => $paginated->lastPage(),
                    'per_page' => $paginated->perPage(),
                    'total' => $paginated->total(),
                ],
            ],
            true,
            'success get serviceProviders',
            200
        );

    }
}
