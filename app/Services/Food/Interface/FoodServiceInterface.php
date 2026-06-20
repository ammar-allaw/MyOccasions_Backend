<?php

namespace App\Services\Food\Interface;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;

interface FoodServiceInterface
{
    public function getFoods(array $requestData): JsonResponse;

    public function getFood(int $foodId): JsonResponse;

    public function addFood(array $data, ?UploadedFile $image = null, $serviceProviderId = null): JsonResponse;

    public function updateFood(int $foodId, array $data, ?UploadedFile $image = null): JsonResponse;
}
