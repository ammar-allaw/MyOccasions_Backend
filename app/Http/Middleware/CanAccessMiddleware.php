<?php

namespace App\Http\Middleware;

use App\Models\Owner;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CanAccessMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, $isOwner = null, $isClient = null, $permissionName = null): Response
    {
        // Normalize bool-like flags
        $isOwner = $this->toBool($isOwner);
        $isClient = $this->toBool($isClient);

        // owner guard check
        $owner = false;
        if (Auth::guard('owner')->check()) {
            $owner = true;
        }

        // api user guard
        $user = null;
        if (Auth::guard('api')->check()) {
            $user = User::find(Auth::guard('api')->id());
        }

        if ($isOwner && $owner) {
            return $next($request);
        }

        if ($isClient && $user && $user->is_provider == false) {
            return $next($request);
        }

        if (!empty($permissionName) && $user && $user->is_provider == true) {
            if ($user->hasPermission($permissionName)) {
                return $next($request);
            }

            return response()->json(["success" => false, "message" => "Unauthorized - missing permission: $permissionName", "data" => null], 403);
        }

        return response()->json(["success" => false, "message" => "Unauthorized. Access conditions not met.", "data" => null], 403);
    }

    protected function toBool($value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_null($value)) {
            return false;
        }

        $value = trim(strtolower($value));

        if (in_array($value, ['1', 'true', 'yes', 'on', 'is_owner', 'owner', 'is_client', 'client'], true)) {
            return true;
        }

        if (in_array($value, ['0', 'false', 'no', 'off'], true)) {
            return false;
        }

        return false;
    }
}
