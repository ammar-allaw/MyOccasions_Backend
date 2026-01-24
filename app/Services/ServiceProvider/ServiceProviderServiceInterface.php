<?php 
namespace App\Services\ServiceProvider;

interface ServiceProviderServiceInterface
{
    //for hall (الصالات) and make service provider 
    public function addRoom($data,$hall);
    public function updateRoom($data,$room);
    public function getRoom($hall);
    public function findRoom($roomId);
    public function findRoomForHall($hall,$roomId);
    public function getRoomsByHallId($hall);
    public function addService($data,$hall);
    public function createServiceProvider($data);
    public function findService($serviceId);
    public function checkServiceable($service,$hall);
    public function updateService($data,$service);
    public function getAllRoomsWithStatus();
    public function deleteService($service);
    public function deleteRoom($room);
}
