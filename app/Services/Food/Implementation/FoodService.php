<?php

namespace App\Services\Food\Implementation;

use App\Exceptions\Handler;
use App\Http\Resources\Food\FoodResource;
use App\Models\Food;
use App\Models\ServiceProvider;
use App\Models\Status;
use App\Repositories\Food\Interface\FoodRepositoryInterface;
use App\Services\Auth\AuthService;
use App\Services\Food\Interface\FoodServiceInterface;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class FoodService implements FoodServiceInterface
{
    public function __construct(
        private FoodRepositoryInterface $foodRepo,
        private AuthService $authService,
        private Handler $handler,
    ) {}

    public function getFoods(array $requestData): JsonResponse
    {
        try {
            $filters = $requestData;
            $perPage = (int) ($requestData['per_page'] ?? 10);
            $perPage = max(1, min(100, $perPage));
            $filters['per_page'] = $perPage;

            if (Auth::guard('api')->check()) {
                $user = Auth::guard('api')->user();
                if ($user && ! $user->is_provider) {
                    $filters['accepted_only'] = true;
                    $filters['is_active'] = true;
                }
                if ($user && $user->is_provider) {
                    $serviceProvider = $this->authService->userable($user);
                    $filters['service_provider_id'] = $serviceProvider->id;
                }
            }

            $paginator = $this->list($filters);

            return $this->handler->successResponse(
                [
                    'foods' => FoodResource::collection($paginator),
                    'pagination' => [
                        'current_page' => $paginator->currentPage(),
                        'last_page' => $paginator->lastPage(),
                        'per_page' => $paginator->perPage(),
                        'total' => $paginator->total(),
                    ],
                ],
                true,
                'success get foods',
                200
            );
        } catch (Exception $e) {
            $status = $e->getCode() ?: 400;

            return $this->handler->errorResponse(false, $e->getMessage(), null, $status);
        }
    }

    public function getFood(int $foodId): JsonResponse
    {
        try {
            $food = $this->foodRepo->findById($foodId);

            if (Auth::guard('sanctum')->check()) {
                $user = Auth::guard('sanctum')->user();
                if ($user && ! $user->is_provider) {
                    if (! $food->orderStatusAble || $food->orderStatusAble->status->name_en !== 'accepted' || ! $food->is_active) {
                        return $this->handler->errorResponse(false, 'Food not found', null, 404);
                    }
                }
            }

            if (Auth::guard('api')->check()) {
                $user = $this->authService->authUser();
                if ($user && ! $user->is_provider) {
                    if (! $food->orderStatusAble || $food->orderStatusAble->status->name_en !== 'accepted' || ! $food->is_active) {
                        return $this->handler->errorResponse(false, 'Food not found', null, 404);
                    }
                }
                if ($user && $user->is_provider) {
                    $serviceProvider = $this->authService->userable($user);
                    if ($serviceProvider->id !== $food->service_provider_id) {
                        return $this->handler->errorResponse(false, 'Unauthorized', null, 403);
                    }
                }
            }

            return $this->handler->successResponse(
                ['food' => new FoodResource($food)],
                true,
                'success get food',
                200
            );
        } catch (Exception $e) {
            $status = $e->getCode() ?: 400;

            return $this->handler->errorResponse(false, $e->getMessage(), null, $status);
        }
    }

    public function addFood(array $data, ?UploadedFile $image = null, $serviceProviderId = null): JsonResponse
    {
        try {
            $isOwner = Auth::guard('owner')->check();
            $serviceProviderId = $serviceProviderId ?? $data['service_provider_id'] ?? null;

            if (! $isOwner) {
                $user = $this->authService->authUser();
                if (! $user || ! $user->is_provider) {
                    throw new BadRequestHttpException('Only service providers can add food');
                }

                $serviceProvider = $this->authService->userable($user);
                if (! $serviceProvider) {
                    throw new NotFoundHttpException('Service provider profile not found');
                }

                $serviceProviderId = $serviceProvider->id;
            }

            if (! $serviceProviderId) {
                throw new BadRequestHttpException('Service provider is required');
            }

            DB::beginTransaction();
            $food = $this->store($data, $serviceProviderId);

            if ($image) {
                $food->clearMediaCollection('food_image');
                $this->handler->attachImagesToModel(
                    $food,
                    $image,
                    'food_image',
                    0,
                    1
                );
            }

            $food->refresh();
            $food->load(['orderStatusAble.status', 'media', 'mainKey']);
            DB::commit();

            return $this->handler->successResponse(
                ['food' => new FoodResource($food)],
                true,
                'success add food',
                201
            );
        } catch (Exception $e) {
            DB::rollBack();
            $status = $e->getCode() ?: 400;

            return $this->handler->errorResponse(false, $e->getMessage(), null, $status);
        }
    }

    public function updateFood(int $foodId, array $data, ?UploadedFile $image = null): JsonResponse
    {
        try {
            $user = null;
            $isOwner = Auth::guard('owner')->check();

            if (! $isOwner) {
                $user = $this->authService->authUser();
                if (! $user || ! $user->is_provider) {
                    return $this->handler->errorResponse(false, 'Unauthorized', null, 403);
                }

                if (isset($data['status_id'])) {
                    return $this->handler->errorResponse(false, 'Only owner can update food status', null, 403);
                }
            }

            DB::beginTransaction();
            $food = $this->update($foodId, $data, $user);

            if ($image) {
                $food->clearMediaCollection('food_image');
                $this->handler->attachImagesToModel(
                    $food,
                    $image,
                    'food_image',
                    0,
                    1
                );
            }

            $food->refresh();
            $food->load(['orderStatusAble.status', 'media', 'mainKey']);
            DB::commit();

            return $this->handler->successResponse(
                ['food' => new FoodResource($food)],
                true,
                'success update food',
                200
            );
        } catch (Exception $e) {
            DB::rollBack();
            $status = $e->getCode() ?: 400;

            return $this->handler->errorResponse(false, $e->getMessage(), null, $status);
        }
    }

    private function list(array $filters = [])
    {
        $perPage = (int) ($filters['per_page'] ?? 10);
        $perPage = max(1, min(100, $perPage));
        $page = (int) ($filters['page'] ?? 1);
        $page = max(1, $page);

        return $this->foodRepo->all($filters)->paginate($perPage, ['*'], 'page', $page);
    }

    private function store(array $data, ?int $serviceProviderId = null)
    {
        if (! $serviceProviderId) {
            throw new BadRequestHttpException('Service provider is required');
        }

        $serviceProvider = ServiceProvider::find($serviceProviderId);
        if (! $serviceProvider) {
            throw new NotFoundHttpException('Service provider not found');
        }

        $data['service_provider_id'] = $serviceProvider->id;
        $data['slug'] = $this->generateUniqueSlug($data);

        if (! isset($data['is_available'])) {
            $data['is_available'] = true;
        }
        if (! isset($data['is_active'])) {
            $data['is_active'] = true;
        }

        $food = $this->foodRepo->create($data);

        $pendingStatus = Status::firstOrCreate(
            ['name_en' => 'pending'],
            ['name' => 'قيد الانتظار']
        );

        $food->orderStatusAble()->create([
            'status_id' => $pendingStatus->id,
        ]);

        return $food->refresh();
    }

    private function update(int $id, array $data, $currentUser = null)
    {
        $food = $this->foodRepo->findById($id);

        if ($currentUser && $currentUser->is_provider) {
            $provider = $currentUser->userable;
            if (! $provider || $provider->id !== $food->service_provider_id) {
                throw new BadRequestHttpException('Unauthorized to update this food');
            }
        }

        if (isset($data['name']) || isset($data['name_en'])) {
            $data['slug'] = $this->generateUniqueSlug($data, $food->id);
        }

        if (isset($data['status_id']) && Auth::guard('owner')->check()) {
            $status = Status::find($data['status_id']);
            if (! $status) {
                throw new NotFoundHttpException('Status not found');
            }
        }

        $food = $this->foodRepo->update($food, $data);

        if (isset($data['status_id']) && Auth::guard('owner')->check()) {
            if ($food->orderStatusAble) {
                $food->orderStatusAble->status_id = $data['status_id'];
                $food->orderStatusAble->rejection_reason = $data['status_id'] == 2 ? ($data['rejection_reason'] ?? null) : null;
                $food->orderStatusAble->save();
            } else {
                $food->orderStatusAble()->create([
                    'status_id' => $data['status_id'],
                    'rejection_reason' => $data['status_id'] == 2 ? ($data['rejection_reason'] ?? null) : null,
                ]);
            }

            return $food->refresh();
        }

        $changedInputs = array_diff(array_keys($data), ['price', 'status_id']);
        if (! empty($changedInputs)) {
            $pendingStatus = Status::firstOrCreate(
                ['name_en' => 'pending'],
                ['name' => 'قيد الانتظار']
            );

            if ($food->orderStatusAble) {
                $food->orderStatusAble->status_id = $pendingStatus->id;
                $food->orderStatusAble->save();
            } else {
                $food->orderStatusAble()->create([
                    'status_id' => $pendingStatus->id,
                ]);
            }
        }

        return $food->refresh();
    }

    private function generateUniqueSlug(array $data, ?int $excludeId = null): string
    {
        $base = Str::slug($data['name_en'] ?? $data['name'] ?? 'food');
        if (empty($base)) {
            $base = 'food';
        }

        $slug = $base;
        $index = 1;

        while (
            Food::where('slug', $slug)
                ->when($excludeId, fn ($query) => $query->where('id', '!=', $excludeId))
                ->exists()
        ) {
            $slug = $base.'-'.$index++;
        }

        return $slug;
    }
}
