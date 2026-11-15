<?php

namespace App\Services\Otp\Interface;

interface PasswordResetOtpServiceInterface
{
    public function send(array $data): array;

    public function verify(array $data): array;

    public function resetPassword(array $data): void;
}
