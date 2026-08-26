<?php

namespace App\Providers;

use App\Services\ThemeResolver;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

/**
 * Wires the theme layer into the application.
 *
 * Phase 0 of the MENetZero 2.0 redesign (documentation/redesign.md).
 *
 * Everything here is additive. With the default theme ('old') nothing
 * below changes what renders: the namespace is registered but empty, the
 * shared variables are unused by existing views, and the Blade directives
 * are only referenced by new-theme views.
 */
class ThemeServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ThemeResolver::class, function ($app) {
            return new ThemeResolver($app['session.store']);
        });
    }

    public function boot(): void
    {
        $this->registerViewNamespaces();
        $this->shareThemeWithViews();
        $this->registerBladeDirectives();
    }

    /**
     * NOTE: the active theme's view directory is prepended to the view
     * finder by App\Http\Middleware\ResolveTheme, NOT here.
     *
     * boot() runs before session middleware, so the session is empty at
     * this point and the theme would always resolve to the default. Doing
     * the prepend here froze the finder on the old theme while the layout
     * — evaluated later, at render time — correctly switched to the new
     * one, rendering old page content inside the new shell.
     *
     * Namespace registration below is safe at boot because it is
     * theme-independent: every namespace is registered regardless of which
     * theme is active.
     */

    /**
     * Register each theme's view namespace.
     *
     * Namespaced lookups ('theme-new::dashboard.index') resolve against
     * resources/views/themes/new. When a view does not exist there,
     * ThemeResolver::view() falls back to the existing view path — which
     * is how an unfinished theme still renders every page.
     */
    protected function registerViewNamespaces(): void
    {
        foreach ((array) config('themes.themes', []) as $theme) {
            $namespace = $theme['view_namespace'] ?? null;
            $path = $theme['view_path'] ?? null;

            if ($namespace === null || $path === null) {
                continue;
            }

            $absolute = base_path($path);

            if (is_dir($absolute)) {
                View::addNamespace($namespace, $absolute);
            }
        }
    }

    /**
     * Make the active theme available to every view.
     *
     * Uses a callback so the theme is resolved per-request at render time,
     * not once at boot.
     */
    protected function shareThemeWithViews(): void
    {
        View::composer('*', function ($view) {
            $themes = $this->app->make(ThemeResolver::class);

            $view->with([
                'activeTheme' => $themes->current(),
                'isNewTheme' => $themes->isNew(),
                'themeAssets' => $themes->assets(),
            ]);
        });
    }

    /**
     * Blade helpers for theme-aware templates.
     *
     * @theme('new') ... @endtheme      — render only under a given theme
     * @themeview('dashboard.index')    — resolve a view name with fallback
     */
    protected function registerBladeDirectives(): void
    {
        Blade::if('theme', function (string $theme) {
            return app(ThemeResolver::class)->current() === $theme;
        });

        Blade::directive('themeview', function ($expression) {
            return "<?php echo app(\\App\\Services\\ThemeResolver::class)->view({$expression}); ?>";
        });
    }
}
