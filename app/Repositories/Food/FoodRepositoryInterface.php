<?php

namespace App\Repositories\Food;

interface FoodRepositoryInterface
{
    public function all(array $filters = []);
    public function findById(int $id);
    public function create(array $data);
    public function update($food, array $data);
    public function delete($food): bool;
}
