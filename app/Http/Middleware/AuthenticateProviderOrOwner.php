<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateProviderOrOwner
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next,$roleName = null): Response
    {
        // التحقق من guard api والتأكد من أن is_provider = true
        if (Auth::guard('api')->check()) {
            $userAuth = Auth::guard('api')->id();
            $user = User::find($userAuth);
            // dd($roleName);
            if(!$roleName == null)
            {
                if ($user && $user->is_provider == true &&  $user->role->name_en === $roleName) 
                {
                    return $next($request);
                }
            }else{
                if ($user && $user->is_provider == true) 
                {
                    return $next($request);
                }
            }
            
        }
        
        // التحقق من guard owner
        if (Auth::guard('owner')->check()) {

            return $next($request);
        }
        
        // إذا لم يتحقق أي من الشرطين
        return response()->json([
            'success' => false,
            'message' => 'Unauthorized. You must be a provider or owner to access this resource.',
            'data' => null
        ], 403);
    }
}
