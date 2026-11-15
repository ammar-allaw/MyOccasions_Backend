<?php

namespace App\Repositories\PasswordResetOtp\Implementation;

use App\Models\PasswordResetOtp;
use App\Repositories\PasswordResetOtp\Interface\PasswordResetOtpRepositoryInterface;

class PasswordResetOtpRepository implements PasswordResetOtpRepositoryInterface
{
    public function findByUserId(int $userId): ?PasswordResetOtp
    {
        return PasswordResetOtp::query()
            ->where('user_id', $userId)
            ->first();
    }

    public function findByUserIdForUpdate(int $userId): ?PasswordResetOtp
    {
        return PasswordResetOtp::query()
            ->where('user_id', $userId)
            ->lockForUpdate()
            ->first();
    }

    public function findByResetTokenHashForUpdate(string $resetTokenHash): ?PasswordResetOtp
    {
        return PasswordResetOtp::query()
            ->where('reset_token_hash', $resetTokenHash)
            ->lockForUpdate()
            ->first();
    }

    public function updateOrCreateForUser(int $userId, array $data): PasswordResetOtp
    {
        return PasswordResetOtp::query()->updateOrCreate(
            ['user_id' => $userId],
            $data
        );
    }

    public function update(PasswordResetOtp $passwordResetOtp, array $data): PasswordResetOtp
    {
        $passwordResetOtp->update($data);

        return $passwordResetOtp->refresh();
    }

    public function incrementAttempts(PasswordResetOtp $passwordResetOtp): void
    {
        $passwordResetOtp->increment('attempts');
    }

    public function delete(PasswordResetOtp $passwordResetOtp): bool
    {
        return (bool) $passwordResetOtp->delete();
    }
}
