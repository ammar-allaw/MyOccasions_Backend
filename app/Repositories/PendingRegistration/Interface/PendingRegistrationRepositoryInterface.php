<?php

namespace App\Repositories\PendingRegistration\Interface;

use App\Models\PendingRegistration;

interface PendingRegistrationRepositoryInterface
{
    public function findByPhoneNumber(string $phoneNumber): ?PendingRegistration;

    public function updateOrCreateByPhoneNumber(string $phoneNumber, array $data): PendingRegistration;

    public function incrementAttempts(PendingRegistration $pendingRegistration): void;

    public function delete(PendingRegistration $pendingRegistration): bool;
}
