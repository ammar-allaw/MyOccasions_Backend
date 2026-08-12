<?php

namespace App\Repositories\Interaction\Implementation;

use App\Models\Food;
use App\Models\InteractionCounter;
use App\Models\ModelLike;
use App\Models\ModelView;
use App\Models\Room;
use App\Models\Service;
use App\Models\ServiceProvider;
use App\Models\User;
use App\Repositories\Interaction\Interface\InteractionRepositoryInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use InvalidArgumentException;

class InteractionRepository implements InteractionRepositoryInterface
{
    private const TYPE_MAP = [
        'service_provider' => ServiceProvider::class,
        'room' => Room::class,
        'service' => Service::class,
        'food' => Food::class,
    ];

    public function findTarget(string $type, int $id): Model
    {
        $modelClass = self::TYPE_MAP[$type] ?? null;

        if (! $modelClass) {
            throw new InvalidArgumentException('Invalid interaction type');
        }

        return $modelClass::query()->findOrFail($id);
    }

    public function recordView(Model $target, User $user, Request $request): bool
    {
        $counter = $this->counterFor($target);

        $attributes = [
            'viewable_type' => $target::class,
            'viewable_id' => $target->getKey(),
            'user_id' => $user->id,
        ];

        $view = ModelView::firstOrCreate($attributes, [
            'viewed_on' => now()->toDateString(),
            'ip_address' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 1000),
        ]);

        if ($view->wasRecentlyCreated) {
            $counter->increment('views_count');
        }

        return $view->wasRecentlyCreated;
    }

    public function like(Model $target, User $user): bool
    {
        $counter = $this->counterFor($target);

        $like = ModelLike::firstOrCreate([
            'likeable_type' => $target::class,
            'likeable_id' => $target->getKey(),
            'user_id' => $user->id,
        ]);

        if ($like->wasRecentlyCreated) {
            $counter->increment('likes_count');
        }

        return $like->wasRecentlyCreated;
    }

    public function unlike(Model $target, User $user): bool
    {
        $counter = $this->counterFor($target);

        $deleted = ModelLike::query()
            ->where('likeable_type', $target::class)
            ->where('likeable_id', $target->getKey())
            ->where('user_id', $user->id)
            ->delete() > 0;

        if ($deleted) {
            InteractionCounter::query()
                ->whereKey($counter->id)
                ->where('likes_count', '>', 0)
                ->decrement('likes_count');
        }

        return $deleted;
    }

    public function counts(Model $target): array
    {
        $counter = $this->counterFor($target);

        return [
            'views_count' => $counter->views_count,
            'likes_count' => $counter->likes_count,
        ];
    }

    public function isLikedBy(Model $target, User $user): bool
    {
        return ModelLike::query()
            ->where('likeable_type', $target::class)
            ->where('likeable_id', $target->getKey())
            ->where('user_id', $user->id)
            ->exists();
    }

    public function likedTargetsForUser(User $user): array
    {
        $likes = ModelLike::query()
            ->where('user_id', $user->id)
            ->latest()
            ->get()
            ->groupBy('likeable_type');

        $serviceProviderIds = $likes->get(ServiceProvider::class)?->pluck('likeable_id')->all() ?? [];
        $roomIds = $likes->get(Room::class)?->pluck('likeable_id')->all() ?? [];
        $serviceIds = $likes->get(Service::class)?->pluck('likeable_id')->all() ?? [];
        $foodIds = $likes->get(Food::class)?->pluck('likeable_id')->all() ?? [];

        return [
            'service_providers' => User::query()
                ->with([
                    'userable',
                    'userable.types',
                    'userable.interactionCounter',
                    'userable.likes' => fn ($query) => $query->where('user_id', $user->id),
                    'userPermissions',
                    'role.permissions',
                ])
                ->where('userable_type', ServiceProvider::class)
                ->whereIn('userable_id', $serviceProviderIds)
                ->whereHasMorph('userable', [ServiceProvider::class], function ($query) {
                    $query->whereHas('orderStatusAble.status', function ($statusQuery) {
                        $statusQuery->where('name_en', 'accepted');
                    });
                })
                ->get(),
            'rooms' => Room::query()
                ->with(['media', 'orderStatusAble.status'])
                ->whereIn('id', $roomIds)
                ->whereHas('orderStatusAble.status', function ($statusQuery) {
                    $statusQuery->where('name_en', 'accepted');
                })
                ->get(),
            'services' => Service::query()
                ->with(['media', 'mainKeys', 'orderStatusAble.status'])
                ->whereIn('id', $serviceIds)
                ->whereHas('orderStatusAble.status', function ($statusQuery) {
                    $statusQuery->where('name_en', 'accepted');
                })
                ->get(),
            'foods' => Food::query()
                ->with(['media', 'mainKey', 'serviceProvider', 'orderStatusAble.status'])
                ->whereIn('id', $foodIds)
                ->where('is_active', true)
                ->whereHas('orderStatusAble.status', function ($statusQuery) {
                    $statusQuery->where('name_en', 'accepted');
                })
                ->get(),
        ];
    }

    private function counterFor(Model $target): InteractionCounter
    {
        return InteractionCounter::firstOrCreate(
            [
                'interactable_type' => $target::class,
                'interactable_id' => $target->getKey(),
            ],
            [
                'views_count' => $this->viewsCountFor($target),
                'likes_count' => $this->likesCountFor($target),
            ]
        );
    }

    private function viewsCountFor(Model $target): int
    {
        return ModelView::query()
            ->where('viewable_type', $target::class)
            ->where('viewable_id', $target->getKey())
            ->count();
    }

    private function likesCountFor(Model $target): int
    {
        return ModelLike::query()
            ->where('likeable_type', $target::class)
            ->where('likeable_id', $target->getKey())
            ->count();
    }
}
