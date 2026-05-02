<?php

namespace App\Services\Food;

use App\Models\ServiceProvider;
use App\Models\Status;
use App\Repositories\Food\FoodRepositoryInterface;
use App\Services\Food\FoodServiceInterface;
use App\Services\User\UserServiceInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class FoodService implements FoodServiceInterface
{
    protected FoodRepositoryInterface $foodRepo;
    protected UserServiceInterface $userService;

    public function __construct(
        FoodRepositoryInterface $foodRepo,
        UserServiceInterface $userService
    ) {
        $this->foodRepo = $foodRepo;
        $this->userService = $userService;
    }

    public function list(array $filters = [])
    {
        $perPage = (int) ($filters['per_page'] ?? 10);
        $perPage = max(1, min(100, $perPage));
        $page = (int) ($filters['page'] ?? 1);
        $page = max(1, $page);

        return $this->foodRepo->all($filters)->paginate($perPage, ['*'], 'page', $page);
    }

    public function show(int $id)
    {
        return $this->foodRepo->findById($id);
    }

    public function store(array $data, ?int $serviceProviderId = null, $currentUser = null)
    {
        if (!$serviceProviderId) {
            throw new BadRequestHttpException('Service provider is required');
        }

        $serviceProvider = ServiceProvider::find($serviceProviderId);
        if (! $serviceProvider) {
            throw new NotFoundHttpException('Service provider not found');
        }

        $data['service_provider_id'] = $serviceProvider->id;
        $data['slug'] = $this->generateUniqueSlug($data);

        if (!isset($data['is_available'])) {
            $data['is_available'] = true;
        }
        if (!isset($data['is_active'])) {
            $data['is_active'] = true;
        }

        $food = $this->foodRepo->create($data);

        $pendingStatus = Status::firstOrCreate(
            ['name_en' => 'pending'],
            ['name' => 'قيد الانتظار']
        );

        $food->orderStatusAble()->create([
            'status_id' => $pendingStatus->id,
        ]);

        return $food->refresh();
    }

    public function update(int $id, array $data, $currentUser = null)
    {
        $food = $this->foodRepo->findById($id);

        if ($currentUser && $currentUser->is_provider) {
            $provider = $currentUser->userable;
            if (!$provider || $provider->id !== $food->service_provider_id) {
                throw new BadRequestHttpException('Unauthorized to update this food');
            }
        }

        if (isset($data['name']) || isset($data['name_en'])) {
            $data['slug'] = $this->generateUniqueSlug($data, $food->id);
        }

        if (isset($data['status_id']) && Auth::guard('owner')->check()) {
            $status = Status::find($data['status_id']);
            if (! $status) {
                throw new NotFoundHttpException('Status not found');
            }
        }

        $food = $this->foodRepo->update($food, $data);

        if (isset($data['status_id']) && Auth::guard('owner')->check()) {
            if ($food->orderStatusAble) {
                $food->orderStatusAble->status_id = $data['status_id'];
                $food->orderStatusAble->rejection_reason = $data['status_id'] == 2 ? ($data['rejection_reason'] ?? null) : null;
                $food->orderStatusAble->save();
            } else {
                $food->orderStatusAble()->create([
                    'status_id' => $data['status_id'],
                    'rejection_reason' => $data['status_id'] == 2 ? ($data['rejection_reason'] ?? null) : null,
                ]);
            }

            return $food->refresh();
        }

        $changedInputs = array_diff(array_keys($data), ['price', 'status_id']);
        if (!empty($changedInputs)) {
            $pendingStatus = Status::firstOrCreate(
                ['name_en' => 'pending'],
                ['name' => 'قيد الانتظار']
            );

            if ($food->orderStatusAble) {
                $food->orderStatusAble->status_id = $pendingStatus->id;
                $food->orderStatusAble->save();
            } else {
                $food->orderStatusAble()->create([
                    'status_id' => $pendingStatus->id,
                ]);
            }
        }

        return $food->refresh();
    }

    public function delete(int $id)
    {
        $food = $this->foodRepo->findById($id);
        return $this->foodRepo->delete($food);
    }

    private function generateUniqueSlug(array $data, ?int $excludeId = null): string
    {
        $base = Str::slug($data['name_en'] ?? $data['name'] ?? 'food');
        if (empty($base)) {
            $base = 'food';
        }

        $slug = $base;
        $index = 1;

        while (
            \App\Models\Food::where('slug', $slug)
                ->when($excludeId, fn($query) => $query->where('id', '!=', $excludeId))
                ->exists()
        ) {
            $slug = $base . '-' . $index++;
        }

        return $slug;
    }
}
