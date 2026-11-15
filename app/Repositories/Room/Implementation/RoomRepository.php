<?php

namespace App\Repositories\Room\Implementation;

use App\Models\Room;
use App\Repositories\Room\Interface\RoomRepositoryInterface;

class RoomRepository implements RoomRepositoryInterface
{
    public function addRoom(array $data)
    {
        return Room::create($data);
    }

    public function updateRoom(array $data, $room)
    {
        return $room->update($data);
    }

    public function getRoomsByHall($hall)
    {
        return $hall->rooms()->get();
    }

    public function getAcceptedRoomsByHall($hall)
    {
        return $hall->rooms()
            ->whereHas('orderStatusAble.status', function ($query) {
                $query->where('name_en', 'accepted');
            })
            ->with([
                'orderStatusAble.status',
                'services' => function ($query) {
                    $query->whereHas('orderStatusAble.status', function ($statusQuery) {
                        $statusQuery->where('name_en', 'accepted');
                    });
                },
                'services.orderStatusAble.status',
                'services.media',
                'media',
            ])
            ->get();
    }

    public function findRoom($roomId)
    {
        return Room::find($roomId);
    }

    public function deleteRoom($room)
    {
        return $room->delete();
    }

    public function findRoomModelById(int $id)
    {
        return Room::find($id);
    }

    public function getAllWithStatus()
    {
        return Room::with([
            'orderStatusAble.status',
            'serviceProvider.user',
            'media',
        ])->get();
    }
}
