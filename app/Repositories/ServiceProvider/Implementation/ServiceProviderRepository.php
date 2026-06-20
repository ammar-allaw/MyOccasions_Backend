<?php

namespace App\Repositories\ServiceProvider\Implementation;

use App\Models\ServiceProvider;
use App\Repositories\ServiceProvider\Interface\ServiceProviderRepositoryInterface;

class ServiceProviderRepository implements ServiceProviderRepositoryInterface
{
    public function createServiceProvider($data)
    {
        return ServiceProvider::create($data);
    }
}
