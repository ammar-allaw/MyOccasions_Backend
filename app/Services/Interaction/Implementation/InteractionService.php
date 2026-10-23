<?php

namespace App\Services\Interaction\Implementation;

use App\Models\Food;
use App\Models\Owner;
use App\Models\Room;
use App\Models\Service;
use App\Models\ServiceProvider;
use App\Models\User;
use App\Repositories\Interaction\Interface\InteractionRepositoryInterface;
use App\Services\Interaction\Interface\InteractionServiceInterface;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InteractionService implements InteractionServiceInterface
{
    public function __construct(
        private InteractionRepositoryInterface $interactionRepository,
    ) {}

    public function view(string $type, int $id, User $user, Request $request): array
    {
        [$target, $created] = DB::transaction(function () use ($type, $id, $user, $request) {
            $target = $this->interactionRepository->findTarget($type, $id);

            return [
                $target,
                $this->interactionRepository->recordView($target, $user, $request),
            ];
        });

        return $this->summary($type, $target, $user, $created ? 'view_created' : 'view_exists');
    }

    public function like(string $type, int $id, User $user): array
    {
        [$target, $created] = DB::transaction(function () use ($type, $id, $user) {
            $target = $this->interactionRepository->findTarget($type, $id);

            return [
                $target,
                $this->interactionRepository->like($target, $user),
            ];
        });

        return $this->summary($type, $target, $user, $created ? 'liked' : 'already_liked');
    }

    public function unlike(string $type, int $id, User $user): array
    {
        [$target, $deleted] = DB::transaction(function () use ($type, $id, $user) {
            $target = $this->interactionRepository->findTarget($type, $id);

            return [
                $target,
                $this->interactionRepository->unlike($target, $user),
            ];
        });

        return $this->summary($type, $target, $user, $deleted ? 'unliked' : 'not_liked');
    }

    public function stats(string $type, int $id, Authenticatable $authUser): array
    {
        $target = $this->interactionRepository->findTarget($type, $id);
        $this->authorizeStatsAccess($target, $authUser);

        return $this->summary(
            $type,
            $target,
            $authUser instanceof User ? $authUser : null,
            'stats'
        );
    }

    public function liked(User $user): array
    {
        return $this->interactionRepository->likedTargetsForUser($user);
    }

    private function summary(string $type, Model $target, ?User $user, string $state): array
    {
        $counts = $this->interactionRepository->counts($target);

        return [
            'type' => $type,
            'id' => $target->getKey(),
            'state' => $state,
            'views_count' => $counts['views_count'],
            'likes_count' => $counts['likes_count'],
            'is_liked' => $user
                ? $this->interactionRepository->isLikedBy($target, $user)
                : false,
        ];
    }

    private function authorizeStatsAccess(Model $target, Authenticatable $authUser): void
    {
        if ($authUser instanceof Owner) {
            return;
        }

        if (! $authUser instanceof User) {
            throw new AuthorizationException('You are not allowed to view these interaction stats');
        }

        if (! $authUser->is_provider) {
            return;
        }

        if ($this->providerOwnsTarget($authUser, $target)) {
            return;
        }

        throw new AuthorizationException('You are not allowed to view these interaction stats');
    }

    private function providerOwnsTarget(User $user, Model $target): bool
    {
        $serviceProviderId = $user->userable_type === ServiceProvider::class
            ? $user->userable_id
            : $user->userable?->id;

        if (! $serviceProviderId) {
            return false;
        }

        if ($target instanceof ServiceProvider) {
            return (int) $target->getKey() === (int) $serviceProviderId;
        }

        if ($target instanceof Room || $target instanceof Food) {
            return (int) $target->service_provider_id === (int) $serviceProviderId;
        }

        if ($target instanceof Service) {
            return $this->providerOwnsService($serviceProviderId, $target);
        }

        return false;
    }

    private function providerOwnsService(int $serviceProviderId, Service $service): bool
    {
        if ($service->serviceable_type === ServiceProvider::class) {
            return (int) $service->serviceable_id === $serviceProviderId;
        }

        if ($service->serviceable_type === Room::class) {
            return Room::query()
                ->whereKey($service->serviceable_id)
                ->where('service_provider_id', $serviceProviderId)
                ->exists();
        }

        return false;
    }
}
