<?php

namespace App\Http\View\Composers;

use App\Models\Company;
use App\Models\CompanyDisclosure;
use App\Models\EsgKpiSnapshot;
use App\Models\Measurement;
use Illuminate\View\View;

/**
 * Supplies $availableYears to the reporting-year dropdowns.
 *
 * Shared through a composer rather than each controller: the disclosure
 * controllers destructure resolveContext() and pass variables to their views
 * explicitly, so a value added there would not reach the view without editing
 * every controller.
 */
class ReportingYearsComposer
{
    /** Cached per request — several partials can render on one page. */
    private static array $cache = [];

    public function compose(View $view): void
    {
        $data = $view->getData();

        // Don't clobber a list a controller deliberately provided.
        if (array_key_exists('availableYears', $data)) {
            return;
        }

        $company = $data['company'] ?? auth('web')->user()?->getActiveCompany();

        if (!$company instanceof Company) {
            return;
        }

        $view->with('availableYears', $this->yearsFor($company));
    }

    /**
     * Years the company actually holds data for, newest first. The current
     * calendar year is always included so a new company still has a valid
     * option to select.
     *
     * @return array<int, int>
     */
    private function yearsFor(Company $company): array
    {
        if (isset(self::$cache[$company->id])) {
            return self::$cache[$company->id];
        }

        $years = array_merge(
            [(int) now()->year],
            Measurement::whereHas('location', fn ($q) => $q->where('company_id', $company->id))
                ->distinct()->pluck('fiscal_year')->all(),
            CompanyDisclosure::where('company_id', $company->id)
                ->distinct()->pluck('fiscal_year')->all(),
            EsgKpiSnapshot::where('company_id', $company->id)
                ->distinct()->pluck('fiscal_year')->all(),
        );

        $years = array_values(array_unique(array_filter(array_map('intval', $years))));

        rsort($years);

        return self::$cache[$company->id] = $years;
    }
}
