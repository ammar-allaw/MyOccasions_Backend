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
        // if(!$services)
        // {
        //     throw new Exception('the services not found');
        // }
        return $services;
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

}