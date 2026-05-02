<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\Handler;
use App\Http\Controllers\Controller;
use App\Http\Requests\Food\StoreFoodRequest;
use App\Http\Requests\Food\UpdateFoodRequest;
use App\Http\Resources\Food\FoodResource;
use App\Services\Auth\AuthService;
use App\Services\Food\FoodServiceInterface;
use App\Services\User\UserServiceInterface;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class FoodController extends Controller
{
    private Handler $handler;
    private FoodServiceInterface $foodService;
    private AuthService $authService;
    private UserServiceInterface $userService;

    public function __construct(
        Handler $handler,
        FoodServiceInterface $foodService,
        AuthService $authService,
        UserServiceInterface $userService
    ) {
        $this->handler = $handler;
        $this->foodService = $foodService;
        $this->authService = $authService;
        $this->userService = $userService;
    }

    public function getFoods(Request $request)
    {
        try {
            $filters = $request->all();
            $perPage = (int) $request->integer('per_page', 10);
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
            // elseif (Auth::guard('api')->check()) {
            //     $user = $this->authService->authUser();
            //     if ($user && ! $user->is_provider) {
            //         $filters['accepted_only'] = true;
            //         $filters['is_active'] = true;
            //     }
            //     if ($user && $user->is_provider) {
            //         $serviceProvider = $this->authService->userable($user);
            //         $filters['service_provider_id'] = $serviceProvider->id;
            //     }
            // }

            $paginator = $this->foodService->list($filters);
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

    public function getFood($foodId)
    {
        try {
            $food = $this->foodService->show($foodId);

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

    public function addFood(StoreFoodRequest $request, $serviceProviderId = null)
    {
        try {
            $data = $request->validated();
            $isOwner = Auth::guard('owner')->check();
            $serviceProviderId = $serviceProviderId ?? $data['service_provider_id'] ?? null;

            if (! $isOwner) {
                $user = $this->authService->authUser();
                if (! $user || ! $user->is_provider) {
                    throw new \Symfony\Component\HttpKernel\Exception\BadRequestHttpException('Only service providers can add food');
                }

                $serviceProvider = $this->authService->userable($user);
                if (! $serviceProvider) {
                    throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException('Service provider profile not found');
                }

                $serviceProviderId = $serviceProvider->id;
            }

            if (! $serviceProviderId) {
                throw new \Symfony\Component\HttpKernel\Exception\BadRequestHttpException('Service provider is required');
            }

            DB::beginTransaction();
            $food = $this->foodService->store($data, $serviceProviderId);

            if ($request->hasFile('image')) {
                $food->clearMediaCollection('food_image');
                $this->handler->attachImagesToModel(
                    $food,
                    $request->file('image'),
                    'food_image',
                    0,
                    1
                );
            }

            $food->refresh();
            $food->load(['orderStatusAble.status', 'media','mainKey']);
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

    public function updateFood(UpdateFoodRequest $request, $foodId)
    {
        try {
            $data = $request->validated();
            $user = null;
            $isOwner = Auth::guard('owner')->check();

            if (! $isOwner) {
                $user = $this->authService->authUser();
                if (!$user || !$user->is_provider) {
                    return $this->handler->errorResponse(false, 'Unauthorized', null, 403);
                }

                if (isset($data['status_id'])) {
                    return $this->handler->errorResponse(false, 'Only owner can update food status', null, 403);
                }
            }

            DB::beginTransaction();
            $food = $this->foodService->update($foodId, $data, $user);

            if ($request->hasFile('image')) {
                $food->clearMediaCollection('food_image');
                $this->handler->attachImagesToModel(
                    $food,
                    $request->file('image'),
                    'food_image',
                    0,
                    1
                );
            }

            $food->refresh();
            $food->load(['orderStatusAble.status', 'media','mainKey']);
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
}
