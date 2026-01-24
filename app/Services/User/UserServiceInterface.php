<?php 
namespace App\Services\User;

interface UserServiceInterface
{
    //in here client and user repo and the same in service 
    //for service provider
    public function getAllUser();
    // public function userable($user);
    public function findUserByPhoneNumber($phoneNumber);
    public function findUserById($id);
    public function createUser(array $data,$userType);
    public function createClient($data);
    public function updateUser($id, array $data);
    public function deleteUser($id);
    public function getUserByRoleId($role, $filters = []);
    public function getAllServiceProvider();
    public function findServiceProviderById($id);
    public function softDeleteServiceProvider($id);
    public function getServiceProviderWithTrashed();
    public function findServiceProviderWithTrashedById($serviceProviderId);
    public function forceDeleteServiceProvider($serviceProvider);
    public function updateServiceProvider($serviceProvider, array $data);
    public function getUserByRoleIdForOwner($role);
}
