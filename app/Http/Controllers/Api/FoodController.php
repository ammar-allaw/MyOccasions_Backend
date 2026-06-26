<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Food\StoreFoodRequest;
use App\Http\Requests\Food\UpdateFoodRequest;
use App\Services\Food\Interface\FoodServiceInterface;
use Illuminate\Http\Request;

class FoodController extends Controller
{
    public function __construct(
        private FoodServiceInterface $foodService,
    ) {}

    public function getFoods(Request $request)
    {
        return $this->foodService->getFoods($request->all());
    }

    public function getFood($foodId)
    {
        return $this->foodService->getFood((int) $foodId);
    }

    public function addFood(StoreFoodRequest $request, $serviceProviderId = null)
    {
        return $this->foodService->addFood(
            $request->validated(),
            $request->file('image'),
            $serviceProviderId
        );
    }

    public function updateFood(UpdateFoodRequest $request, $foodId)
    {
        return $this->foodService->updateFood(
            (int) $foodId,
            $request->validated(),
            $request->file('image')
        );
    }
}
