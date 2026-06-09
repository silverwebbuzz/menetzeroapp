<?php

namespace App\Http\Controllers\Disclosure;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

abstract class DisclosureBaseController extends Controller
{
    protected function resolveContext(Request $request, bool $requireEdit = false): array
    {
        $user = Auth::user();
        $company = $user->getActiveCompany();
        if (!$company) {
            abort(403, 'No active company found.');
        }

        if ($requireEdit) {
            $this->requirePermission('disclosures', 'edit', [['reports', 'view']]);
        } else {
            $this->requirePermission('disclosures', 'view', [['reports', 'view']]);
        }

        $fiscalYear = (int) $request->input(
            'fiscal_year',
            session('disclosure_fiscal_year', now()->year)
        );
        session(['disclosure_fiscal_year' => $fiscalYear]);

        return compact('company', 'fiscalYear', 'user');
    }

    protected function fiscalRedirect(string $route, int $fiscalYear, ?string $message = null, array $params = [])
    {
        $redirect = redirect()->route($route, array_merge(['fiscal_year' => $fiscalYear], $params));

        return $message ? $redirect->with('success', $message) : $redirect;
    }
}
