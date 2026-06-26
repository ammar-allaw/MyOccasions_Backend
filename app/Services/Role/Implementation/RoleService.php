<?php

namespace App\Services\Role\Implementation;

use App\Exceptions\Handler;
use App\Http\Resources\App\RoleResource;
use App\Models\User;
use App\Repositories\Role\Interface\RoleRepositoryInterface;
use App\Services\Role\Interface\RoleServiceInterface;
use Illuminate\Http\JsonResponse;

class RoleService implements RoleServiceInterface
{
    public function __construct(
        private Handler $handler,
        private RoleRepositoryInterface $roleRepository,
    ) {}

    public function getBrowsableRolesForClient(User $user): JsonResponse
    {
        $role_name = $user->role->name_en;
        if ($role_name != 'client') {
            return $this->handler->errorResponse(
                false,
                $this->message('unauthorized'),
                null,
                401
            );
        }

        $roles = $this->roleRepository->getBrowsableForClient();

        return $this->handler->successResponse(
            RoleResource::collection($roles),
            true,
            $this->message('roles_retrieved'),
            200
        );
    }

    private function message(string $key): string
    {
        $locale = request()->header('Accept-Language', 'ar');

        $messages = [
            'ar' => [
                'unauthorized' => 'غير مصرح',
                'roles_retrieved' => 'تم جلب الأدوار بنجاح',
            ],
            'en' => [
                'unauthorized' => 'Unauthorized',
                'roles_retrieved' => 'success get roles',
            ],
        ];

        return $messages[$locale === 'en' ? 'en' : 'ar'][$key] ?? $key;
    }
}
