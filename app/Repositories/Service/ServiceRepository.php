<?php
namespace App\Repositories\Service;

use App\Models\MainKey;
use App\Repositories\Service\ServiceRepositoryInterface;

class ServiceRepository implements ServiceRepositoryInterface
{
    public function getServicesForServiceProvider($serviceProvider = null)
    {
        return $serviceProvider->services()->with(['media', 'mainKeys', 'orderStatusAble.status'])->get();
    }

    public function getServicesByRoleAndMainKeyQuery($role, array $filters = [])
    {
        $query = \App\Models\Service::query()
            ->whereHas('orderStatusAble', function ($statusQuery) {
                $statusQuery->whereHas('status', function ($innerQuery) {
                    $innerQuery->where('name_en', 'accepted');
                });
            })
            ->whereHasMorph('serviceable', [\App\Models\ServiceProvider::class], function ($providerQuery) use ($role, $filters) {
                $providerQuery->whereHas('user', function ($userQuery) use ($role) {
                    $userQuery->where('role_id', $role->id);
                });

                if (!empty($filters['government_id'])) {
                    $providerQuery->where('government_id', $filters['government_id']);
                }

                if (!empty($filters['region_id'])) {
                    $providerQuery->where('region_id', $filters['region_id']);
                }
            })
            ->whereHas('mainKeys', function ($mainKeyQuery) use ($filters) {
                if (!empty($filters['main_key_id'])) {
                    $mainKeyQuery->where('main_keys.id', $filters['main_key_id']);
                }
            })
            ->with(['media', 'mainKeys', 'orderStatusAble.status']);

        if (isset($filters['service_min_price'])) {
            $query->where('price', '>=', $filters['service_min_price']);
        }
        if (isset($filters['service_max_price'])) {
            $query->where('price', '<=', $filters['service_max_price']);
        }

        return $query;
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

    public function syncServiceMainKeys($service, array $mainKeyIds): void
    {
        $service->mainKeys()->sync($mainKeyIds);
    }
}