<?php

namespace App\Http\Middleware;

use App\Services\ThemeResolver;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolves the active theme for the request and points the view finder at it.
 *
 * Phase 0/1 of the MENetZero 2.0 redesign (documentation/redesign.md).
 *
 * Two jobs, both of which MUST happen after the session has started:
 *
 *   1. Read ?theme= and persist it to the session, so the choice is sticky
 *      across navigation (requirement 5). Without the write, ?theme=new
 *      would apply to exactly one page and be lost on the first click.
 *
 *   2. Prepend the active theme's view directory to the view finder, so
 *      Blade resolves a theme's copy of a view first and the original
 *      second. The original path is never removed — that is the fallback,
 *      and it is why an unmigrated screen can never 404 (requirement 11).
 *
 * Job 2 lives here rather than in ThemeServiceProvider::boot() on purpose.
 * boot() runs before session middleware, so the session is still empty at
 * that point and the theme always resolved to the default — the view finder
 * would be frozen on the old theme while the layout (evaluated later, at
 * render time) correctly switched to the new one. That mismatch rendered
 * old page content inside the new shell.
 *
 * This middleware only reads the session and configures view paths. It never
 * alters routing, authentication, or authorisation: every route keeps its own
 * middleware stack untouched, whichever theme is active.
 */
class ResolveTheme
{
    /**
     * View paths already prepended this request, keyed by absolute path.
     *
     * The finder is a singleton and prependLocation() does not de-duplicate,
     * so without this guard a sub-request (or any second pass through the
     * web group) would stack the same path repeatedly.
     *
     * @var array<string, true>
     */
    protected static array $prepended = [];

    public function __construct(protected ThemeResolver $themes)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $this->applyRequestedTheme($request);
        $this->pointViewFinderAtTheme();

        return $next($request);
    }

    /**
     * Persist a ?theme= choice for the rest of the session.
     */
    protected function applyRequestedTheme(Request $request): void
    {
        $key = (string) config('themes.query_key', 'theme');

        if (! $request->has($key) || ! $this->themes->switchEnabled()) {
            return;
        }

        $requested = strtolower(trim((string) $request->query($key)));

        if ($requested === 'reset' || $requested === 'default') {
            $this->themes->forget();

            return;
        }

        // Unregistered values are ignored rather than erroring — a stray
        // ?theme=foo should not break the page.
        $this->themes->set($requested);
    }

    /**
     * Prepend the active theme's view directory to the finder.
     *
     * The default theme has no view_path, so nothing is prepended and view
     * resolution is byte-for-byte what it was before the theme layer existed.
     */
    protected function pointViewFinderAtTheme(): void
    {
        $path = $this->themes->config()['view_path'] ?? null;

        if ($path === null) {
            return;
        }

        $absolute = base_path($path);

        if (isset(static::$prepended[$absolute]) || ! is_dir($absolute)) {
            return;
        }

        static::$prepended[$absolute] = true;

        View::getFinder()->prependLocation($absolute);

        // Views resolved earlier in this request (before the theme was
        // known) are cached against their old paths. Clearing lets the
        // theme copy win.
        View::getFinder()->flush();
    }
}
