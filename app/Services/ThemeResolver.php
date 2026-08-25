<?php

namespace App\Services;

use Illuminate\Contracts\Session\Session;

/**
 * Resolves which theme should render the current request.
 *
 * Phase 0 of the MENetZero 2.0 redesign (documentation/redesign.md).
 *
 * Resolution order — first hit wins:
 *
 *   1. Session      — a ?theme= choice made earlier in this session.
 *   2. Company/user — reserved for Phase 6 opt-in (not read yet; there is
 *                     no theme column and adding one is a Phase 6 task).
 *   3. Config       — config('themes.default'), which is 'old'.
 *
 * Session-first is deliberate: it means testing can never leak to a client,
 * because a tester's session choice is scoped to that session alone.
 */
class ThemeResolver
{
    public const THEME_OLD = 'old';
    public const THEME_NEW = 'new';

    public function __construct(protected Session $session)
    {
    }

    /**
     * The theme for the current request. Always returns a registered theme.
     */
    public function current(): string
    {
        $sessionTheme = $this->session->get($this->sessionKey());

        if (is_string($sessionTheme) && $this->isRegistered($sessionTheme)) {
            return $sessionTheme;
        }

        // Tier 2 (company/user preference) lands here in Phase 6.

        return $this->defaultTheme();
    }

    /**
     * Persist a theme choice for the rest of this session.
     *
     * Called by the ResolveTheme middleware when it sees ?theme=, and by
     * the theme switch route. Writing to the session is what makes the
     * choice sticky across navigation (requirement 5) — a bare query
     * param would be lost on the first link click.
     */
    public function set(string $theme): bool
    {
        if (! $this->isRegistered($theme)) {
            return false;
        }

        $this->session->put($this->sessionKey(), $theme);

        return true;
    }

    /**
     * Drop any session override, falling back to the default theme.
     */
    public function forget(): void
    {
        $this->session->forget($this->sessionKey());
    }

    public function isNew(): bool
    {
        return $this->current() === self::THEME_NEW;
    }

    public function isOld(): bool
    {
        return $this->current() === self::THEME_OLD;
    }

    /**
     * Whether ?theme= switching is currently permitted at all.
     *
     * Set THEME_SWITCH_ENABLED=false in .env to kill switching instantly
     * without a deploy (see redesign.md section 7A.1).
     */
    public function switchEnabled(): bool
    {
        return (bool) config('themes.switch_enabled', true);
    }

    public function isRegistered(string $theme): bool
    {
        return array_key_exists($theme, (array) config('themes.themes', []));
    }

    public function defaultTheme(): string
    {
        $default = (string) config('themes.default', self::THEME_OLD);

        return $this->isRegistered($default) ? $default : self::THEME_OLD;
    }

    /**
     * Configuration for a theme (label, view namespace, assets, layouts).
     */
    public function config(?string $theme = null): array
    {
        $theme ??= $this->current();

        return (array) config("themes.themes.{$theme}", []);
    }

    /**
     * The view namespace for a theme, or null when it uses default paths.
     */
    public function viewNamespace(?string $theme = null): ?string
    {
        return $this->config($theme)['view_namespace'] ?? null;
    }

    /**
     * Extra stylesheets/scripts a theme needs, already asset()-resolved.
     */
    public function assets(?string $theme = null): array
    {
        $assets = $this->config($theme)['assets'] ?? [];

        if (empty($assets)) {
            return ['css' => [], 'js' => []];
        }

        $version = $assets['version'] ?? null;
        $suffix = $version ? '?v=' . $version : '';

        return [
            'css' => array_map(fn ($p) => asset($p) . $suffix, $assets['css'] ?? []),
            'js' => array_map(fn ($p) => asset($p) . $suffix, $assets['js'] ?? []),
        ];
    }

    /**
     * Resolve a view name against the current theme, falling back to the
     * existing view when the theme has not built that screen yet.
     *
     * This is the mechanism behind requirement 11: an unbuilt page renders
     * existing content rather than producing a broken route. It is what
     * lets the new theme ship 10%-complete and still work everywhere.
     */
    public function view(string $view, ?string $theme = null): string
    {
        $namespace = $this->viewNamespace($theme);

        if ($namespace === null) {
            return $view;
        }

        $namespaced = "{$namespace}::{$view}";

        return view()->exists($namespaced) ? $namespaced : $view;
    }

    /**
     * Resolve a LAYOUT name for @extends().
     *
     * Distinct from view() because layouts are resolved by pages via
     * @extends($themeLayout), which the existing codebase already does in
     * six places (roles/*, staff/*, zero-ai/*). Returning the theme's own
     * layout when it exists, and the original otherwise, means an auth page
     * needs no edit at all — it extends whichever shell is active.
     *
     * Shared with every view as $themeLayout by ThemeServiceProvider.
     */
    public function layout(string $layout, ?string $theme = null): string
    {
        return $this->view($layout, $theme);
    }

    /**
     * Whether the current theme has its own copy of a view.
     * Useful for telling "migrated" from "falling back" in diagnostics.
     */
    public function hasThemeView(string $view, ?string $theme = null): bool
    {
        $namespace = $this->viewNamespace($theme);

        return $namespace !== null && view()->exists("{$namespace}::{$view}");
    }

    protected function sessionKey(): string
    {
        return (string) config('themes.session_key', 'mnz_theme');
    }
}
