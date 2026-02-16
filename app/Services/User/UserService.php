<?php

namespace App\Services\User;

use App\Models\Client;
use App\Models\Permission;
use App\Models\User;
use App\Repositories\User\UserRepositoryInterface;
use Exception;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

use function Laravel\Prompts\error;

class UserService implements UserServiceInterface
{
    protected $userRepo;

    public function __construct(UserRepositoryInterface $userRepo)
    {
        $this->userRepo = $userRepo;
    }

    public function getAllServiceProvider()
    {
        $users=$this->userRepo->getAllServiceProvider();
        return $users;
    }
    public function findUserByPhoneNumber($phoneNumber) 
    {
        $user=$this->userRepo->findUserByPhoneNumber($phoneNumber);
        if(!$user)
        {
            throw new Exception('the user not found');
        }
        return $user;
    }

    public function getAllUser()
    {
        return $this->userRepo->getAllUser();
    }


    public function findUserById($id) 
    {
        $user=$this->userRepo->findUserById($id);
        if(!$user)
        {
            // throw new NotFoundHttpException('the user not found');
            throw new Exception('the user not found');
        }
        return $user;
        // if($user){
        //     return $user;
        // }
    }

    public function getServiceProviderById($serviceProviderId)
    {
        $serviceProvider=$this->userRepo->getServiceProviderById($serviceProviderId);
        if(!$serviceProvider)
        {
            throw new NotFoundHttpException('the service provider not found');
        }
        return $serviceProvider;
    }
    


    public function createUser(array $data,$userType)
    {
        $user=$this->userRepo->createUser($data);
        if ($userType instanceof Client) 
        {      
            $user->role_id=2;
            $user->is_provider=0;
        }else{
            $user->is_provider=1;
            $user->role_id=$data['role_id'];
        }
        $user->userable_id=$userType->id;
        $user->userable_type=get_class($userType);
        $user->save();
        return $user;
    }

    //this function not in interface
    public function createClient($data)
    {
        $client=$this->userRepo->createClient($data);
        return $client;
    }

    public function updateUser($id, array $data){}
    // public function softDeleteServiceProvider($servicePro)
    // {
    //     $this->userRepo->softDeleteServiceProvider($id);
    // }
    public function deleteUser($id){}

    public function getUserByRoleId($role, $filters = [])
    {
        $users=$this->userRepo->getUserByRoleId($role, $filters);
        $users->load('userable');
        return $users;
    }
    public function getUserByRoleIdForOwner($role)
    {
        $users=$this->userRepo->getUserByRoleIdForOwner($role);
        $users->load(['userable.orderStatusAble.status']);
        return $users;
    }

    public function findServiceProviderById($id)
    {
        $user=$this->userRepo->findServiceProviderById($id);
        if(!$user)
        {
            throw new NotFoundHttpException('the service provider not found');
        }
        return $user;
    }


    public function softDeleteServiceProvider($id)
    {
        $this->userRepo->softDeleteServiceProvider($id);
    }
    public function getServiceProviderWithTrashed()
    {
        return $this->userRepo->getServiceProviderWithTrashed();
    }
    public function findServiceProviderWithTrashedById($serviceProviderId)
    {
        $user = $this->userRepo->findServiceProviderWithTrashedById($serviceProviderId);
        if (!$user) {
            throw new NotFoundHttpException('the service provider not found');
        }
        return $user;
    }
    public function forceDeleteServiceProvider($serviceProvider)
    {
        return $this->userRepo->forceDeleteServiceProvider($serviceProvider);
    }

    public function updateServiceProvider($serviceProvider, array $data)
    {
        return $this->userRepo->updateServiceProvider($serviceProvider, $data);
    }

    public function addTypesToServiceProvider($serviceProvider, $types)
    {
        return $this->userRepo->addTypesToServiceProvider($serviceProvider, $types);
    }

}
