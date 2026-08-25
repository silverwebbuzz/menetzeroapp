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
        $this->registerThemeViewOverrides();
        $this->shareThemeWithViews();
        $this->registerBladeDirectives();
    }

    /**
     * Let a theme override a view without editing the controller that
     * returns it.
     *
     * Some views are returned by name from a controller — for example
     * AdminLoginController does `return view('admin.auth.login')`. Rather
     * than edit those controllers (which would break rule P3: additive
     * only), we prepend the theme directory to the view finder's search
     * paths. Blade then finds resources/views/themes/new/<name> first when
     * it exists, and the original everywhere else.
     *
     * This IS the fallback mechanism for controller-returned views, and it
     * is why an unmigrated screen can never 404: the original path is still
     * searched, just second.
     */
    protected function registerThemeViewOverrides(): void
    {
        $themes = $this->app->make(ThemeResolver::class);
        $active = $themes->current();

        $path = $themes->config($active)['view_path'] ?? null;

        if ($path === null) {
            return;
        }

        $absolute = base_path($path);

        if (is_dir($absolute)) {
            View::getFinder()->prependLocation($absolute);
        }
    }

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
                // Layout names, resolved per theme with fallback. Pages use
                // @extends($authLayout) so no page file needs editing when a
                // theme ships its own shell.
                'authLayout' => $themes->layout('layouts.portal-auth'),
                'appLayout' => $themes->layout('layouts.app'),
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
