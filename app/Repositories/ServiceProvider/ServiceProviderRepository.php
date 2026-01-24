<?php
namespace App\Repositories\ServiceProvider;

use App\Models\Client;
use App\Models\Room;
use App\Models\Service;
use App\Models\ServiceProvider;
use App\Models\User;
use App\Repositories\User\UserRepositoryInterface;

class ServiceProviderRepository implements ServiceProviderRepositoryInterface
{

    public function createServiceProvider($data)
    {
        return ServiceProvider::create($data);
    }

  

    //// for hall
    public function addRoom($data)
    {
        return Room::create($data);
    }
    public function updateRoom($data,$room)
    {
        return $room->update($data);
    }
    public function getRoom($hall)
    {
        return $hall->rooms()->get();
    }
    public function findRoom($roomId)
    {
        return Room::find($roomId);
    }

    //add service
    public function addService($data)
    {
        return Service::create($data);
    }
    public function findService($serviceId)
    {
        return Service::find($serviceId);
    }

    public function deleteService($service)
    {
        return $service->delete();
    }

    public function updateService($data,$service)
    {
        return $service->update($data);
    }

    public function deleteRoom($room)
    {
        return $room->delete();
    }



    // public function findRoomsByHallId($hall)
    // {
    //     $rooms=$hall->
    // }

}