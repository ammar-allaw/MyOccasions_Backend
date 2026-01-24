<?php

namespace App\Repositories\ServiceProvider;
interface ServiceProviderRepositoryInterface
{
    public function createServiceProvider($data);
    public function addRoom($data);
    public function updateRoom($data,$room);
    public function getRoom($hall);
    public function findRoom($roomId);
    public function addService($data);
    public function findService($serviceId);
    public function updateService($data,$service);
    public function deleteService($service);
    public function deleteRoom($room);
    // public function getRoomByHallId()

}