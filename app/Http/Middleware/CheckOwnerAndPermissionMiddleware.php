<?php

namespace App\Http\Middleware;

use App\Models\Owner;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckOwnerAndPermissionMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next,$permissions=null): Response
    {
        $owner_id = Auth::guard('owner')->user()?->id;
        if($owner_id){
            $owner=Owner::find($owner_id);
        }
        else{
            $owner=null;
        }
        // $permissions = explode('|', $permissions);
        // foreach ($permissions as $permission) {
        if($owner != null){
            // dd($user->hasPermission($permission));
                if ($owner->hasPermission($permissions)) {
                    return $next($request);
                }
                return response()->json(['error' => 'Unauthorized'], 403);

            }
        return response()->json(['error' => 'Unauthorized'], 403);
    }
}
