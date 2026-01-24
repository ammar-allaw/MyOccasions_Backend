<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckAdminAndPermissionMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next,$permissions=null): Response
    {
       
        $user_id = Auth::guard('sanctum')->user()?->id;
        if($user_id){
            $user=User::find($user_id);
        }
        else{
            $user=null;
        }

        // $permissions = explode('|', $permissions);
        // foreach ($permissions as $permission) {
        if($user != null){
            // dd($user->hasPermission($permission));
                if ($user->hasPermission($permissions)) {
                    return $next($request);
                }
                return response()->json(['error' => 'Unauthorized'], 403);

            }
        // }
        return response()->json(['error' => 'Unauthorized'], 403);
    }
}
