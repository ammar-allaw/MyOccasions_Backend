<?php 
namespace App\Services\Service;

interface ServiceServiceInterface
{
    //in here client and user repo and the same in service 
    //for service provider
    public function getServicesForServiceProvider($serviceProvider = null);
    
    public function addMainKey($data);

    public function getMainKeys($data = []);

    public function findMainKeyById($id);

    public function updateMainKey($data, $mainKey);

    public function deleteMainKey($mainKey);
}