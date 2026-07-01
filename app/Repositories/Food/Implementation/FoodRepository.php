<?php

namespace App\Repositories\Food\Implementation;

use App\Models\Food;
use App\Repositories\Food\Interface\FoodRepositoryInterface;

class FoodRepository implements FoodRepositoryInterface
{
    private const DEFAULT_WITH = ['mainKey', 'serviceProvider.user', 'orderStatusAble.status', 'media'];

    public function all(array $filters = [])
    {
        $query = Food::with(self::DEFAULT_WITH);

        if (! empty($filters['service_provider_id'])) {
            $query->where('service_provider_id', $filters['service_provider_id']);
        }

        if (! empty($filters['main_key_id'])) {
            $query->where('main_key_id', $filters['main_key_id']);
        }

        if (isset($filters['is_available'])) {
            $query->where('is_available', (bool) $filters['is_available']);
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', (bool) $filters['is_active']);
        }

        if (! empty($filters['accepted_only'])) {
            $query->whereHas('orderStatusAble.status', function ($statusQuery) {
                $statusQuery->where('name_en', 'accepted');
            });
        }

        if (! empty($filters['search'])) {
            $query->where(function ($subQuery) use ($filters) {
                $term = '%'.$filters['search'].'%';
                $subQuery->where('name', 'like', $term)
                    ->orWhere('name_en', 'like', $term)
                    ->orWhere('description', 'like', $term)
                    ->orWhere('description_en', 'like', $term)
                    ->orWhere('short_description', 'like', $term)
                    ->orWhere('short_description_en', 'like', $term);
            });
        }

        return $query;
    }

    public function findById(int $id)
    {
        return Food::with(self::DEFAULT_WITH)->findOrFail($id);
    }

    public function create(array $data)
    {
        return Food::create($data);
    }

    public function update($food, array $data)
    {
        $food->update($data);

        return $food->refresh();
    }

    public function delete($food): bool
    {
        return $food->delete();
    }
}
