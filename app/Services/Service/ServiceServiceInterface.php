<?php 
namespace App\Services\Service;

interface ServiceServiceInterface
{
    //in here client and user repo and the same in service 
    //for service provider
    public function getServicesForServiceProvider($serviceProvider = null);
}