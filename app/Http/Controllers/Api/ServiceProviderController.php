<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\ApiResponseException;
use App\Exceptions\Handler;
use App\Http\Controllers\Controller;
use App\Http\Requests\Owner\AddServiceProviderRequest;
use App\Http\Requests\Image\AddImageRequest;
use App\Http\Requests\ServiceProvider\UpdateServiceProviderRequest;
use App\Http\Resources\Image\GetImageUrlResource;
use App\Http\Resources\User\UserResource;
use App\Services\Auth\AuthService;
use App\Services\User\Interface\UserServiceInterface;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Pagination\LengthAwarePaginator;

//this controller for service provider بشكل عام 
class ServiceProviderController extends Controller
{
    public $handler;
    private $authService;
    private $userService;
    public function __construct(Handler $handler, AuthService $authService, UserServiceInterface $userService)
    {
        $this->handler = $handler;
        $this->authService = $authService;
        $this->userService = $userService;
    }

    public function addServiceProvider(AddServiceProviderRequest $request)
    {
        try {
            $data = $request->validated();
            $user = $this->userService->addServiceProviderByOwner($data);

            return $this->handler->successResponse(
                ['user' => new UserResource($user)],
                true,
                'success add service provider',
                201
            );
        } catch (ApiResponseException $e) {
            return $this->handler->errorResponse(false, $e->getMessage(), $e->data, $e->statusCode);
        } catch (Exception $e) {
            return $this->handler->errorResponse(
                false,
                $e->getMessage(),
                null,
                400
            );
        }
    }

    public function getServiceProviderDetails($userId=null)
    {
        try {
            $user = $this->userService->getServiceProviderDetails(
                $this->authService->authUser(),
                $userId
            );

            return $this->handler->successResponse(
                ['serviceProvider' => new UserResource($user)],
                true,
                'success get service provider details',
                200
            );
        } catch (ApiResponseException $e) {
            return $this->handler->errorResponse(false, $e->getMessage(), $e->data, $e->statusCode);
        } catch (ModelNotFoundException $e) {
            return $this->handler->errorResponse(false, 'Service provider not found', [], 404);
        }
    }


    public function addImageForServiceProvider(AddImageRequest $request,$userId=null)
    {
        $data=$request->validated();
        try {
            $result = $this->userService->addImageForServiceProvider(
                $data,
                $request,
                $userId
            );

            $responseData = [
                'images' => GetImageUrlResource::collection($result['images'] ?? []),
            ];
            if (! empty($result['cover_images'])) {
                $responseData['cover_images'] = GetImageUrlResource::collection($result['cover_images']);
            }
            if (! empty($result['youtube'])) {
                $responseData['youtube_link'] = [
                    'id' => $result['youtube']->id,
                    'url' => $result['youtube']->youtube_link,
                ];
            }

            return $this->handler->successResponse(
                $responseData,
                true,
                'success add image for service provider',
                201);
        } catch (ModelNotFoundException $e) {
            return $this->handler->errorResponse(false, 'User not found', [], 404);
        } catch (ApiResponseException $e) {
            return $this->handler->errorResponse(false, $e->getMessage(), $e->data, $e->statusCode);
        } catch (Exception $e) {
            return $this->handler->errorResponse(false, $e->getMessage(), [], 400);
        }
    }

    public function updateServiceProvider(UpdateServiceProviderRequest $request, $serviceProviderId=null)
    {
        $data=$request->validated();
        try {
            $updatedUser = $this->userService->updateServiceProviderFromRequest(
                $data,
                $request,
                $serviceProviderId
            );

            return $this->handler->successResponse(
                ['serviceProvider' => new UserResource($updatedUser)],
                true,
                'success update service provider',
                200
            );
        } catch (ApiResponseException $e) {
            return $this->handler->errorResponse(false, $e->getMessage(), $e->data, $e->statusCode);
        } catch (Exception $e) {
            return $this->handler->errorResponse(false, $e->getMessage(), null, 400);
        }
    }

    public function getServiceProvidersByRoleId($roleId)
    {
        return $this->userService->getServiceProvidersByRoleIdForClient(auth()->user(), $roleId);
    }

    public function getServiceProvidersByRoleIdForOwner($roleId = null)
    {
        if ($roleId) {
            $role = $this->authService->findRoleById($roleId);
            $collection = $this->userService->getUserByRoleIdForOwner($role);
        } else {
            $collection = $this->userService->getAllUser();
        }

        $collection = $collection->sortByDesc('id');

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
            [
                'serviceProviders' => UserResource::collection($paginated),
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
