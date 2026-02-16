<?php

namespace App\Repositories\Service;
interface ServiceRepositoryInterface
{
    public function getServicesForServiceProvider($serviceProvider = null);

}