<?php

namespace App\Repositories\PendingRegistration\Implementation;

use App\Models\PendingRegistration;
use App\Repositories\PendingRegistration\Interface\PendingRegistrationRepositoryInterface;

class PendingRegistrationRepository implements PendingRegistrationRepositoryInterface
{
    public function findByPhoneNumber(string $phoneNumber): ?PendingRegistration
    {
        return PendingRegistration::query()
            ->where('phone_number', $phoneNumber)
            ->first();
    }

    public function updateOrCreateByPhoneNumber(string $phoneNumber, array $data): PendingRegistration
    {
        return PendingRegistration::query()->updateOrCreate(
            ['phone_number' => $phoneNumber],
            $data
        );
    }

    public function incrementAttempts(PendingRegistration $pendingRegistration): void
    {
        $pendingRegistration->increment('attempts');
    }

    public function delete(PendingRegistration $pendingRegistration): bool
    {
        return (bool) $pendingRegistration->delete();
    }
}
