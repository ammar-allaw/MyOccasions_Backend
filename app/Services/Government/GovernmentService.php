<?php

namespace App\Services\Government;

use App\Models\Government;
use App\Models\Permission;
use App\Models\Room;
use App\Models\Service;
use App\Models\User;
use App\Services\Owner\UserService;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class GovernmentService
{
    public function getGovernments()
    {
        $governments = Government::get();
        return $governments;
    }
}