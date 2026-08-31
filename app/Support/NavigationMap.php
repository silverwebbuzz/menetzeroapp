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
     * @param  array<string, bool>  $gates      From NavigationGates::forUser().
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
     * Build the pillar tab bar plus the ACTIVE tab's sidebar.
     *
     * Same config, same gates, same items as build() -- only the shape
     * differs. build() returns every group at once for a single scrolling
     * rail; this returns one tab per group and the items of the active tab
     * only, for a tab bar + filtered sidebar.
     *
     * SETTINGS IS A TAB HERE. In build() it is the 'footer' block rendered
     * at the bottom of the rail. Its items are identical either way -- only
     * its placement changes -- so it is appended as the sixth tab
     * rather than duplicated into config.
     *
     * ACTIVE TAB: the group that owns the current route. Falls back to the
     * first tab when nothing matches, so an unrecognised route still renders
     * a usable nav instead of an empty rail.
     *
     * A tab whose items are all gated away is dropped, exactly as build()
     * drops such a group -- otherwise the user gets a tab that opens onto
     * nothing.
     *
     * @param  array<string, bool>  $gates
     * @return array{tabs: list<array<string, mixed>>, active: string|null, items: list<array<string, mixed>>, title: string|null, eyebrow: string|null}
     */
    public static function tabs(array $gates, ?int $fiscalYear = null): array
    {
        $built = self::build($gates, $fiscalYear);

        $tabs = [];
        foreach ($built['groups'] as $group) {
            $tabs[] = [
                'key' => $group['key'],
                // The Overview group carries 'title' => null because it renders
                // headingless in the rail. A TAB must always have a label.
                'label' => $group['title'] ?? 'Overview',
                'pillar' => $group['pillar'],
                'items' => $group['items'],
                'eyebrow' => self::eyebrowFor($group['key']),
                'url' => $group['items'][0]['url'] ?? null,
                'active' => false,
            ];
        }

        // Settings: the footer block, promoted to a tab.
        if ($built['footer']['items'] !== []) {
            $tabs[] = [
                'key' => 'settings',
                'label' => $built['footer']['title'] ?? 'Settings',
                'pillar' => null,
                'items' => $built['footer']['items'],
                'eyebrow' => self::eyebrowFor('settings'),
                'url' => $built['footer']['items'][0]['url'] ?? null,
                'active' => false,
            ];
        }

        if ($tabs === []) {
            return ['tabs' => [], 'active' => null, 'items' => [], 'title' => null, 'eyebrow' => null, 'pillar' => null];
        }

        // The active tab owns whichever item is active. Items already carry
        // their active flag from items(), computed by prefix match, so this
        // needs no second route comparison.
        $activeIndex = null;
        foreach ($tabs as $i => $tab) {
            foreach ($tab['items'] as $item) {
                if ($item['active']) {
                    $activeIndex = $i;
                    break 2;
                }
            }
        }

        $activeIndex ??= 0;
        $tabs[$activeIndex]['active'] = true;

        return [
            'tabs' => $tabs,
            'active' => $tabs[$activeIndex]['key'],
            'items' => $tabs[$activeIndex]['items'],
            'title' => $tabs[$activeIndex]['label'],
            'eyebrow' => $tabs[$activeIndex]['eyebrow'],
            // Active tab's pillar, so the sidebar can pick up that pillar's
            // accent without re-scanning the tab list in a view.
            'pillar' => $tabs[$activeIndex]['pillar'],
        ];
    }

    /**
     * Small caps label above the sidebar heading, per tab.
     *
     * Display only. An unlisted key falls back to null and the sidebar simply
     * renders no eyebrow.
     */
    protected static function eyebrowFor(string $key): ?string
    {
        return [
            'overview' => 'Workspace',
            'environmental' => 'Pillar',
            'social' => 'Pillar',
            'governance' => 'Pillar',
            'reports' => 'Output',
            'settings' => 'Admin',
        ][$key] ?? null;
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
                // The old theme renders this through its SVG icon library;
                // the new theme ignores it and draws a dot. Passed through
                // for both so neither theme needs its own icon mapping.
                'icon' => $item['icon'] ?? 'dot',
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
