<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * Usage:
     *  - role:admin
     *  - role:teacher
     *  - role:student
     *  - role:parent
     *  - role:any,admin,teacher  (any of admin|teacher)
     */
    public function handle(Request $request, Closure $next, ...$roles)
    {
        $user = Auth::user();
        if (!$user) {
            abort(401);
        }

        // Allow "any" + list: role:any,admin,teacher
        if (isset($roles[0]) && $roles[0] === 'any') {
            array_shift($roles);
        }

        // If no roles provided, deny.
        if (empty($roles)) {
            abort(403, 'Role not allowed.');
        }

        if (! in_array($user->role, $roles, true)) {
            abort(403, 'Unauthorized role.');
        }

        return $next($request);
    }
}
