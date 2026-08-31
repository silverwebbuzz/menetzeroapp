<?php

namespace App\Http\Controllers\Disclosure;

use App\Services\SocialDashboardService;
use Illuminate\Http\Request;

/**
 * /social -- the Social pillar dashboard.
 *
 * Replaces EsgDashboardController on this route only, for the same reason
 * /environmental was changed: that controller answers "how is my whole ESG
 * programme doing" and so rendered E+S+G content under a Social heading. It
 * is unchanged and still serves its own pages.
 */
class SocialDashboardController extends DisclosureBaseController
{
    public function __construct(
        protected SocialDashboardService $social,
    ) {
    }

    public function index(Request $request)
    {
        ['company' => $company, 'fiscalYear' => $fiscalYear] = $this->resolveContext($request);

        return view('social.index', [
            'company' => $company,
            'fiscalYear' => $fiscalYear,
            'social' => $this->social->build($company, $fiscalYear),
        ]);
    }
}
