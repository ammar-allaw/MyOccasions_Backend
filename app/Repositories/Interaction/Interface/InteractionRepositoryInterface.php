<?php

namespace App\Repositories\Interaction\Interface;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

interface InteractionRepositoryInterface
{
    public function findTarget(string $type, int $id): Model;

    public function recordView(Model $target, User $user, Request $request): bool;

    public function like(Model $target, User $user): bool;

    public function unlike(Model $target, User $user): bool;

    public function counts(Model $target): array;

    public function isLikedBy(Model $target, User $user): bool;
}
