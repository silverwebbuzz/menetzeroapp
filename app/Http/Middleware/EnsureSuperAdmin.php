<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSuperAdmin
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check admin guard authentication
        if (!auth()->guard('admin')->check()) {
            return redirect()->route('admin.login');
        }

        return $next($request);
    }
}

