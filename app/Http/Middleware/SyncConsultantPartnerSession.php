<?php

namespace App\Http\Middleware;

use App\Services\ConsultantPartnerLinkService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * When a consultant is logged in, ensure the linked web User session exists
 * so agency routes under /consultant work from the same account (P15).
 */
class SyncConsultantPartnerSession
{
    public function handle(Request $request, Closure $next): Response
    {
        $consultant = Auth::guard('consultant')->user();

        if ($consultant && $consultant->is_active) {
            app(ConsultantPartnerLinkService::class)->syncWebSession($consultant);
        }

        return $next($request);
    }
}
