<?php
namespace App\Repositories\Service;


use App\Repositories\Service\ServiceRepositoryInterface;

class ServiceRepository implements ServiceRepositoryInterface
{
    public function getServicesForServiceProvider($serviceProvider = null)
    {
        return $serviceProvider->services()->get();
    }
}