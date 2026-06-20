<?php

namespace App\Services\Room\Interface;

use App\Models\Room;
use App\Models\ServiceProvider;
use Illuminate\Http\Request;

interface RoomServiceInterface
{
    public function addRoom(array $data, Request $request, $serviceProviderId = null): Room;

    public function getRooms();

    public function updateRoom(array $data, Request $request, $roomId): Room;

    public function getRoomsByHallId($hallId);

    public function getHallDetails($userId = null): ServiceProvider;

    public function findRoom($roomId);

    public function findRoomForHall($hall, $roomId);

    public function deleteRoomById($roomId): void;

    public function getAllRoomsWithStatus();
}
