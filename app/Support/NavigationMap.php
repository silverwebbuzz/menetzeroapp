<?php

namespace App\Support;

use Illuminate\Support\Facades\Route;

/**
 * Renders config/navigation.php into theme-agnostic link data.
 *
 * BOTH themes' nav-client.blade.php call build() and then apply their own
 * markup. All logic that can be wrong — route resolution, active state,
 * fiscal-year propagation, permission gating — lives here, once, so the two
 * themes cannot drift.
 *
 * Nothing in this class emits HTML. It returns plain arrays.
 */
class NavigationMap
{
    /**
     * The reporting year the sidebar should link to.
     *
     * DisclosureBaseController::resolveContext() writes
     * session('disclosure_fiscal_year') on every disclosure page load, so
     * reading it here keeps the nav on the year the user is actually
     * working in. Without this, a nav click would drop ?fiscal_year= and
     * silently move the user to the current calendar year — editing 2026
     * data while believing they are in 2025.
     */
    public static function fiscalYear(): int
    {
        return (int) session('disclosure_fiscal_year', now()->year);
    }

    /**
     * Build the sidebar for the current request.
     *
     * @param  array<string, bool>  $gates      From nav-gates.blade.php ($navGates).
     * @param  int|null             $fiscalYear Year to carry on 'year' => true links.
     *                                          Defaults to the session year.
     * @return array{groups: list<array<string, mixed>>, footer: array<string, mixed>}
     */
    public static function build(array $gates, ?int $fiscalYear = null): array
    {
        $fiscalYear ??= self::fiscalYear();
        $config = (array) config('navigation', []);
        $currentRoute = Route::currentRouteName() ?? '';
        $yearKey = $config['fiscal_year_key'] ?? 'fiscal_year';

        $groups = [];
        foreach ((array) ($config['groups'] ?? []) as $key => $group) {
            if (! self::passes($group['gate'] ?? 'always', $gates)) {
                continue;
            }

            $items = self::items($group['items'] ?? [], $gates, $currentRoute, $fiscalYear, $yearKey);

            // A group whose every item is gated away renders nothing —
            // otherwise the user sees a bare heading with no links.
            if ($items === []) {
                continue;
            }

            $groups[] = [
                'key' => $key,
                'title' => $group['title'] ?? null,
                'pillar' => $group['pillar'] ?? null,
                'items' => $items,
            ];
        }

        $footer = (array) ($config['footer'] ?? []);
        $footerItems = self::items($footer['items'] ?? [], $gates, $currentRoute, $fiscalYear, $yearKey);

        return [
            'groups' => $groups,
            'footer' => [
                'title' => $footer['title'] ?? null,
                'items' => $footerItems,
            ],
        ];
    }

    /**
     * Frameworks a register feeds, for the "Feeds:" lineage line.
     *
     * Looks the current (or given) route up in the nav config and returns
     * its 'feeds' entries resolved to label + url. Display only — this
     * never gates anything.
     *
     * @return list<array{key: string, label: string, url: string}>
     */
    public static function feedsFor(?string $routeName = null, ?int $fiscalYear = null): array
    {
        $fiscalYear ??= self::fiscalYear();
        $config = (array) config('navigation', []);
        $routeName ??= Route::currentRouteName() ?? '';
        $yearKey = $config['fiscal_year_key'] ?? 'fiscal_year';
        $frameworks = (array) ($config['frameworks'] ?? []);

        foreach ((array) ($config['groups'] ?? []) as $group) {
            foreach ((array) ($group['items'] ?? []) as $item) {
                if (empty($item['feeds']) || ! self::isActive($item, $routeName)) {
                    continue;
                }

                $out = [];
                foreach ($item['feeds'] as $key) {
                    $framework = $frameworks[$key] ?? null;
                    if (! $framework || ! self::routeExists($framework['route'])) {
                        continue;
                    }

                    $out[] = [
                        'key' => $key,
                        'label' => $framework['label'],
                        'url' => route(
                            $framework['route'],
                            $fiscalYear ? [$yearKey => $fiscalYear] : []
                        ),
                    ];
                }

                return $out;
            }
        }

        return [];
    }

    /**
     * @param  list<array<string, mixed>>  $items
     * @return list<array{label: string, url: string, active: bool}>
     */
    protected static function items(
        array $items,
        array $gates,
        string $currentRoute,
        ?int $fiscalYear,
        string $yearKey
    ): array {
        $out = [];

        foreach ($items as $item) {
            if (! self::passes($item['gate'] ?? 'always', $gates)) {
                continue;
            }

            $name = $item['route'] ?? null;

            // Defensive: a route name removed from routes/web.php would
            // otherwise throw and take the whole page down. Skipping the
            // link degrades navigation; throwing degrades the app.
            if (! $name || ! self::routeExists($name)) {
                continue;
            }

            $params = [];
            if (! empty($item['year']) && $fiscalYear !== null) {
                $params[$yearKey] = $fiscalYear;
            }

            $out[] = [
                'label' => $item['label'] ?? '',
                'url' => route($name, $params),
                'active' => self::isActive($item, $currentRoute),
            ];
        }

        return $out;
    }

    /**
     * Active when the current route name starts with any configured prefix.
     *
     * Prefix matching (not equality) is what lets 'locations.' light the
     * item up on locations.index, locations.edit, locations.create and so on.
     */
    protected static function isActive(array $item, string $currentRoute): bool
    {
        if ($currentRoute === '') {
            return false;
        }

        foreach ((array) ($item['active'] ?? []) as $prefix) {
            if (str_starts_with($currentRoute, $prefix)) {
                return true;
            }
        }

        return false;
    }

    /** Unknown gate keys resolve to false — fail closed, never open. */
    protected static function passes(string $gate, array $gates): bool
    {
        return (bool) ($gates[$gate] ?? false);
    }

    protected static function routeExists(string $name): bool
    {
        return Route::has($name);
    }
}
