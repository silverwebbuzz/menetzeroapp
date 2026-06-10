<?php

namespace App\Http\Controllers\Consultant\Agency\Concerns;

use App\Models\Company;
use App\Services\ConsultantAgencyWorkspaceService;
use Illuminate\Support\Facades\Auth;

trait ResolvesConsultantAgency
{
    protected function consultantCompany(): Company
    {
        $company = app(ConsultantAgencyWorkspaceService::class)->getConsultantHomeCompany(Auth::user());

        if (!$company) {
            abort(403, 'Consultant organisation required.');
        }

        return $company;
    }
}
