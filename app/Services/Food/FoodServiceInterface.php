<?php

namespace App\Services\Food;

interface FoodServiceInterface
{
    public function list(array $filters = []);
    public function show(int $id);
    public function store(array $data, ?int $serviceProviderUserId = null, $currentUser = null);
    public function update(int $id, array $data, $currentUser = null);
    public function delete(int $id);
}
