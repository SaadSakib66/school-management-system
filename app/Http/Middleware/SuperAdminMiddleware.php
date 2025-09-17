<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SuperAdminMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user(); // Authenticatable|null

        if (!$user || $user->role !== 'super_admin') {
            abort(403);
        }

        return $next($request);
    }
}
