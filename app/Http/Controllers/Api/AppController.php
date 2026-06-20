<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\Handler;
use App\Http\Controllers\Controller;
use App\Http\Resources\App\RoleResource;
use App\Services\Auth\AuthService;

class AppController extends Controller
{

    public $handler;
    private $authService;

    public function __construct(
        Handler $handler,
        AuthService $authService,
    ) {
        $this->handler = $handler;
        $this->authService = $authService;
    }

    public function getRoles()
    {
        $user = auth()->user();
        $role_name=$user->role->name_en;
        if($role_name!='client')
        {
            return $this->handler->errorResponse(
                    false,
                    $this->message('unauthorized'),
                    null,
                    401
                );
        }
        $roles=$this->authService->getRole();
        return $this->handler->successResponse(
                    RoleResource::collection($roles),
                    true,
                    $this->message('roles_retrieved'),
                    200);

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
