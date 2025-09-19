<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class Authenticate
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        return $next($request);
    }

    protected function redirectTo($request): ?string
    {
        if (! $request->expectsJson()) {
            // Redirect to different login pages based on route prefix
            if ($request->is('admin/*')) {
                return route('admin.login');
            }
            
            // Default login route (optional)
            return route('admin.login');
        }

        return null;
    }
}
