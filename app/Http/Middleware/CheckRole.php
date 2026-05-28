<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  ...$roles
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        // Ensure the user is logged in
        if (!$request->user()) {
            return redirect()->route('login');
        }

        // Check if the user's role matches any allowed roles
        // (Assumes you have a 'role' column or relationship on your User model)
        if (!in_array($request->user()->role, $roles)) {
            abort(403, 'Mohon maaf, Anda tidak dapat mengakses halaman ini.');
        }

        return $next($request);
    }
}