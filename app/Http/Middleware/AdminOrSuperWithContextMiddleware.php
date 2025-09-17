<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AdminOrSuperWithContextMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        // Allow school admin directly
        if ($user && $user->role === 'admin') {
            return $next($request);
        }

        // Allow super admin only if a school is selected in session
        if ($user && $user->role === 'super_admin') {
            $currentSchoolId = (int) $request->session()->get('current_school_id', 0);

            if ($currentSchoolId > 0) {
                return $next($request);
            }

            return redirect()
                ->route('superadmin.schools.switch')
                ->with('error', 'Please select a school first.');
        }

        abort(403);
    }
}
