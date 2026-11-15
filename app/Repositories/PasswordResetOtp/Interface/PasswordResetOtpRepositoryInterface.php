<?php

namespace App\Repositories\PasswordResetOtp\Interface;

use App\Models\PasswordResetOtp;

interface PasswordResetOtpRepositoryInterface
{
    public function findByUserId(int $userId): ?PasswordResetOtp;

    public function findByUserIdForUpdate(int $userId): ?PasswordResetOtp;

    public function findByResetTokenHashForUpdate(string $resetTokenHash): ?PasswordResetOtp;

    public function updateOrCreateForUser(int $userId, array $data): PasswordResetOtp;

    public function update(PasswordResetOtp $passwordResetOtp, array $data): PasswordResetOtp;

    public function incrementAttempts(PasswordResetOtp $passwordResetOtp): void;

    public function delete(PasswordResetOtp $passwordResetOtp): bool;
}
