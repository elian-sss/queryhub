<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class ForcePasswordChange
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check() && Auth::user()->password_change_required) {
            if ($request->routeIs('password.change') || $request->routeIs('password.updateFirst') || $request->routeIs('logout')) {
                return $next($request);
            }
            return redirect()->route('password.change');
        }

        return $next($request);
    }
}
