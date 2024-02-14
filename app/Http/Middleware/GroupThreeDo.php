<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\User;

class GroupThreeDo
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $userId = $request->route('my_user_id');
        $user = User::select('role')->where('id',$userId)->firstOrFail();
        if ($user && (int)$user->role === 1 || (int)$user->role === 2 || (int)$user->role === 3 || (int)$user->role === 4 ){
            return  $next($request);
        }
        return response()->json(['message' => "Permission denied!"], 401);
    }
}
