<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateOwner
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::guard('owner')->check()) {
            return $next($request);
        }

        return response()->json([
            'success' => false,
            'message' => 'Unauthorized. Owner access only.',
            'data' => null,
        ], 401);
    }
}
