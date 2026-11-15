<?php

namespace App\Services\Otp\Implementation;

use App\Exceptions\ApiResponseException;
use App\Jobs\SendPasswordResetOtpJob;
use App\Repositories\PasswordResetOtp\Interface\PasswordResetOtpRepositoryInterface;
use App\Repositories\User\Interface\UserRepositoryInterface;
use App\Services\Otp\Interface\PasswordResetOtpServiceInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class PasswordResetOtpService implements PasswordResetOtpServiceInterface
{
    private const OTP_TTL_MINUTES = 5;
    private const MAX_VERIFY_ATTEMPTS = 5;
    private const RESEND_COOLDOWN_SECONDS = 60;
    private const RESET_TOKEN_TTL_MINUTES = 10;

    public function __construct(
        private UserRepositoryInterface $userRepository,
        private PasswordResetOtpRepositoryInterface $passwordResetOtpRepository,
    ) {}

    public function send(array $data): array
    {
        $accountType = $data['account_type'] ?? 'client';
        $isProvider = $accountType === 'service_provider';
        $user = $this->userRepository->findByPhoneNumberAndProviderStatus(
            $data['phone_number'],
            $isProvider
        );

        if (! $user) {
            throw new ApiResponseException($this->message('user_not_found'), 404, null);
        }

        $existingOtp = $this->passwordResetOtpRepository->findByUserId($user->id);

        if ($existingOtp?->last_sent_at) {
            $elapsedSeconds = $existingOtp->last_sent_at->diffInSeconds(now());

            if ($elapsedSeconds < self::RESEND_COOLDOWN_SECONDS) {
                throw new ApiResponseException($this->message('resend_cooldown'), 429, [
                    'retry_after' => self::RESEND_COOLDOWN_SECONDS - $elapsedSeconds,
                ]);
            }
        }

        [$phoneNumber, $code] = DB::transaction(function () use ($user) {
            $code = (string) random_int(100000, 999999);

            $passwordResetOtp = $this->passwordResetOtpRepository->updateOrCreateForUser(
                $user->id,
                [
                    'phone_number' => $user->phone_number,
                    'otp_hash' => Hash::make($code),
                    'attempts' => 0,
                    'expires_at' => now()->addMinutes(self::OTP_TTL_MINUTES),
                    'last_sent_at' => now(),
                    'verified_at' => null,
                    'reset_token_hash' => null,
                    'reset_token_expires_at' => null,
                ]
            );

            return [$passwordResetOtp->phone_number, $code];
        });

        SendPasswordResetOtpJob::dispatch($phoneNumber, $code);

        return [
            'phone_number' => $phoneNumber,
            'account_type' => $accountType,
            'expires_in' => self::OTP_TTL_MINUTES * 60,
            'resend_after' => self::RESEND_COOLDOWN_SECONDS,
        ];
    }

    public function verify(array $data): array
    {
        $user = $this->findTargetUser($data);

        $result = DB::transaction(function () use ($user, $data) {
            $passwordResetOtp = $this->passwordResetOtpRepository->findByUserIdForUpdate($user->id);

            if (! $passwordResetOtp) {
                throw new ApiResponseException($this->message('password_reset_not_found'), 404, null);
            }

            if ($passwordResetOtp->verified_at) {
                throw new ApiResponseException($this->message('otp_already_verified'), 422, null);
            }

            if ($passwordResetOtp->expires_at->isPast()) {
                throw new ApiResponseException($this->message('otp_expired'), 422, null);
            }

            if ($passwordResetOtp->attempts >= self::MAX_VERIFY_ATTEMPTS) {
                throw new ApiResponseException($this->message('max_attempts_exceeded'), 429, null);
            }

            if (! Hash::check($data['code'], $passwordResetOtp->otp_hash)) {
                $this->passwordResetOtpRepository->incrementAttempts($passwordResetOtp);

                return [
                    'verified' => false,
                    'remaining_attempts' => max(
                        0,
                        self::MAX_VERIFY_ATTEMPTS - ($passwordResetOtp->attempts + 1)
                    ),
                ];
            }

            $resetToken = bin2hex(random_bytes(32));

            $this->passwordResetOtpRepository->update($passwordResetOtp, [
                'verified_at' => now(),
                'reset_token_hash' => hash('sha256', $resetToken),
                'reset_token_expires_at' => now()->addMinutes(self::RESET_TOKEN_TTL_MINUTES),
            ]);

            return [
                'verified' => true,
                'reset_token' => $resetToken,
                'expires_in' => self::RESET_TOKEN_TTL_MINUTES * 60,
            ];
        });

        if (! $result['verified']) {
            throw new ApiResponseException($this->message('invalid_otp'), 422, [
                'remaining_attempts' => $result['remaining_attempts'],
            ]);
        }

        unset($result['verified']);

        return $result;
    }

    public function resetPassword(array $data): void
    {
        DB::transaction(function () use ($data) {
            $resetTokenHash = hash('sha256', $data['reset_token']);
            $passwordResetOtp = $this->passwordResetOtpRepository
                ->findByResetTokenHashForUpdate($resetTokenHash);

            if (! $passwordResetOtp || ! $passwordResetOtp->verified_at) {
                throw new ApiResponseException($this->message('invalid_reset_token'), 422, null);
            }

            if (! $passwordResetOtp->reset_token_expires_at
                || $passwordResetOtp->reset_token_expires_at->isPast()) {
                throw new ApiResponseException($this->message('reset_token_expired'), 422, null);
            }

            $this->userRepository->updateUser($passwordResetOtp->user_id, [
                'password' => Hash::make($data['new_password']),
            ]);
            $this->userRepository->revokeAllTokens($passwordResetOtp->user_id);
            $this->passwordResetOtpRepository->delete($passwordResetOtp);
        });
    }

    private function findTargetUser(array $data)
    {
        $accountType = $data['account_type'] ?? 'client';
        $user = $this->userRepository->findByPhoneNumberAndProviderStatus(
            $data['phone_number'],
            $accountType === 'service_provider'
        );

        if (! $user) {
            throw new ApiResponseException($this->message('user_not_found'), 404, null);
        }

        return $user;
    }

    private function message(string $key): string
    {
        $locale = str_starts_with(
            strtolower((string) request()->header('Accept-Language', 'ar')),
            'en'
        ) ? 'en' : 'ar';

        $messages = [
            'ar' => [
                'user_not_found' => 'لا يوجد حساب مطابق لرقم الهاتف ونوع الحساب المحددين',
                'resend_cooldown' => 'يرجى الانتظار قبل طلب رمز تحقق جديد',
                'password_reset_not_found' => 'لا يوجد طلب لإعادة تعيين كلمة المرور لهذا الحساب',
                'otp_already_verified' => 'تم استخدام رمز التحقق مسبقًا، يرجى طلب رمز جديد',
                'otp_expired' => 'انتهت صلاحية رمز التحقق',
                'max_attempts_exceeded' => 'تم تجاوز الحد الأقصى لمحاولات التحقق',
                'invalid_otp' => 'رمز التحقق غير صحيح',
                'invalid_reset_token' => 'رمز إعادة تعيين كلمة المرور غير صالح',
                'reset_token_expired' => 'انتهت صلاحية رمز إعادة تعيين كلمة المرور',
            ],
            'en' => [
                'user_not_found' => 'No account matches the provided phone number and account type',
                'resend_cooldown' => 'Please wait before requesting another OTP code',
                'password_reset_not_found' => 'No password reset request exists for this account',
                'otp_already_verified' => 'OTP code has already been used; request a new code',
                'otp_expired' => 'OTP code has expired',
                'max_attempts_exceeded' => 'Maximum OTP attempts exceeded',
                'invalid_otp' => 'Invalid OTP code',
                'invalid_reset_token' => 'Invalid password reset token',
                'reset_token_expired' => 'Password reset token has expired',
            ],
        ];

        return $messages[$locale][$key] ?? $key;
    }
}
