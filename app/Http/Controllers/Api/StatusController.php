<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\Handler;
use App\Http\Controllers\Controller;
use App\Http\Resources\Status\StatusResource;
use App\Services\Status\Interface\StatusServiceInterface;
use Exception;

class StatusController extends Controller
{
    public function __construct(
        private Handler $handler,
        private StatusServiceInterface $statusService,
    ) {}

    public function index()
    {
        try {
            $statuses = $this->statusService->list();

            return $this->handler->successResponse(
                ['statuses' => StatusResource::collection($statuses)],
                true,
                'success get statuses',
                200
            );
        } catch (Exception $e) {
            return $this->handler->errorResponse(false, $e->getMessage(), null, 400);
        }
    }
}
