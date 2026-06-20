<?php

namespace App\Services\User;

use App\Exceptions\Handler;
use App\Http\Resources\Hall\HallSearchResource;
use App\Http\Resources\Services\SearchServiceResource;
use App\Http\Resources\User\UserResource;
use App\Models\Client;
use App\Models\Permission;
use App\Models\User;
use App\Repositories\User\UserRepositoryInterface;
use App\Services\Auth\AuthService;
use App\Services\Service\ServiceServiceInterface;
use Exception;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

use function Laravel\Prompts\error;

class UserService implements UserServiceInterface
{
    protected $userRepo;

    public function __construct(
        UserRepositoryInterface $userRepo,
        private Handler $handler,
        private AuthService $authService,
        private ServiceServiceInterface $serviceService,
    ) {
        $this->userRepo = $userRepo;
    }

    public function getAllServiceProvider()
    {
        $users=$this->userRepo->getAllServiceProvider();
        return $users;
    }
    public function findUserByPhoneNumber($phoneNumber) 
    {
        $user=$this->userRepo->findUserByPhoneNumber($phoneNumber);
        if(!$user)
        {
            throw new Exception('the user not found');
        }
        return $user;
    }

    public function getAllUser()
    {
        return $this->userRepo->getAllUser();
    }


    public function findUserById($id) 
    {
        $user=$this->userRepo->findUserById($id);
        if(!$user)
        {
            // throw new NotFoundHttpException('the user not found');
            throw new Exception('the user not found');
        }
        return $user;
        // if($user){
        //     return $user;
        // }
    }

    public function getServiceProviderById($serviceProviderId)
    {
        $serviceProvider=$this->userRepo->getServiceProviderById($serviceProviderId);
        if(!$serviceProvider)
        {
            throw new NotFoundHttpException('the service provider not found');
        }
        return $serviceProvider;
    }
    


    public function createUser(array $data,$userType)
    {
        $user=$this->userRepo->createUser($data);
        if ($userType instanceof Client) 
        {      
            $user->role_id=2;
            $user->is_provider=0;
        }else{
            $user->is_provider=1;
            $user->role_id=$data['role_id'];
        }
        $user->userable_id=$userType->id;
        $user->userable_type=get_class($userType);
        $user->save();
        return $user;
    }

    //this function not in interface
    public function createClient($data)
    {
        $client=$this->userRepo->createClient($data);
        return $client;
    }

    public function updateUser($id, array $data){}
    // public function softDeleteServiceProvider($servicePro)
    // {
    //     $this->userRepo->softDeleteServiceProvider($id);
    // }
    public function deleteUser($id){}

    public function getUserByRoleId($role, $filters = [])
    {
        $users = $this->userRepo->getUserByRoleId($role, $filters);
        $users->load(['userable', 'userPermissions', 'role.permissions']);
        return $users;
    }
    public function getUserByRoleIdForOwner($role)
    {
        $users=$this->userRepo->getUserByRoleIdForOwner($role);
        $users->load(['userable.orderStatusAble.status']);
        return $users;
    }

    public function findServiceProviderById($id)
    {
        $user=$this->userRepo->findServiceProviderById($id);
        if(!$user)
        {
            throw new NotFoundHttpException('the service provider not found');
        }
        return $user;
    }


    public function softDeleteServiceProvider($id)
    {
        $this->userRepo->softDeleteServiceProvider($id);
    }
    public function getServiceProviderWithTrashed()
    {
        return $this->userRepo->getServiceProviderWithTrashed();
    }
    public function findServiceProviderWithTrashedById($serviceProviderId)
    {
        $user = $this->userRepo->findServiceProviderWithTrashedById($serviceProviderId);
        if (!$user) {
            throw new NotFoundHttpException('the service provider not found');
        }
        return $user;
    }
    public function forceDeleteServiceProvider($serviceProvider)
    {
        return $this->userRepo->forceDeleteServiceProvider($serviceProvider);
    }

    public function updateServiceProvider($serviceProvider, array $data)
    {
        return $this->userRepo->updateServiceProvider($serviceProvider, $data);
    }

    public function addTypesToServiceProvider($serviceProvider, $types)
    {
        return $this->userRepo->addTypesToServiceProvider($serviceProvider, $types);
    }

    public function removeTypesFromServiceProvider($serviceProvider, $types)
    {
        return $this->userRepo->removeTypesFromServiceProvider($serviceProvider, $types);
    }

    public function getServiceProvidersByRoleIdForClient($user, $roleId): JsonResponse
    {
        $role_name = $user->role->name_en;
        if ($role_name != 'client') {
            return $this->handler->errorResponse(
                false,
                $this->message('unauthorized'),
                null,
                401
            );
        }

        $role = $this->authService->findRoleById($roleId);

        $filters = $this->buildClientBrowseFilters($user, $role);
        $perPage = $filters['per_page'];
        $isHall = $filters['_is_hall'];
        unset($filters['_is_hall']);

        if (! empty($filters['main_key_id'])) {
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

        $page = (int) request()->integer('page', 1);
        $paginated = $this->userRepo->paginateUsersByRoleIdForClient($role, $filters, $page, $perPage);

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

    private function buildClientBrowseFilters($user, $role): array
    {
        $filters = request()->all();

        $perPage = (int) request()->integer('per_page', 10);
        $filters['per_page'] = max(1, min(100, $perPage));

        if ($user && $user->userable_type === Client::class && empty($filters['government_id'])) {
            $filters['government_id'] = $user->userable->government_id ?? null;
        }

        $isHall = isset($role->name_en) && strtolower($role->name_en) === 'halls';
        $filters['_is_hall'] = $isHall;

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

        return $filters;
    }

    private function message(string $key): string
    {
        $locale = request()->header('Accept-Language', 'ar');

        $messages = [
            'ar' => [
                'unauthorized' => 'غير مصرح',
                'services_by_main_key_retrieved' => 'تم جلب الخدمات حسب المفتاح الرئيسي بنجاح',
                'service_providers_retrieved' => 'تم جلب مزودي الخدمة بنجاح',
            ],
            'en' => [
                'unauthorized' => 'Unauthorized',
                'services_by_main_key_retrieved' => 'success get services by main key',
                'service_providers_retrieved' => 'success get serviceProviders',
            ],
        ];

        return $messages[$locale === 'en' ? 'en' : 'ar'][$key] ?? $key;
    }

}
