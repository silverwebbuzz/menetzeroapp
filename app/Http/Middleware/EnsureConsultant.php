<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureConsultant
{
    public function handle(Request $request, Closure $next): Response
    {
        $consultant = Auth::guard('consultant')->user();

        if (!$consultant || !$consultant->is_active) {
            return redirect()->route('consultant.login')
                ->with('error', 'Please sign in to your consultant account.');
        }

        return $next($request);
    }
}
