<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SchoolActiveMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if ($user && $user->role !== 'super_admin') {
            $school = $user->school; // relation on User

            if (!$school || (int) $school->status === 0) {
                Auth::logout();

                return redirect()
                    ->route('admin.login.page')
                    ->with('error', 'Your school is inactive. Please contact support.');
            }
        }

        return $next($request);
    }
}
