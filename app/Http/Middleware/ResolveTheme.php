<?php

namespace App\Http\Middleware;

use App\Services\ThemeResolver;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Reads ?theme= from the request and persists it to the session.
 *
 * Phase 0 of the MENetZero 2.0 redesign (documentation/redesign.md).
 *
 * The write-to-session step is the whole point. Without it, ?theme=new
 * would apply to exactly one page: navigating to any link would drop the
 * parameter and revert to the old theme. Persisting makes the choice
 * sticky for the rest of the session (requirement 5).
 *
 * Registered globally on the 'web' group so any route can be previewed,
 * but it only ever writes a session value — it never alters routing,
 * authentication, or authorisation. Every route keeps its own middleware
 * stack untouched.
 */
class ResolveTheme
{
    public function __construct(protected ThemeResolver $themes)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $key = (string) config('themes.query_key', 'theme');

        if ($request->has($key) && $this->themes->switchEnabled()) {
            $requested = strtolower(trim((string) $request->query($key)));

            if ($requested === 'reset' || $requested === 'default') {
                $this->themes->forget();
            } else {
                // Unregistered values are ignored rather than erroring —
                // a stray ?theme=foo should not break the page.
                $this->themes->set($requested);
            }
        }

        return $next($request);
    }
}
