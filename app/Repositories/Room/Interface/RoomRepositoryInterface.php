<?php

namespace App\Repositories\Room\Interface;

interface RoomRepositoryInterface
{
    public function addRoom(array $data);

    public function updateRoom(array $data, $room);

    public function getRoomsByHall($hall);

    public function findRoom($roomId);

    public function deleteRoom($room);

    public function findRoomModelById(int $id);

    public function getAllWithStatus();
}
