<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Services\ThemeResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Phase 6 Tier 2 — per-company theme opt-in.
 *
 * A new controller rather than an addition to SuperAdminController, so the
 * switch-over surface is additive and can be removed in one step once the
 * migration completes and the old theme is retired.
 *
 * Sits behind the same 'ensureSuperAdmin' middleware as the rest of
 * /admin/companies. Only a super admin can opt a company in or out.
 */
class CompanyThemeController extends Controller
{
    public function __construct(protected ThemeResolver $themes)
    {
    }

    public function update(Request $request, Company $company): RedirectResponse
    {
        $validated = $request->validate([
            // 'default' clears the opt-in and lets config('themes.default') win.
            'theme' => ['required', 'string', 'in:default,old,new'],
        ]);

        $theme = $validated['theme'];

        if ($theme === 'default') {
            $company->setThemePreference(null);
            $message = "{$company->name} now follows the default theme.";
        } else {
            if (! $this->themes->isRegistered($theme)) {
                return back()->with('error', 'That theme is not registered.');
            }

            $company->setThemePreference($theme);

            $label = config("themes.themes.{$theme}.label", $theme);
            $message = "{$company->name} is now pinned to {$label}.";
        }

        $company->save();

        return back()->with('success', $message);
    }
}
