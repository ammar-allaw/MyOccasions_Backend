<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\Handler;
use App\Http\Controllers\Controller;
use App\Http\Resources\Food\FoodResource;
use App\Http\Resources\Hall\RoomResource;
use App\Http\Resources\Hall\ServiceResource;
use App\Http\Resources\Interaction\InteractionResource;
use App\Http\Resources\User\UserResource;
use App\Services\Interaction\Interface\InteractionServiceInterface;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;

class InteractionController extends Controller
{
    public function __construct(
        private Handler $handler,
        private InteractionServiceInterface $interactionService,
    ) {}

    public function view(Request $request, string $type, int $id)
    {
        try {
            $interaction = $this->interactionService->view($type, $id, $request->user(), $request);

            return $this->handler->successResponse(
                ['interaction' => new InteractionResource($interaction)],
                true,
                'success record view',
                200
            );
        } catch (InvalidArgumentException $e) {
            return $this->handler->errorResponse(false, $e->getMessage(), null, 422);
        } catch (ModelNotFoundException $e) {
            return $this->handler->errorResponse(false, 'Interaction target not found', null, 404);
        } catch (Exception $e) {
            return $this->handler->errorResponse(false, $e->getMessage(), null, 400);
        }
    }

    public function like(Request $request, string $type, int $id)
    {
        try {
            $interaction = $this->interactionService->like($type, $id, $request->user());

            return $this->handler->successResponse(
                ['interaction' => new InteractionResource($interaction)],
                true,
                'success like',
                200
            );
        } catch (InvalidArgumentException $e) {
            return $this->handler->errorResponse(false, $e->getMessage(), null, 422);
        } catch (ModelNotFoundException $e) {
            return $this->handler->errorResponse(false, 'Interaction target not found', null, 404);
        } catch (Exception $e) {
            return $this->handler->errorResponse(false, $e->getMessage(), null, 400);
        }
    }

    public function unlike(Request $request, string $type, int $id)
    {
        try {
            $interaction = $this->interactionService->unlike($type, $id, $request->user());

            return $this->handler->successResponse(
                ['interaction' => new InteractionResource($interaction)],
                true,
                'success unlike',
                200
            );
        } catch (InvalidArgumentException $e) {
            return $this->handler->errorResponse(false, $e->getMessage(), null, 422);
        } catch (ModelNotFoundException $e) {
            return $this->handler->errorResponse(false, 'Interaction target not found', null, 404);
        } catch (Exception $e) {
            return $this->handler->errorResponse(false, $e->getMessage(), null, 400);
        }
    }

    public function stats(Request $request, string $type, int $id)
    {
        try {
            $interaction = $this->interactionService->stats($type, $id, $this->authenticatedStatsUser());

            return $this->handler->successResponse(
                ['interaction' => new InteractionResource($interaction)],
                true,
                'success get interaction stats',
                200
            );
        } catch (InvalidArgumentException $e) {
            return $this->handler->errorResponse(false, $e->getMessage(), null, 422);
        } catch (AuthorizationException $e) {
            return $this->handler->errorResponse(false, $e->getMessage(), null, 403);
        } catch (ModelNotFoundException $e) {
            return $this->handler->errorResponse(false, 'Interaction target not found', null, 404);
        } catch (Exception $e) {
            return $this->handler->errorResponse(false, $e->getMessage(), null, 400);
        }
    }

    public function liked(Request $request)
    {
        try {
            $liked = $this->interactionService->liked($request->user());

            return $this->handler->successResponse(
                [
                    'serviceProviders' => UserResource::collection($liked['service_providers']),
                    'rooms' => RoomResource::collection($liked['rooms']),
                    'services' => ServiceResource::collection($liked['services']),
                    'foods' => FoodResource::collection($liked['foods']),
                ],
                true,
                'success get liked items',
                200
            );
        } catch (Exception $e) {
            return $this->handler->errorResponse(false, $e->getMessage(), null, 400);
        }
    }

    private function authenticatedStatsUser()
    {
        return Auth::guard('owner')->user() ?: Auth::guard('api')->user();
    }
}
