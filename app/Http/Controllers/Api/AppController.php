<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\Handler;
use App\Http\Controllers\Controller;
use App\Http\Resources\Hall\HallSearchResource;
use App\Http\Resources\User\UserResource;
use App\Models\Client;
use App\Services\Auth\AuthService;
use App\Services\ServiceProvider\ServiceProviderServiceInterface;
use App\Services\User\UserServiceInterface;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class AppController extends Controller
{

    public $handler;
    private $ServiceProviderService;
    private $authService;
    private $userService;
    public function __construct(Handler $handler,ServiceProviderServiceInterface $ServiceProviderService
    ,AuthService $authService
    ,UserServiceInterface $userService){
        $this->handler=$handler;
        $this->ServiceProviderService=$ServiceProviderService;
        $this->authService=$authService;
        $this->userService=$userService;
    }

    public function getRoles()
    {
        $roles=$this->authService->getRole();
        return $this->handler->successResponse(
                    true,
                    'success get roles',
                    $roles, // <--- single resource, not collection
                    200);

    }

    //we should make api get service provider for flutter

    public function getServiceProvidersByRoleId($roleId)
    {
        $role = $this->authService->findRoleById($roleId);

        $filters = request()->all();
        
        // Default government filter for Client
        $user = auth()->user();
        if ($user && $user->userable_type === Client::class && empty($filters['government_id'])) {
             $filters['government_id'] = $user->userable->government_id ?? null;
        }

        // Logic for Hall special filters
        // Check if role is Hall (assuming name_en is 'hall' or 'Hall')
        $isHall = false;
        if (isset($role->name_en) && strtolower($role->name_en) === 'halls') {
             $isHall = true;
        }

        if ($isHall) {
             // Price Filter Logic
             if (request()->has('price')) {
                 $price = request('price');
                 // Expecting 'price_operator' to be 'more' or 'less'
                 $op = request('price_operator', 'less'); 
                 if ($op === 'more') {
                     $filters['min_price'] = $price;
                 } else { 
                     $filters['max_price'] = $price;
                 }
             }
             
             // Capacity Filter Logic
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

        $collection = $this->userService->getUserByRoleId($role, $filters);
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

        // Switch Resource based on filters/role
        $resourceClass = UserResource::class;
        if ($isHall && (request()->has('price') || request()->has('capacity'))) {
             $resourceClass = HallSearchResource::class;
        }

        return $this->handler->successResponse(
            true,
            'success get serviceProviders',
            [
                'serviceProviders' => $resourceClass::collection($paginated),
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
}
