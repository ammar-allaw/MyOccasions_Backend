<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeadersMiddleware
{
    /**
     * Handle an incoming request and append security headers to the response.
     */
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        // Content Security Policy — restricts sources for scripts, styles, images, etc.
        // For a pure API (JSON only), a strict policy is safe and covers any edge case.
        $response->headers->set(
            'Content-Security-Policy',
            "default-src 'none'; frame-ancestors 'none';"
        );

        // Prevent browsers from MIME-type sniffing
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // Block clickjacking via iframes
        $response->headers->set('X-Frame-Options', 'DENY');

        // Force HTTPS on supported browsers (1 year)
        $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');

        // Disable referrer info leakage
        $response->headers->set('Referrer-Policy', 'no-referrer');

        // Restrict browser features/APIs
        $response->headers->set('Permissions-Policy', 'geolocation=(), microphone=(), camera=()');

        return $response;
    }
}
