<?php

namespace App\Services\Owner;

use App\Models\Client;
use App\Models\Owner;
use App\Models\Permission;
use App\Models\Role;
use App\Repositories\User\UserRepositoryInterface;
use Exception;

//owner service 
class OwnerService
{
    // protected $userRepo;

    // public function __construct(UserRepositoryInterface $userRepo)
    // {
    //     $this->userRepo = $userRepo;
    // }

    public function findByEmail($email)
    {
        $owner=Owner::where('email',$email)->first();
        if($owner)
        {
            return $owner;
        }
        // if(!$owner)
        // {
        //     throw new Exception('Invalid credentials',404);
        // }
        // return $owner;
    }

    public function getRolesForOwner()
    {
        $roles=Role::get();
        return $roles;
    }
}
