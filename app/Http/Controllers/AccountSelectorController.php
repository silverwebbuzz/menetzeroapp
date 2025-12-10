<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\UserCompanyAccess;
use App\Models\Company;

class AccountSelectorController extends Controller
{
    /**
     * Show account selector page.
     */
    public function index()
    {
        // Get user from web guard
        $user = auth('web')->user();
        
        if (!$user->hasMultipleCompanyAccess()) {
            // Single company access - redirect to dashboard
            $company = $user->getActiveCompany();
            if ($company) {
                return redirect()->route('client.dashboard');
            }
        }

        $accessibleCompanies = collect();
        
        // Add companies from user_company_access
        $accessRecords = $user->accessibleCompanies()->where('status', 'active')->get();
        foreach ($accessRecords as $access) {
            $company = Company::find($access->company_id);
            if ($company) {
                $accessibleCompanies->push([
                    'id' => $company->id,
                    'name' => $company->name,
                    'type' => $access->company_type,
                    'role' => $access->role ? $access->role->name : ($access->customRole ? $access->customRole->role_name : 'N/A'),
                    'last_accessed' => $access->last_accessed_at,
                ]);
            }
        }
        
        // Add own company if set (for standard staff)
        if ($user->company_id) {
            $company = Company::find($user->company_id);
            if ($company) {
                $accessibleCompanies->push([
                    'id' => $company->id,
                    'name' => $company->name,
                    'type' => $company->company_type,
                    'role' => $user->role ?? 'N/A',
                    'last_accessed' => null,
                ]);
            }
        }

        return view('account-selector', compact('accessibleCompanies'));
    }
    
    /**
     * Switch active account.
     */
    public function switch(Request $request)
    {
        $request->validate([
            'company_id' => 'required|exists:companies,id'
        ]);
        
        // Get user from web guard
        $user = auth('web')->user();
        
        // Verify user has access
        if (!$user->hasAccessToCompany($request->company_id) && $user->company_id != $request->company_id) {
            abort(403, 'You do not have access to this company');
        }
        
        $user->switchToCompany($request->company_id);
        
        // Update last accessed
        UserCompanyAccess::where('user_id', $user->id)
            ->where('company_id', $request->company_id)
            ->update(['last_accessed_at' => now()]);
        
        return redirect()->route('client.dashboard');
    }
}

