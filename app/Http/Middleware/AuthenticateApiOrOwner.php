<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateApiOrOwner
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // التحقق من guard api أولاً
        if (Auth::guard('api')->check()) {
            return $next($request);
        }
        
        // التحقق من guard owner
        if (Auth::guard('owner')->check()) {
            return $next($request);
        }
        
        // إذا لم يكن مصادق في أي من الـ guards
        return response()->json([
            'success' => false,
            'message' => 'Unauthenticated. Please login first.',
            'data' => null
        ], 401);
    }
}
