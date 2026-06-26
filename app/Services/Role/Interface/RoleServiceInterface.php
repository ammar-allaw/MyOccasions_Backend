<?php

namespace App\Services\Role\Interface;

use App\Models\User;
use Illuminate\Http\JsonResponse;

interface RoleServiceInterface
{
    public function getBrowsableRolesForClient(User $user): JsonResponse;
}
