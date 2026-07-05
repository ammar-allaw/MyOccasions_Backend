<?php
namespace App\Repositories\User\Implementation;

use App\Models\Client;
use App\Models\OrderStatus;
use App\Models\ServiceProvider;
use App\Models\Status;
use App\Models\Type;
use App\Models\User;
use App\Repositories\User\Interface\UserRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

class UserRepository implements UserRepositoryInterface
{
    private const CLIENT_BROWSE_WITH = [
        'userable',
        'userPermissions',
        'role.permissions',
        'userable.types',
    ];

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

    public function getAllUser(array $filters = [])
    {
        return $this->applyOwnerServiceProviderFilters(User::query(), $filters)->get();
    }

    public function findUserByPhoneNumber($phoneNumber)
    {
        return User::where('phone_number', $phoneNumber)->first();
    }

    public function findUserById($id)
    {
        return User::findOrFail($id);

    }

    public function findClientProfileByUserId(int $id)
    {
        return User::with(['userable.government', 'role'])
            ->where('id', $id)
            ->where('is_provider', false)
            ->first();
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
        return $this->buildUserByRoleIdQuery($role, $filters)->get();
    }

    public function paginateUsersByRoleIdForClient($role, array $filters, int $page, int $perPage): LengthAwarePaginator
    {
        $collection = $this->buildUserByRoleIdQuery($role, $filters)
            ->with(self::CLIENT_BROWSE_WITH)
            ->get();

        $page = max(1, $page);
        $offset = ($page - 1) * $perPage;

        return new LengthAwarePaginator(
            $collection->slice($offset, $perPage)->values(),
            $collection->count(),
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );
    }

    private function buildUserByRoleIdQuery($role, array $filters = []): Builder
    {
        return User::query()
            ->where('role_id', $role->id)
            ->where('role_id', '!=', 2)
            ->whereHasMorph('userable', [ServiceProvider::class], function ($query) use ($filters) {

                if (! empty($filters['search'])) {
                    $search = $filters['search'];
                    $query->where(function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%")
                            ->orWhere('name_en', 'like', "%{$search}%");
                    });
                }

                if (! empty($filters['government_id'])) {
                    $query->where('government_id', $filters['government_id']);
                }

                if (! empty($filters['region_id'])) {
                    $query->where('region_id', $filters['region_id']);
                }

                if (isset($filters['min_price']) || isset($filters['max_price']) || isset($filters['min_capacity']) || isset($filters['max_capacity'])) {
                    $query->whereHas('rooms', function ($roomQ) use ($filters) {
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

                if (! empty($filters['type_id'])) {
                    $query->whereHas('types', function ($typeQuery) use ($filters) {
                        $typeQuery->where('types.id', $filters['type_id']);
                    });
                }

                $query->whereHas('orderStatusAble', function ($statusQuery) {
                    $statusQuery->whereHas('status', function ($innerQuery) {
                        $innerQuery->where('name_en', 'accepted');
                    });
                });
            });
    }

    public function getUserByRoleIdForOwner($role, array $filters = [])
    {
        $query = User::query()
            ->where('role_id', $role->id);

        return $this->applyOwnerServiceProviderFilters($query, $filters)->get();
    }

    private function applyOwnerServiceProviderFilters(Builder $query, array $filters = []): Builder
    {
        if (! empty($filters['phone_number'])) {
            $phoneNumbers = $filters['phone_number_variants'] ?? [$filters['phone_number']];
            $query->where(function ($phoneQuery) use ($phoneNumbers) {
                foreach ($phoneNumbers as $phoneNumber) {
                    $phoneQuery->orWhere('phone_number', 'like', "%{$phoneNumber}%");
                }
            });
        }

        if (! empty($filters['status_id']) || ! empty($filters['name'])) {
            $query->whereHasMorph('userable', [ServiceProvider::class], function ($providerQuery) use ($filters) {
                if (! empty($filters['status_id'])) {
                    $providerQuery->whereHas('orderStatusAble', function ($statusQuery) use ($filters) {
                        $statusQuery->where('status_id', (int) $filters['status_id']);
                    });
                }

                if (! empty($filters['name'])) {
                    $name = $filters['name'];
                    $providerQuery->where(function ($nameQuery) use ($name) {
                        $nameQuery->where('name', 'like', "%{$name}%")
                            ->orWhere('name_en', 'like', "%{$name}%");
                    });
                }
            });
        }

        return $query;
    }


    public function updateServiceProvider($serviceProvider, array $data)
    {
        // فصل البيانات - User fields vs ServiceProvider fields
        $userFields = array_intersect_key($data, array_flip(['phone_number', 'password']));
        $providerFields = array_intersect_key($data, array_flip([
            'name', 'name_en', 'description', 'description_en', 
            'location', 'location_en', 'address_url', 'landline_phone',
            'use_landline_for_calls', 'government_id', 'region_id'
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

    public function removeTypesFromServiceProvider($serviceProvider, $types)
    {
        return $serviceProvider->types()->detach($types);
    }

    public function findStatusByNameEn(string $nameEn)
    {
        return Status::where('name_en', $nameEn)->first();
    }

    public function typeBelongsToProviderRole($serviceProvider, int $typeId): bool
    {
        $providerRoleId = $serviceProvider->user?->role_id;

        if (! $providerRoleId) {
            return false;
        }

        return Type::where('id', $typeId)
            ->where('role_id', $providerRoleId)
            ->exists();
    }

    public function findServiceProviderModelById(int $id)
    {
        return ServiceProvider::find($id);
    }

    public function syncOrderStatusForImageUpdate($serviceProvider, $underReviewStatus): void
    {
        $orderStatus = $serviceProvider->orderStatusAble;
        if ($orderStatus) {
            $orderStatus->update([
                'status_id' => $underReviewStatus->id,
                'change_description' => 'Service provider images updated',
                'last_modified_at' => now(),
            ]);
        } else {
            OrderStatus::create([
                'orderable_id' => $serviceProvider->id,
                'orderable_type' => get_class($serviceProvider),
                'status_id' => $underReviewStatus->id,
                'change_description' => 'Service provider images updated',
                'last_modified_at' => now(),
            ]);
        }
    }
}
