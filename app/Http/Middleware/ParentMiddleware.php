<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class ParentMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {

        if(!empty(Auth::check())) {
            if(Auth::user()->role == 'parent') {
                return $next($request);
            }
            else {
                Auth::logout();
                return redirect()->route('admin.login')->with('error', 'You are not authorized to access this page.');
            }
        }
        else{
            Auth::logout();
            return redirect()->route('admin.login')->with('error', 'You are not logged in as an admin.');
        }


    }
}
