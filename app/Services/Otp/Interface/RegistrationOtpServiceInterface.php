<?php

namespace App\Services\Otp\Interface;

use App\Models\User;

interface RegistrationOtpServiceInterface
{
    public function start(array $data): array;

    public function resend(string $phoneNumber): array;

    public function verify(string $phoneNumber, string $code): User;
}
