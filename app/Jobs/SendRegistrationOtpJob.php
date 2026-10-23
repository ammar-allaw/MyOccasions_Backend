<?php

namespace App\Jobs;

use App\Services\Otp\Interface\OtpSenderInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendRegistrationOtpJob implements ShouldQueue, ShouldBeEncrypted
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [10, 30, 60];

    public int $timeout = 20;

    public function __construct(
        private string $phoneNumber,
        private string $code,
    ) {
        $this->onQueue('otp');
        $this->afterCommit();
    }

    public function handle(OtpSenderInterface $otpSender): void
    {
        $otpSender->send($this->phoneNumber, $this->code);
    }
}
