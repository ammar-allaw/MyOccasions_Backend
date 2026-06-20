<?php

namespace App\Repositories\Service\Interface;

interface ServiceRepositoryInterface
{
    public function getServicesForServiceProvider($serviceProvider = null);

    public function getServicesByRoleAndMainKeyQuery($role, array $filters = []);

    public function addMainKey($data);

    public function getMainKeys($roleId = null);

    public function findMainKeyById($id);

    public function updateMainKey($data, $mainKey);

    public function deleteMainKey($mainKey);

    public function syncServiceMainKeys($service, array $mainKeyIds): void;

    public function createService(array $data);

    public function findService($serviceId);

    public function updateService(array $data, $service);

    public function deleteService($service);

    public function findServiceModelById(int $id);
}
