<?php

namespace App\Repositories\Service;
interface ServiceRepositoryInterface
{
    public function getServicesForServiceProvider($serviceProvider = null);
    
    public function addMainKey($data);

    public function getMainKeys($roleId = null);

    public function findMainKeyById($id);

    public function updateMainKey($data, $mainKey);

    public function deleteMainKey($mainKey);
}