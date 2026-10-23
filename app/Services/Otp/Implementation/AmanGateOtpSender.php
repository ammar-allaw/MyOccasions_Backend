<?php

namespace App\Services\Otp\Implementation;

use App\Exceptions\ApiResponseException;
use App\Services\Otp\Interface\OtpSenderInterface;
use Illuminate\Support\Facades\Http;

class AmanGateOtpSender implements OtpSenderInterface
{
    public function send(string $phoneNumber, string $code): array
    {
        $token = config('services.aman_gate.token');
        $templateId = config('services.aman_gate.otp_template_id');
        $baseUrl = rtrim((string) config('services.aman_gate.base_url'), '/');
        $language = (int) config('services.aman_gate.language', 1);

        if (! $token || ! $templateId || ! $baseUrl) {
            throw new ApiResponseException('OTP service is not configured', 500, null);
        }

        $response = Http::timeout(10)
            ->acceptJson()
            ->withHeaders([
                'Authorization' => "Token {$token}",
            ])
            ->post("{$baseUrl}/otp/send/", [
                'gsm' => ltrim($phoneNumber, '+'),
                'template_id' => (int) $templateId,
                'code' => $code,
                'language' => $language,
            ]);

        if (! $response->successful()) {
            throw new ApiResponseException('Failed to send OTP code', 502, [
                'otp_provider_status' => $response->status(),
            ]);
        }

        return $response->json() ?? [];
    }
}
