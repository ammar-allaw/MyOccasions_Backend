<?php

namespace App\Services\Interaction\Implementation;

use App\Repositories\Interaction\Interface\InteractionRepositoryInterface;
use App\Services\Interaction\Interface\InteractionServiceInterface;
use Illuminate\Http\Request;
use App\Models\User;

class InteractionService implements InteractionServiceInterface
{
    public function __construct(
        private InteractionRepositoryInterface $interactionRepository,
    ) {}

    public function view(string $type, int $id, User $user, Request $request): array
    {
        $target = $this->interactionRepository->findTarget($type, $id);
        $created = $this->interactionRepository->recordView($target, $user, $request);

        return $this->summary($type, $id, $user, $created ? 'view_created' : 'view_exists');
    }

    public function like(string $type, int $id, User $user): array
    {
        $target = $this->interactionRepository->findTarget($type, $id);
        $created = $this->interactionRepository->like($target, $user);

        return $this->summary($type, $id, $user, $created ? 'liked' : 'already_liked');
    }

    public function unlike(string $type, int $id, User $user): array
    {
        $target = $this->interactionRepository->findTarget($type, $id);
        $deleted = $this->interactionRepository->unlike($target, $user);

        return $this->summary($type, $id, $user, $deleted ? 'unliked' : 'not_liked');
    }

    private function summary(string $type, int $id, User $user, string $state): array
    {
        $target = $this->interactionRepository->findTarget($type, $id);
        $counts = $this->interactionRepository->counts($target);

        return [
            'type' => $type,
            'id' => $id,
            'state' => $state,
            'views_count' => $counts['views_count'],
            'likes_count' => $counts['likes_count'],
            'is_liked' => $this->interactionRepository->isLikedBy($target, $user),
        ];
    }
}
