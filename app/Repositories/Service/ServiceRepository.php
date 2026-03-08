<?php
namespace App\Repositories\Service;

use App\Models\MainKey;
use App\Repositories\Service\ServiceRepositoryInterface;

class ServiceRepository implements ServiceRepositoryInterface
{
    public function getServicesForServiceProvider($serviceProvider = null)
    {
        return $serviceProvider->services()->get();
    }

    public function addMainKey($data)
    {
        return MainKey::create($data);
    }

    public function getMainKeys($roleId = null)
    {
        $query = MainKey::query();
        if ($roleId) {
            $query->where('role_id', $roleId);
        }
        return $query->get();
    }

    public function findMainKeyById($id)
    {
        return MainKey::find($id);
    }

    public function updateMainKey($data, $mainKey)
    {
        $mainKey->update($data);
        return $mainKey;
    }
    

    public function deleteMainKey($mainKey)
    {
        $mainKey->delete();
    }
}