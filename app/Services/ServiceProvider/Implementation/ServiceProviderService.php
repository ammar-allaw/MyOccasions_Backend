<?php

namespace App\Services\ServiceProvider\Implementation;

use App\Repositories\ServiceProvider\Interface\ServiceProviderRepositoryInterface;
use App\Services\ServiceProvider\Interface\ServiceProviderServiceInterface;

class ServiceProviderService implements ServiceProviderServiceInterface
{
    public function __construct(
        private ServiceProviderRepositoryInterface $serviceProviderRepo,
    ) {}

    public function createServiceProvider($data)
    {
        return $this->serviceProviderRepo->createServiceProvider($data);
    }
}
