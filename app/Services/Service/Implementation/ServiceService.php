<?php

namespace App\Services\Service\Implementation;

use App\Exceptions\ApiResponseException;
use App\Exceptions\Handler;
use App\Models\MainKey;
use App\Models\Service;
use App\Models\ServiceProvider;
use App\Models\Status;
use App\Repositories\Service\Interface\ServiceRepositoryInterface;
use App\Repositories\User\Interface\UserRepositoryInterface;
use App\Services\Auth\AuthService;
use App\Services\Room\Interface\RoomServiceInterface;
use App\Services\Service\Interface\ServiceServiceInterface;
use App\Traits\TracksChanges;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ServiceService implements ServiceServiceInterface
{
    use TracksChanges;

    public function __construct(
        private ServiceRepositoryInterface $serviceRepo,
        private Handler $handler,
        private AuthService $authService,
        private UserRepositoryInterface $userRepository,
        private RoomServiceInterface $roomService,
    ) {}

    public function getServicesForServiceProvider($serviceProvider = null)
    {
        return $this->serviceRepo->getServicesForServiceProvider($serviceProvider);
    }

    public function filterServicesByMainKey($role, array $filters = [])
    {
        if (! empty($filters['price'])) {
            $price = $filters['price'];
            $op = $filters['price_operator'] ?? 'less';
            if ($op === 'more') {
                $filters['service_min_price'] = $price;
            } else {
                $filters['service_max_price'] = $price;
            }
        }

        $query = $this->serviceRepo->getServicesByRoleAndMainKeyQuery($role, $filters);

        $perPage = $filters['per_page'] ?? 10;
        $page = $filters['page'] ?? 1;

        return $query->paginate($perPage, ['*'], 'page', $page);
    }

    public function addMainKey($data)
    {
        return $this->serviceRepo->addMainKey($data);
    }

    public function getMainKeys($data = [])
    {
        $roleId = null;
        $isClient = false;

        if (Auth::guard('owner')->check()) {
            if (isset($data['role_id'])) {
                $roleId = $data['role_id'];
            }
        } elseif (Auth::guard('api')->check()) {
            $user = Auth::guard('api')->user();

            if ($user->is_provider) {
                $roleId = $user->role_id;
            } else {
                $isClient = true;
                if (! isset($data['role_id'])) {
                    throw new Exception('role_id is required', 400);
                }
                $roleId = $data['role_id'];
            }
        }

        $mainKeys = $this->serviceRepo->getMainKeys($roleId);

        if ($isClient) {
            $mainKeys->transform(function ($item) use ($data) {
                if ($data['lang'] != 'ar') {
                    $item->key = $item->key_en;
                }
                unset($item->key_en);

                return $item;
            });
        }

        return $mainKeys;
    }

    public function findMainKeyById($id)
    {
        $mainKey = $this->serviceRepo->findMainKeyById($id);
        if (! $mainKey) {
            throw new Exception('Main Key not found', 404);
        }

        return $mainKey;
    }

    public function updateMainKey($data, $mainKey)
    {
        return $this->serviceRepo->updateMainKey($data, $mainKey);
    }

    public function deleteMainKey($mainKey)
    {
        $this->serviceRepo->deleteMainKey($mainKey);
    }

    public function syncServiceMainKeys($service, array $mainKeyIds, $serviceProvider = null): void
    {
        if (! empty($mainKeyIds)) {
            $existingCount = MainKey::whereIn('id', $mainKeyIds)->count();
            if ($existingCount !== count($mainKeyIds)) {
                throw new Exception('One or more main_key IDs are invalid.', 422);
            }

            if ($serviceProvider) {
                $serviceProvider->loadMissing('user');
                $userRoleId = $serviceProvider->user?->role_id;
                if ($userRoleId) {
                    $invalidCount = MainKey::whereIn('id', $mainKeyIds)
                        ->where('role_id', '!=', $userRoleId)
                        ->count();
                    if ($invalidCount > 0) {
                        throw new Exception('One or more main keys do not match the service provider role.', 422);
                    }
                }
            }
        }

        $this->serviceRepo->syncServiceMainKeys($service, $mainKeyIds);
    }

    public function addService(array $data, Request $request, $serviceProviderId = null)
    {
        $isOwner = Auth::guard('owner')->check();
        if ($isOwner) {
            if (! $serviceProviderId) {
                throw new ApiResponseException('Service provider ID is required', 400, null);
            }
            $user = $this->userRepository->findUserById($serviceProviderId);
            $serviceProvider = $user->userable;
        } else {
            $user = $this->authService->authUser();
            if (! $user->is_provider = true) {
                throw new ApiResponseException('Only service providers can add services', 403, null);
            }
            $serviceProvider = $this->authService->userable($user);
        }

        DB::beginTransaction();
        try {
            $services = $this->createServiceForProvider($data, $serviceProvider);

            $services->load('orderStatusAble');

            $currentImageCount = $services->getMedia('service_image')->count();
            $maxAllowedImages = 1;
            $this->handler->attachImagesToModel(
                $services,
                $request->file('image'),
                'service_image',
                $currentImageCount,
                $maxAllowedImages
            );

            $userRole = $user->role->name_en ?? '';
            if ($this->isVisualProvider($userRole) || $isOwner) {
                if ($request->filled('youtube_link')) {
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
                        'youtube_link' => $request->youtube_link,
                    ]);
                }

                if ($request->hasFile('gallery')) {
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

            if (! empty($data['main_key_ids'])) {
                $this->syncServiceMainKeys($services, $data['main_key_ids'], $serviceProvider);
            }

            $services->load('mainKeys');

            DB::commit();

            return $services;
        } catch (Exception $e) {
            DB::rollBack();

            throw $e;
        }
    }

    public function updateServiceFromRequest(array $data, Request $request, $serviceId): Service
    {
        $isOwner = Auth::guard('owner')->check();

        if (isset($data['status_id'])) {
            if (! $isOwner) {
                throw new ApiResponseException('Only owner can update service status', 403, null);
            }

            $service = $this->findService($serviceId);
            $service->load('orderStatusAble');
            if ($service->orderStatusAble) {
                $service->orderStatusAble->status_id = $data['status_id'];

                if ($data['status_id'] == 2 && isset($data['rejection_reason'])) {
                    $service->orderStatusAble->rejection_reason = $data['rejection_reason'];
                } else {
                    $service->orderStatusAble->rejection_reason = null;
                }

                $service->orderStatusAble->save();
            }
        }

        if ($isOwner) {
            $service = $this->findService($serviceId);
            $service->load('serviceable');
            $serviceProvider = $service->serviceable;
        } else {
            $authUser = $this->authService->authUser();
            $hall = $this->authService->userable($authUser);
            $service = $this->findService($serviceId);
            $service->load(['serviceable', 'media']);
            $serviceProvider = $service->serviceable;
            $this->checkServiceable($service, $hall);

            $originalService = $this->serviceRepo->findServiceModelById((int) $serviceId);
            $this->trackChangesAndUpdateStatus($originalService, $data, $request, [
                'name' => 'الاسم',
                'name_en' => 'الاسم الإنجليزي',
                'description' => 'الوصف',
                'description_en' => 'الوصف الإنجليزي',
            ]);
        }

        DB::beginTransaction();
        try {
            $this->serviceRepo->updateService($data, $service);
            $service->refresh();

            $this->handler->manageImagesOnModel(
                $service,
                'service_image',
                $request->file('image'),
                1,
                $data['replace_all'] ?? false,
                $data['image_id'] ?? null
            );

            if ($serviceProvider instanceof ServiceProvider) {
                $providerUser = $serviceProvider->user;
                if ($providerUser) {
                    $userRole = $providerUser->role->name_en ?? '';

                    if ($this->isVisualProvider($userRole) || $isOwner) {
                        if ($request->filled('youtube_link')) {
                            $this->handler->upsertYoutubeLinkOnModel(
                                $service,
                                'service_link_youtube',
                                $request->input('youtube_link')
                            );
                        }

                        if ($request->hasFile('gallery') || $request->input('deleted_gallery_ids')) {
                            $this->handler->manageImagesOnModel(
                                $service,
                                'gallery',
                                $request->file('gallery'),
                                8,
                                $request->boolean('replace_all_gallery'),
                                $request->input('deleted_gallery_ids')
                            );
                        }
                    }
                }
            }

            $service->refresh();
            $service->load(['orderStatusAble.status', 'media', 'mainKeys']);

            if (array_key_exists('main_key_ids', $data)) {
                $this->syncServiceMainKeys($service, $data['main_key_ids'] ?? [], $serviceProvider);
                $service->load('mainKeys');
            }

            DB::commit();

            return $service;
        } catch (ApiResponseException $e) {
            throw $e;
        } catch (Exception $e) {
            DB::rollBack();

            throw $e;
        }
    }

    public function deleteServiceById($serviceId): void
    {
        DB::beginTransaction();
        try {
            if (Auth::guard('owner')->check()) {
                $service = $this->findService($serviceId);
            } else {
                $authUser = $this->authService->authUser();
                $hall = $this->authService->userable($authUser);
                $service = $this->findService($serviceId);
                $service->load(['serviceable', 'media']);
                $this->checkServiceable($service, $hall);
            }

            $this->serviceRepo->deleteService($service);

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();

            throw $e;
        }
    }

    public function findService($serviceId)
    {
        $service = $this->serviceRepo->findService($serviceId);
        if (! $service) {
            throw new Exception('the service not found');
        }

        return $service;
    }

    public function checkServiceable($service, $serviceProvider)
    {
        $type = $service->serviceable_type;
        if ($type == 'App\Models\ServiceProvider') {
            $serviceHall = $serviceProvider->services()->where('id', $service->id)->first();
        } else {
            $serviceHall = Service::where('id', $service->id)
                ->whereHasMorph('serviceable', [\App\Models\Room::class], function ($query) use ($serviceProvider) {
                    $query->where('service_provider_id', $serviceProvider->id);
                })
                ->first();
        }

        if (! $serviceHall) {
            throw new Exception('This service does not belong to your hall');
        }

        return $serviceHall;
    }

    private function createServiceForProvider(array $data, $hall)
    {
        if (! empty($data['room_id'])) {
            $services = [];

            foreach ($data['room_id'] as $roomId) {
                $room = $this->roomService->findRoomForHall($hall, $roomId);

                $service = $this->serviceRepo->createService($data);
                $service->serviceable_id = $room->id;
                $service->serviceable_type = get_class($room);
                $service->save();
                $pendingStatus = Status::where('name_en', 'under_review')->first();
                if ($pendingStatus) {
                    $service->orderStatusAble()->create([
                        'status_id' => $pendingStatus->id,
                    ]);
                }
                $services[] = $service;
            }

            return $services;
        }

        $service = $this->serviceRepo->createService($data);
        $service->serviceable_id = $hall->id;
        $service->serviceable_type = get_class($hall);
        $service->save();

        $pendingStatus = Status::where('name_en', 'under_review')->first();
        if ($pendingStatus) {
            $service->orderStatusAble()->create([
                'status_id' => $pendingStatus->id,
            ]);
        }

        return $service;
    }

    private function isVisualProvider(string $userRole): bool
    {
        $acceptedRoles = ['photographers', 'aradas', 'banquet coordinator', 'coordinator', 'planner', 'owner'];

        foreach ($acceptedRoles as $role) {
            if (str_contains($userRole, $role)) {
                return true;
            }
        }

        return false;
    }
}
