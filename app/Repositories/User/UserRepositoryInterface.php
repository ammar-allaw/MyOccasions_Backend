<?php

namespace App\Repositories\User;
interface UserRepositoryInterface
{
    //in here client and user repo and the same in service 
    public function getAllUser();
    public function getUserByRoleId($role, $filters = []);
    public function findUserByPhoneNumber($id);
    public function findUserById($id);
    public function createUser(array $data);
    public function createClient(array $data);
    public function updateUser($id, array $data);
    public function deleteUser($id);
    public function getAllServiceProvider();
    public function findServiceProviderById($id);
    public function softDeleteServiceProvider($id);
    public function getServiceProviderWithTrashed();
    public function findServiceProviderWithTrashedById($serviceProviderId);
    public function forceDeleteServiceProvider($serviceProvider);
    public function updateServiceProvider($serviceProvider, array $data);
    public function getUserByRoleIdForOwner($role);

}