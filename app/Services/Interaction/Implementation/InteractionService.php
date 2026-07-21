<?php

namespace App\Services\Interaction\Implementation;

use App\Models\User;
use App\Repositories\Interaction\Interface\InteractionRepositoryInterface;
use App\Services\Interaction\Interface\InteractionServiceInterface;
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

    public function stats(string $type, int $id, User $user): array
    {
        $target = $this->interactionRepository->findTarget($type, $id);

        return $this->summary($type, $target, $user, 'stats');
    }

    private function summary(string $type, Model $target, User $user, string $state): array
    {
        $counts = $this->interactionRepository->counts($target);

        return [
            'type' => $type,
            'id' => $target->getKey(),
            'state' => $state,
            'views_count' => $counts['views_count'],
            'likes_count' => $counts['likes_count'],
            'is_liked' => $this->interactionRepository->isLikedBy($target, $user),
        ];
    }
}
