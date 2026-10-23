<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to the "home" route for your application.
     *
     * Typically, users are redirected here after authentication.
     *
     * @var string
     */
    public const HOME = '/home';

    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     */
    public function boot(): void
    {
        $this->configureRateLimiting();

        $this->routes(function () {
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api.php'));

            Route::middleware('web')
                ->group(base_path('routes/web.php'));
        });
    }

    /**
     * Configure the rate limiters for the application.
     */
    protected function configureRateLimiting(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('interactions', function (Request $request) {
            return Limit::perMinute(25)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('register-otp', function (Request $request) {
            return [
                Limit::perMinute(5)->by($this->otpPhoneRateLimitKey($request)),
                Limit::perMinute(20)->by($request->ip()),
            ];
        });

        RateLimiter::for('verify-register-otp', function (Request $request) {
            return [
                Limit::perMinute(10)->by($this->otpPhoneRateLimitKey($request)),
                Limit::perMinute(30)->by($request->ip()),
            ];
        });

        RateLimiter::for('resend-register-otp', function (Request $request) {
            return [
                Limit::perMinute(3)->by($this->otpPhoneRateLimitKey($request)),
                Limit::perMinute(15)->by($request->ip()),
            ];
        });
    }

    private function otpPhoneRateLimitKey(Request $request): string
    {
        $phoneNumber = $this->normalizeOtpPhoneNumberForRateLimit($request->input('phone_number'));

        return $phoneNumber ?: $request->ip();
    }

    private function normalizeOtpPhoneNumberForRateLimit(?string $phoneNumber): ?string
    {
        if ($phoneNumber === null) {
            return null;
        }

        $phoneNumber = preg_replace('/[\s\-\(\)]/', '', trim($phoneNumber));

        if ($phoneNumber === '') {
            return null;
        }

        if (str_starts_with($phoneNumber, '00')) {
            $phoneNumber = '+' . substr($phoneNumber, 2);
        }

        if (str_starts_with($phoneNumber, '+9630')) {
            return '+963' . substr($phoneNumber, 5);
        }

        if (str_starts_with($phoneNumber, '9630')) {
            return '+963' . substr($phoneNumber, 4);
        }

        if (str_starts_with($phoneNumber, '09')) {
            return '+963' . substr($phoneNumber, 1);
        }

        if (str_starts_with($phoneNumber, '963')) {
            return '+' . $phoneNumber;
        }

        return $phoneNumber;
    }
}
