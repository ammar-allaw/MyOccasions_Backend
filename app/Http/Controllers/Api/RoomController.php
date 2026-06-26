<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\ApiResponseException;
use App\Exceptions\Handler;
use App\Http\Controllers\Controller;
use App\Http\Requests\Hall\AddRoomRequest;
use App\Http\Requests\Hall\UpdateRoomRequest;
use App\Http\Resources\Hall\RoomResource;
use App\Http\Resources\User\ServiceProvideResource;
use App\Services\Room\Interface\RoomServiceInterface;
use Exception;

class RoomController extends Controller
{
    public function __construct(
        public Handler $handler,
        private RoomServiceInterface $roomService,
    ) {}

    public function addRoom(AddRoomRequest $request, $serviceProviderId = null)
    {
        try {
            $room = $this->roomService->addRoom(
                $request->validated(),
                $request,
                $serviceProviderId
            );

            return $this->handler->successResponse(
                ['room' => new RoomResource($room)],
                true,
                'success add room',
                201
            );
        } catch (Exception $e) {
            return $this->handler->errorResponse(
                false,
                $e->getMessage(),
                null,
                400
            );
        }
    }

    public function getRoom()
    {
        $rooms = $this->roomService->getRooms();

        return $this->handler->successResponse(
            ['rooms' => RoomResource::collection($rooms)],
            true,
            'success add room',
            200
        );
    }

    public function updateRoom(UpdateRoomRequest $request, $roomId)
    {
        try {
            $room = $this->roomService->updateRoom(
                $request->validated(),
                $request,
                $roomId
            );

            return $this->handler->successResponse(
                ['room' => new RoomResource($room)],
                true,
                'success update room',
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

    public function getRoomsByHallId($hallId)
    {
        try {
            $rooms = $this->roomService->getRoomsByHallId($hallId);

            return $this->handler->successResponse(
                ['room' => RoomResource::collection($rooms)],
                true,
                'success get rooms',
                201
            );
        } catch (Exception $e) {
            return $this->handler->errorResponse(
                false,
                $e->getMessage(),
                null,
                400
            );
        }
    }

    public function getDetailsOfHall($userId = null)
    {
        try {
            $hall = $this->roomService->getHallDetails($userId);

            return $this->handler->successResponse(
                ['hall' => new ServiceProvideResource($hall)],
                true,
                'get details of hall',
                200
            );
        } catch (ApiResponseException $e) {
            return $this->handler->errorResponse(false, $e->getMessage(), $e->data, $e->statusCode);
        }
    }

    public function deleteRoom($roomId)
    {
        try {
            $this->roomService->deleteRoomById($roomId);

            return $this->handler->successResponse(
                null,
                true,
                'success delete room',
                200
            );
        } catch (Exception $e) {
            return $this->handler->errorResponse(false, $e->getMessage(), null, 400);
        }
    }
}
