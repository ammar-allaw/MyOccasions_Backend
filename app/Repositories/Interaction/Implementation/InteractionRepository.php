<?php

namespace App\Repositories\Interaction\Implementation;

use App\Models\Food;
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
        $attributes = [
            'viewable_type' => $target::class,
            'viewable_id' => $target->getKey(),
            'user_id' => $user->id,
            'viewed_on' => now()->toDateString(),
        ];

        $view = ModelView::firstOrCreate($attributes, [
            'ip_address' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 1000),
        ]);

        return $view->wasRecentlyCreated;
    }

    public function like(Model $target, User $user): bool
    {
        $like = ModelLike::firstOrCreate([
            'likeable_type' => $target::class,
            'likeable_id' => $target->getKey(),
            'user_id' => $user->id,
        ]);

        return $like->wasRecentlyCreated;
    }

    public function unlike(Model $target, User $user): bool
    {
        return ModelLike::query()
            ->where('likeable_type', $target::class)
            ->where('likeable_id', $target->getKey())
            ->where('user_id', $user->id)
            ->delete() > 0;
    }

    public function counts(Model $target): array
    {
        return [
            'views_count' => ModelView::query()
                ->where('viewable_type', $target::class)
                ->where('viewable_id', $target->getKey())
                ->count(),
            'likes_count' => ModelLike::query()
                ->where('likeable_type', $target::class)
                ->where('likeable_id', $target->getKey())
                ->count(),
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
}
