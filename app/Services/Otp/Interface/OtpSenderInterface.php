<?php

namespace App\Services\Otp\Interface;

interface OtpSenderInterface
{
    public function send(string $phoneNumber, string $code): array;
}
