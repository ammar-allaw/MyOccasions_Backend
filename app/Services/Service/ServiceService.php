<?php

namespace App\Services\Service;

use App\Models\MainKey;
use App\Repositories\Service\ServiceRepositoryInterface;
use App\Services\Service\ServiceServiceInterface;
use Exception;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Illuminate\Support\Facades\Auth;

use function Laravel\Prompts\error;

class ServiceService implements ServiceServiceInterface
{
    protected $serviceRepo;

    public function __construct(ServiceRepositoryInterface $serviceRepo)
    {
        $this->serviceRepo = $serviceRepo;
    }

    public function getServicesForServiceProvider($serviceProvider = null)
    {
        $services=$this->serviceRepo->getServicesForServiceProvider($serviceProvider);
        return $services;
    }

    public function filterServicesByMainKey($role, array $filters = [])
    {
        if (!empty($filters['price'])) {
            $price = $filters['price'];
            $op = $filters['price_operator'] ?? 'less';
            if ($op === 'more') {
                $filters['service_min_price'] = $price;
            } else {
                $filters['service_max_price'] = $price;
            }
        }

        $query = $this->serviceRepo->getServicesByRoleAndMainKeyQuery($role, $filters);

        $perPage = $filters['per_page'] ?? 10;
        $page = $filters['page'] ?? 1;

        return $query->paginate($perPage, ['*'], 'page', $page);
    }

    public function addMainKey($data)
    {
        $mainKey=$this->serviceRepo->addMainKey($data);
        return $mainKey;
    }

    public function getMainKeys($data = [])
    {
        $roleId = null;
        $isClient = false;

        if (Auth::guard('owner')->check()) {
            if (isset($data['role_id'])) {
                $roleId = $data['role_id'];
            }
        } elseif (Auth::guard('api')->check()) {
            $user = Auth::guard('api')->user();

            if ($user->is_provider) {
                $roleId = $user->role_id;
            } else {
                $isClient = true;
                if (!isset($data['role_id'])) {
                    throw new Exception('role_id is required', 400);
                }
                $roleId = $data['role_id'];
            }
        }

        $mainKeys = $this->serviceRepo->getMainKeys($roleId);

        if ($isClient) {
            // $lang = app()->header('Accept-Language');
            $mainKeys->transform(function ($item) use ($data) {
                if ($data['lang'] == 'ar') {
                    // $item->key is already correct
                } else {
                    $item->key = $item->key_en;
                }
                unset($item->key_en);
                return $item;
            });
        }
        
        return $mainKeys;
    }

    public function findMainKeyById($id)
    {
        $mainKey=$this->serviceRepo->findMainKeyById($id);
        if(!$mainKey)
        {
            throw new Exception('Main Key not found',404);
        }
        return $mainKey;
    }

    public function updateMainKey($data, $mainKey)
    {
         $updateMainKey=$this->serviceRepo->updateMainKey($data, $mainKey);
        return $updateMainKey;
    }

    public function deleteMainKey($mainKey)
    {
        $this->serviceRepo->deleteMainKey($mainKey);
    }

    public function syncServiceMainKeys($service, array $mainKeyIds, $serviceProvider = null): void
    {
        if (!empty($mainKeyIds)) {
            $existingCount = MainKey::whereIn('id', $mainKeyIds)->count();
            if ($existingCount !== count($mainKeyIds)) {
                throw new Exception('One or more main_key IDs are invalid.', 422);
            }

            if ($serviceProvider) {
                $serviceProvider->loadMissing('user');
                $userRoleId = $serviceProvider->user?->role_id;
                if ($userRoleId) {
                    $invalidCount = MainKey::whereIn('id', $mainKeyIds)
                        ->where('role_id', '!=', $userRoleId)
                        ->count();
                    if ($invalidCount > 0) {
                        throw new Exception('One or more main keys do not match the service provider role.', 422);
                    }
                }
            }
        }

        $this->serviceRepo->syncServiceMainKeys($service, $mainKeyIds);
    }

}