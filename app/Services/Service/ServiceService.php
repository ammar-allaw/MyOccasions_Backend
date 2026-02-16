<?php

namespace App\Services\Service;
use App\Repositories\Service\ServiceRepositoryInterface;
use App\Services\Service\ServiceServiceInterface;
use Exception;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

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
}