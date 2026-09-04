<?php

/*
|--------------------------------------------------------------------------
| MENetZero Theme Registry
|--------------------------------------------------------------------------
|
| Phase 0 of the MENetZero 2.0 redesign (documentation/redesign.md).
|
| Two themes coexist in the same deploy. 'old' is the live experience and
| stays the default until the Phase 6 switch-over. 'new' is the MENetZero
| 2.0 redesign, built up progressively across Phases 1-5.
|
| Nothing here changes behaviour for existing users: the default is 'old',
| and the old theme renders exactly as it did before Phase 0.
|
*/

return [

    /*
    | The theme used when nothing else selects one. Flipping this to 'new'
    | is the Phase 6 switch-over — one value, instantly reversible.
    */
    'default' => env('THEME_DEFAULT', 'old'),

    /*
    | Master kill-switch for ?theme= switching.
    |
    | Theme switching is deliberately open to any authenticated user
    | (see redesign.md section 7A.1 — the Super Admin restriction was
    | overridden because super admins authenticate on the 'admin' guard
    | and cannot reach the company portal at all).
    |
    | The theme only changes appearance. Every route keeps its full
    | middleware stack regardless of theme, so no data access changes.
    |
    | If a client ever stumbles onto ?theme=new mid-migration, set
    | THEME_SWITCH_ENABLED=false in .env — switching dies immediately,
    | no deploy required.
    */
    'switch_enabled' => env('THEME_SWITCH_ENABLED', true),

    /*
    | The query parameter and session key used to select a theme.
    | Reading the query param WRITES to the session, so the choice is
    | sticky across navigation (requirement 5). A bare query param would
    | be lost on the first click.
    */
    'query_key' => 'theme',
    'session_key' => 'mnz_theme',

    /*
    | Registered themes. 'view_namespace' is registered with the view
    | finder in ThemeServiceProvider; a theme with a null namespace uses
    | the default view paths (that is the old theme — untouched).
    */
    'themes' => [

        'old' => [
            'label' => 'MENetZero (current)',
            'view_namespace' => null,
            'layout' => 'layouts.app',
            'consultant_layout' => 'consultant.layouts.app',
            'admin_layout' => 'admin.layouts.app',
            'assets' => [],
        ],

        'new' => [
            'label' => 'MENetZero 2.0',
            'view_namespace' => 'theme-new',
            'view_path' => 'resources/views/themes/new',
            'layout' => 'layouts.app',
            'consultant_layout' => 'consultant.layouts.app',
            'admin_layout' => 'admin.layouts.app',
            'assets' => [
                'css' => ['css/mnz-ui.css'],
                'js' => ['js/mnz-ui.js'],
                'version' => '20260904a',
            ],
        ],
    ],
];
