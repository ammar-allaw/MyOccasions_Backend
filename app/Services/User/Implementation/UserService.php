<?php

namespace App\Services\User\Implementation;

use App\Exceptions\ApiResponseException;
use App\Exceptions\Handler;
use App\Http\Resources\Hall\HallSearchResource;
use App\Http\Resources\Services\SearchServiceResource;
use App\Http\Resources\User\UserResource;
use App\Models\Client;
use App\Models\User;
use App\Repositories\User\Interface\UserRepositoryInterface;
use App\Services\Auth\AuthService;
use App\Services\Service\Interface\ServiceServiceInterface;
use App\Services\User\Interface\UserServiceInterface;
use App\Traits\TracksChanges;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class UserService implements UserServiceInterface
{
    use TracksChanges;

    private const COVER_COLLECTION = 'cover_image_service_provider';

    private const YOUTUBE_COLLECTION = 'service_provider_link_youtube';

    private const COVER_ENABLED_ROLES = [
        'photographers',
        'aradas',
        'singers',
        'banquet coordinators',
    ];

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

    public function addServiceProviderByOwner(array $data): User
    {
        DB::beginTransaction();
        try {
            $serviceProvider = $this->userRepo->createServiceProvider($data);
            $user = $this->createUser($data, $serviceProvider);

            $underReviewStatus = $this->userRepo->findStatusByNameEn('under_review');
            if ($underReviewStatus) {
                \App\Models\OrderStatus::create([
                    'orderable_id' => $serviceProvider->id,
                    'orderable_type' => get_class($serviceProvider),
                    'status_id' => $underReviewStatus->id,
                    'change_description' => null,
                    'last_modified_at' => null,
                ]);
            }

            if (isset($data['user_type']) && ! empty($data['user_type'])) {
                $this->addTypesToServiceProvider($serviceProvider, $data['user_type']);
            }

            DB::commit();

            $user->load('userable.orderStatusAble.status');

            return $user;
        } catch (Exception $e) {
            DB::rollBack();

            throw $e;
        }
    }

    public function getServiceProviderDetails($user, $userId = null): User
    {
        if ($user && $user->is_provider == true) {
            $user->load('userable');
        } else {
            if ((Auth::guard('owner')->check() ||
                (Auth::guard('api')->check() && Auth::guard('api')->user()->is_provider == false))
                && $userId != null) {
                $user = $this->findUserById($userId);
                $user->load('userable');
            } else {
                throw new ApiResponseException('Unauthorized', 401, []);
            }
        }

        $user->loadMissing(['role.permissions', 'userPermissions']);

        return $user;
    }

    public function addImageForServiceProvider(array $data, Request $request, $userId = null): array
    {
        $user = $this->resolveUserForServiceProviderMedia($userId);
        $serviceProvider = $user->userable;
        $isOwner = $userId != null && Auth::guard('owner')->check();

        DB::beginTransaction();
        try {
            $result = $this->syncServiceProviderProfileMedia(
                $serviceProvider,
                $user,
                $data,
                $request,
                $isOwner
            );

            $underReviewStatus = $this->userRepo->findStatusByNameEn('under_review');
            if ($underReviewStatus && $this->hasServiceProviderMediaChanges($request, $data)) {
                $this->userRepo->syncOrderStatusForImageUpdate($serviceProvider, $underReviewStatus);
            }

            DB::commit();

            return $result;
        } catch (Exception $e) {
            DB::rollBack();

            throw $e;
        }
    }

    public function updateServiceProviderFromRequest(array $data, Request $request, $serviceProviderId = null): User
    {
        $isOwner = Auth::guard('owner')->check();
        if ($serviceProviderId != null && $isOwner) {
            $user = $this->findServiceProviderById($serviceProviderId);
        } else {
            $user = $this->authService->authUser();
        }

        $serviceProvider = $user->userable;
        $serviceProvider->load('orderStatusAble');

        DB::beginTransaction();
        try {
            if (isset($data['status_id'])) {
                if (! $isOwner) {
                    DB::rollBack();
                    throw new ApiResponseException(
                        'Only owner can update service provider status',
                        403,
                        null
                    );
                }

                if ($serviceProvider->orderStatusAble) {
                    $serviceProvider->orderStatusAble->status_id = $data['status_id'];

                    if ($data['status_id'] == 2 && isset($data['rejection_reason'])) {
                        $serviceProvider->orderStatusAble->rejection_reason = $data['rejection_reason'];
                    } else {
                        $serviceProvider->orderStatusAble->rejection_reason = null;
                    }

                    $serviceProvider->orderStatusAble->save();
                }
            }

            if (! $isOwner) {
                $originalServiceProvider = $this->userRepo->findServiceProviderModelById($serviceProvider->id);
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

            if (isset($data['type_id']) || isset($data['delete_type_id'])) {
                if (! $isOwner) {
                    DB::rollBack();
                    throw new ApiResponseException(
                        'Only owner can update service provider types',
                        403,
                        null
                    );
                }

                if (isset($data['type_id'])) {
                    $typeIds = is_array($data['type_id']) ? $data['type_id'] : [$data['type_id']];
                    foreach ($typeIds as $typeId) {
                        if (! $this->userRepo->typeBelongsToProviderRole($serviceProvider, $typeId)) {
                            DB::rollBack();
                            throw new ApiResponseException(
                                'Type does not belong to this service provider role',
                                422,
                                null
                            );
                        }
                    }
                    $this->addTypesToServiceProvider($serviceProvider, $typeIds);
                }

                if (isset($data['delete_type_id'])) {
                    $deleteTypeIds = is_array($data['delete_type_id']) ? $data['delete_type_id'] : [$data['delete_type_id']];
                    foreach ($deleteTypeIds as $typeId) {
                        if (! $this->userRepo->typeBelongsToProviderRole($serviceProvider, $typeId)) {
                            DB::rollBack();
                            throw new ApiResponseException(
                                'Type does not belong to this service provider role',
                                422,
                                null
                            );
                        }
                    }
                    $this->removeTypesFromServiceProvider($serviceProvider, $deleteTypeIds);
                }
            }

            $updatedUser = $this->updateServiceProvider($serviceProvider, $data);
            $updatedUser->load('userable.orderStatusAble.status');

            if ($this->hasServiceProviderMediaChanges($request, $data)) {
                $this->syncServiceProviderProfileMedia(
                    $serviceProvider,
                    $user,
                    $data,
                    $request,
                    $isOwner
                );

                $underReviewStatus = $this->userRepo->findStatusByNameEn('under_review');
                if ($underReviewStatus) {
                    $this->userRepo->syncOrderStatusForImageUpdate($serviceProvider, $underReviewStatus);
                }

                $updatedUser->refresh();
            }

            DB::commit();

            return $updatedUser;
        } catch (ApiResponseException $e) {
            throw $e;
        } catch (Exception $e) {
            DB::rollBack();

            throw $e;
        }
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

    private function resolveUserForServiceProviderMedia($userId = null): User
    {
        if ($userId != null && Auth::guard('owner')->check()) {
            return $this->findUserById($userId);
        }

        return $this->authService->authUser();
    }

    private function roleSupportsCoverMedia(?string $roleNameEn): bool
    {
        return in_array(strtolower(trim($roleNameEn ?? '')), self::COVER_ENABLED_ROLES, true);
    }

    private function getMaxProfileImages(User $user, bool $isOwner): int
    {
        if ($user->role->name_en === 'halls') {
            return $isOwner ? 3 : 2;
        }

        return 1;
    }

    private function getMaxCoverImages(User $user): int
    {
        return strtolower(trim($user->role->name_en ?? '')) === 'photographers' ? 8 : 5;
    }

    private function hasServiceProviderMediaChanges(Request $request, array $data): bool
    {
        return $request->hasFile('image')
            || isset($data['image_id'])
            || $request->hasFile('cover_image')
            || isset($data['cover_image_id'])
            || $request->filled('youtube_link');
    }

    private function syncServiceProviderProfileMedia(
        $serviceProvider,
        User $user,
        array $data,
        Request $request,
        bool $isOwner
    ): array {
        $result = [
            'images' => [],
            'cover_images' => [],
            'youtube' => null,
        ];

        if ($request->hasFile('image') || isset($data['image_id']) || ($data['replace_all'] ?? false)) {
            $result['images'] = $this->handler->manageImagesOnModel(
                $serviceProvider,
                'service_provider_image',
                $request->file('image'),
                $this->getMaxProfileImages($user, $isOwner),
                $data['replace_all'] ?? false,
                $data['image_id'] ?? null
            );
        }

        if ($request->hasFile('cover_image') || isset($data['cover_image_id']) || ($data['replace_all_cover'] ?? false)) {
            if (! $this->roleSupportsCoverMedia($user->role->name_en ?? null)) {
                throw new ApiResponseException(
                    'Cover images are only allowed for photographers, aradas, singers, and banquet coordinators',
                    422,
                    null
                );
            }

            $result['cover_images'] = $this->handler->manageCoverImagesOnModel(
                $serviceProvider,
                self::COVER_COLLECTION,
                $request->file('cover_image'),
                $this->getMaxCoverImages($user),
                $data['replace_all_cover'] ?? false,
                $data['cover_image_id'] ?? null
            );
        }

        if ($request->filled('youtube_link')) {
            if (! $this->roleSupportsCoverMedia($user->role->name_en ?? null)) {
                throw new ApiResponseException(
                    'YouTube link is only allowed for photographers, aradas, singers, and banquet coordinators',
                    422,
                    null
                );
            }

            $result['youtube'] = $this->handler->upsertYoutubeLinkOnModel(
                $serviceProvider,
                self::YOUTUBE_COLLECTION,
                $request->input('youtube_link'),
                isset($data['youtube_media_id']) ? (int) $data['youtube_media_id'] : null
            );
        }

        return $result;
    }

}
