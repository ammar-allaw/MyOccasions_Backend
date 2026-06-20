<?php

namespace App\Services\Service\Interface;

use App\Models\Service;
use Illuminate\Http\Request;

interface ServiceServiceInterface
{
    public function getServicesForServiceProvider($serviceProvider = null);

    public function filterServicesByMainKey($role, array $filters = []);

    public function addMainKey($data);

    public function getMainKeys($data = []);

    public function findMainKeyById($id);

    public function updateMainKey($data, $mainKey);

    public function deleteMainKey($mainKey);

    public function syncServiceMainKeys($service, array $mainKeyIds, $serviceProvider = null): void;

    public function addService(array $data, Request $request, $serviceProviderId = null);

    public function updateServiceFromRequest(array $data, Request $request, $serviceId): Service;

    public function deleteServiceById($serviceId): void;

    public function findService($serviceId);

    public function checkServiceable($service, $serviceProvider);
}
