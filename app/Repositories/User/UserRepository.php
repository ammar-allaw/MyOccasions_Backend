<?php
namespace App\Repositories\User;

use App\Models\Client;
use App\Models\ServiceProvider;
use App\Models\User;
use App\Repositories\User\UserRepositoryInterface;

class UserRepository implements UserRepositoryInterface
{
    public function findServiceProviderById($id)
    {
        return User::where('is_provider',1)->where('id',$id)->first();
    }

    public function getServiceProviderById($serviceProviderId)
    {
        return ServiceProvider::where('id', $serviceProviderId)->first();
    }


    public function softDeleteServiceProvider($serviceProvider)
    {
        return $serviceProvider->delete();
    }
    public function getServiceProviderWithTrashed()
    {
        return User::onlyTrashed()->where('is_provider',1)->get();
    }

    public function findServiceProviderWithTrashedById($serviceProviderId)
    {
        return User::withTrashed()->where('is_provider',1)->where('id',$serviceProviderId)->first();
    }
    
    public function forceDeleteServiceProvider($serviceProvider)
    {
        return $serviceProvider->forceDelete();
    }

    public function getAllUser()
    {
        return User::get();
    }

    public function findUserByPhoneNumber($phoneNumber)
    {
        return User::where('phone_number', $phoneNumber)->first();
    }

    public function findUserById($id)
    {
        return User::findOrFail($id);

    }


    public function createUser(array $data)
    {
        return User::create($data);
    }

    public function createClient(array $data)
    {
        return Client::create($data);
    }

    public function createServiceProvider($data)
    {
        return ServiceProvider::create($data);
    }


    public function updateUser($id, array $data)
    {
        $task = User::findOrFail($id);
        $task->update($data);
        return $task;
    }

    public function deleteUser($id)
    {
        $task = User::findOrFail($id);
        $task->delete();
    }

    public function getAllServiceProvider()
    {
        $users=User::where('is_provider',1)->get();
        return $users;
    }


    public function getUserByRoleId($role, $filters = [])
    {
        return User::query()
            ->where('role_id', $role->id)
            ->where('role_id', '!=', 2)
            ->whereHasMorph('userable', [ServiceProvider::class], function($query) use ($filters) {
                
                // Filter by Search Name
                if (!empty($filters['search'])) {
                    $search = $filters['search'];
                    $query->where(function($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%")
                          ->orWhere('name_en', 'like', "%{$search}%");
                    });
                }

                // Filter by Government
                if (!empty($filters['government_id'])) {
                    $query->where('government_id', $filters['government_id']);
                }

                // Filter by Region
                if (!empty($filters['region_id'])) {
                    $query->where('region_id', $filters['region_id']);
                }
                
                // Hall Filters: Rent Price & Capacity (Applying on related rooms)
                if (isset($filters['min_price']) || isset($filters['max_price']) || isset($filters['min_capacity']) || isset($filters['max_capacity'])) {
                    $query->whereHas('rooms', function($roomQ) use ($filters) {
                        if (isset($filters['min_price'])) {
                            $roomQ->where('rent_price', '>=', $filters['min_price']);
                        }
                        if (isset($filters['max_price'])) {
                            $roomQ->where('rent_price', '<=', $filters['max_price']);
                        }
                        if (isset($filters['min_capacity'])) {
                            $roomQ->where('capacity', '>=', $filters['min_capacity']);
                        }
                        if (isset($filters['max_capacity'])) {
                            $roomQ->where('capacity', '<=', $filters['max_capacity']);
                        }
                    });
                }

                $query->whereHas('orderStatusAble', function($statusQuery) {
                    $statusQuery->whereHas('status', function($innerQuery) {
                        $innerQuery->where('name_en', 'accepted');
                    });
                });
            })
            ->get();
    }
    public function getUserByRoleIdForOwner($role)
    {
        return User::query()
            ->where('role_id', $role->id)
            ->get();
    }


    public function updateServiceProvider($serviceProvider, array $data)
    {
        // فصل البيانات - User fields vs ServiceProvider fields
        $userFields = array_intersect_key($data, array_flip(['phone_number', 'password']));
        $providerFields = array_intersect_key($data, array_flip([
            'name', 'name_en', 'description', 'description_en', 
            'location', 'location_en', 'address_url', 'government_id', 'region_id'
        ]));
        
        // تحديث ServiceProvider
        if (!empty($providerFields)) {
            $serviceProvider->update($providerFields);
        }
        
        // تحديث User
        if (!empty($userFields)) {
            if (isset($userFields['password'])) {
                $userFields['password'] = bcrypt($userFields['password']);
            }
            $serviceProvider->user()->update($userFields);
        }
        
        // إرجاع User object مع العلاقات
        return $serviceProvider->user->fresh(['role', 'userable.media']);
    }

    public function addTypesToServiceProvider($serviceProvider, $types)
    {
        return $serviceProvider->types()->syncWithoutDetaching($types);
    }
}
