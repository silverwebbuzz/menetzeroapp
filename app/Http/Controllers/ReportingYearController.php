<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

/**
 * Sets the reporting year held in the session.
 *
 * Phase B of the IA work. The reporting year is app-level CONTEXT, not a
 * per-link parameter: every disclosure screen is scoped to a year, and
 * before this the year travelled only as ?fiscal_year= on each individual
 * link. Any link that forgot to carry it silently dropped the user back to
 * the current calendar year — so someone could edit 2026 data believing
 * they were in 2025. That is a data-accuracy bug, not a cosmetic one.
 *
 * The session key is the SAME one
 * DisclosureBaseController::resolveContext() already reads and writes
 * ('disclosure_fiscal_year'), so this control and the existing per-page
 * year dropdowns stay in agreement — neither overrides the other, and
 * ?fiscal_year= on a URL still wins for that request, as it did before.
 */
class ReportingYearController extends Controller
{
    /**
     * Bounds for an accepted year.
     *
     * Deliberately generous — companies do restate historical inventories —
     * but bounded, because the value is echoed into queries and views.
     */
    private const MIN_YEAR = 2000;
    private const MAX_YEARS_AHEAD = 5;

    public function update(Request $request)
    {
        $validated = $request->validate([
            'fiscal_year' => [
                'required',
                'integer',
                'min:' . self::MIN_YEAR,
                'max:' . (now()->year + self::MAX_YEARS_AHEAD),
            ],
        ]);

        session(['disclosure_fiscal_year' => (int) $validated['fiscal_year']]);

        // Back to wherever the user was. The target page re-reads the year
        // from the session, so the whole app moves together.
        return redirect()->back();
    }
}
