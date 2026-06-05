<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\Handler;
use App\Http\Controllers\Controller;
use App\Http\Resources\App\RoleResource;
use App\Http\Resources\Hall\HallSearchResource;
use App\Http\Resources\Hall\ServiceResource;
use App\Http\Resources\Services\SearchServiceResource;
use App\Http\Resources\Type\TypeResource;
use App\Http\Resources\User\UserResource;
use App\Models\Client;
use App\Models\Type;
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
                    $this->message('unauthorized'),
                    null,
                    401
                );
        }
        $roles=$this->authService->getRole();
        return $this->handler->successResponse(
                    RoleResource::collection($roles),
                    true,
                    $this->message('roles_retrieved'),
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
                    $this->message('unauthorized'),
                    null,
                    401
                );
        }
        $role = $this->authService->findRoleById($roleId);

        $filters = request()->all();

        $perPage = (int) request()->integer('per_page', 10);
        $perPage = max(1, min(100, $perPage));
        $filters['per_page'] = $perPage;
        
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
                $this->message('services_by_main_key_retrieved'),
                200
            );
        }

        $collection = $this->userService->getUserByRoleId($role, $filters);
        $collection->loadMissing(['userPermissions', 'role.permissions', 'userable.types']); // تحويل الـ Collection إلى Paginator يدويًا
        $page = (int) request()->integer('page', 1);
        $page = max(1, $page);
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
            $this->message('service_providers_retrieved'),
            200
        );

    }

    private function message(string $key): string
    {
        $locale = request()->header('Accept-Language', 'ar');

        $messages = [
            'ar' => [
                'unauthorized' => 'غير مصرح',
                'roles_retrieved' => 'تم جلب الأدوار بنجاح',
                'services_by_main_key_retrieved' => 'تم جلب الخدمات حسب المفتاح الرئيسي بنجاح',
                'service_providers_retrieved' => 'تم جلب مزودي الخدمة بنجاح',
            ],
            'en' => [
                'unauthorized' => 'Unauthorized',
                'roles_retrieved' => 'success get roles',
                'services_by_main_key_retrieved' => 'success get services by main key',
                'service_providers_retrieved' => 'success get serviceProviders',
            ],
        ];

        return $messages[$locale === 'en' ? 'en' : 'ar'][$key] ?? $key;
    }
}
