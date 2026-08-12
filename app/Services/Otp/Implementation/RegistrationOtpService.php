<?php

namespace App\Services\Otp\Implementation;

use App\Exceptions\ApiResponseException;
use App\Jobs\SendRegistrationOtpJob;
use App\Models\User;
use App\Repositories\PendingRegistration\Interface\PendingRegistrationRepositoryInterface;
use App\Services\Otp\Interface\RegistrationOtpServiceInterface;
use App\Services\User\Interface\UserServiceInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class RegistrationOtpService implements RegistrationOtpServiceInterface
{
    private const OTP_TTL_MINUTES = 5;
    private const MAX_VERIFY_ATTEMPTS = 5;
    private const RESEND_COOLDOWN_SECONDS = 60;

    public function __construct(
        private PendingRegistrationRepositoryInterface $pendingRegistrationRepository,
        private UserServiceInterface $userService,
    ) {}

    public function start(array $data): array
    {
        [$phoneNumber, $code] = DB::transaction(function () use ($data) {
            $code = $this->generateCode();

            $pendingRegistration = $this->pendingRegistrationRepository->updateOrCreateByPhoneNumber(
                $data['phone_number'],
                [
                    'first_name' => $data['first_name'],
                    'last_name' => $data['last_name'],
                    'password' => Hash::make($data['password']),
                    'government_id' => $data['government_id'],
                    'otp_hash' => Hash::make($code),
                    'attempts' => 0,
                    'expires_at' => now()->addMinutes(self::OTP_TTL_MINUTES),
                    'last_sent_at' => now(),
                ]
            );

            return [$pendingRegistration->phone_number, $code];
        });

        SendRegistrationOtpJob::dispatch($phoneNumber, $code);

        return $this->otpResponseData($phoneNumber);
    }

    public function resend(string $phoneNumber): array
    {
        $pendingRegistration = $this->pendingRegistrationRepository->findByPhoneNumber($phoneNumber);

        if (! $pendingRegistration) {
            throw new ApiResponseException('No pending registration found for this phone number', 404, null);
        }

        if ($pendingRegistration->last_sent_at && $pendingRegistration->last_sent_at->diffInSeconds(now()) < self::RESEND_COOLDOWN_SECONDS) {
            throw new ApiResponseException('Please wait before requesting another OTP code', 429, [
                'retry_after' => self::RESEND_COOLDOWN_SECONDS - $pendingRegistration->last_sent_at->diffInSeconds(now()),
            ]);
        }

        [$phoneNumber, $code] = DB::transaction(function () use ($phoneNumber) {
            $code = $this->generateCode();

            $pendingRegistration = $this->pendingRegistrationRepository->updateOrCreateByPhoneNumber(
                $phoneNumber,
                [
                    'otp_hash' => Hash::make($code),
                    'attempts' => 0,
                    'expires_at' => now()->addMinutes(self::OTP_TTL_MINUTES),
                    'last_sent_at' => now(),
                ]
            );

            return [$pendingRegistration->phone_number, $code];
        });

        SendRegistrationOtpJob::dispatch($phoneNumber, $code);

        return $this->otpResponseData($phoneNumber);
    }

    public function verify(string $phoneNumber, string $code): User
    {
        $pendingRegistration = $this->pendingRegistrationRepository->findByPhoneNumber($phoneNumber);

        if (! $pendingRegistration) {
            throw new ApiResponseException('No pending registration found for this phone number', 404, null);
        }

        if ($pendingRegistration->expires_at->isPast()) {
            throw new ApiResponseException('OTP code has expired', 422, null);
        }

        if ($pendingRegistration->attempts >= self::MAX_VERIFY_ATTEMPTS) {
            throw new ApiResponseException('Maximum OTP attempts exceeded', 429, null);
        }

        if (! Hash::check($code, $pendingRegistration->otp_hash)) {
            $this->pendingRegistrationRepository->incrementAttempts($pendingRegistration);

            throw new ApiResponseException('Invalid OTP code', 422, [
                'remaining_attempts' => max(0, self::MAX_VERIFY_ATTEMPTS - ($pendingRegistration->attempts + 1)),
            ]);
        }

        return DB::transaction(function () use ($pendingRegistration) {
            $data = [
                'first_name' => $pendingRegistration->first_name,
                'last_name' => $pendingRegistration->last_name,
                'phone_number' => $pendingRegistration->phone_number,
                'password' => $pendingRegistration->password,
                'government_id' => $pendingRegistration->government_id,
            ];

            $client = $this->userService->createClient($data);
            $user = $this->userService->createUser($data, $client);
            $user->load('role');

            $this->pendingRegistrationRepository->delete($pendingRegistration);

            return $user;
        });
    }

    private function generateCode(): string
    {
        return (string) random_int(100000, 999999);
    }

    private function otpResponseData(string $phoneNumber): array
    {
        return [
            'phone_number' => $phoneNumber,
            'expires_in' => self::OTP_TTL_MINUTES * 60,
            'resend_after' => self::RESEND_COOLDOWN_SECONDS,
        ];
    }
}
