<?php

namespace App\Services\Interaction\Interface;

use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;

interface InteractionServiceInterface
{
    public function view(string $type, int $id, User $user, Request $request): array;

    public function like(string $type, int $id, User $user): array;

    public function unlike(string $type, int $id, User $user): array;

    public function stats(string $type, int $id, Authenticatable $authUser): array;

    public function liked(User $user): array;
}
