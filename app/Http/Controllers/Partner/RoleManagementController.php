<?php

namespace App\Http\Controllers\Partner;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\RoleManagementService;
use App\Models\CompanyCustomRole;
use App\Models\RoleTemplate;
use Illuminate\Support\Facades\Auth;

class RoleManagementController extends Controller
{
    protected $roleManagementService;

    public function __construct(RoleManagementService $roleManagementService)
    {
        $this->roleManagementService = $roleManagementService;
    }

    /**
     * Display a listing of custom roles.
     */
    public function index()
    {
        $company = Auth::user()->getActiveCompany();
        if (!$company || $company->company_type !== 'partner') {
            abort(403, 'Unauthorized action.');
        }

        $customRoles = $company->customRoles()->where('is_active', true)->get();
        $templates = $this->roleManagementService->getAvailableTemplates('partner');

        return view('partner.roles.index', compact('customRoles', 'templates'));
    }

    /**
     * Show the form for creating a new custom role.
     */
    public function create()
    {
        $company = Auth::user()->getActiveCompany();
        if (!$company || $company->company_type !== 'partner') {
            abort(403, 'Unauthorized action.');
        }

        $templates = $this->roleManagementService->getAvailableTemplates('partner');

        return view('partner.roles.create', compact('templates'));
    }

    /**
     * Store a newly created custom role.
     */
    public function store(Request $request)
    {
        $company = Auth::user()->getActiveCompany();
        if (!$company || $company->company_type !== 'partner') {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'role_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'permissions' => 'required|array',
            'based_on_template' => 'nullable|string',
        ]);

        $this->roleManagementService->createCustomRole(
            $company->id,
            $request->role_name,
            $request->permissions,
            [
                'description' => $request->description,
                'based_on_template' => $request->based_on_template,
            ]
        );

        return redirect()->route('partner.roles.index')
            ->with('success', 'Custom role created successfully.');
    }

    /**
     * Show the form for editing the specified custom role.
     */
    public function edit(CompanyCustomRole $role)
    {
        $company = Auth::user()->getActiveCompany();
        if (!$company || $company->company_type !== 'partner' || $role->company_id !== $company->id) {
            abort(403, 'Unauthorized action.');
        }

        $templates = $this->roleManagementService->getAvailableTemplates('partner');

        return view('partner.roles.edit', compact('role', 'templates'));
    }

    /**
     * Update the specified custom role.
     */
    public function update(Request $request, CompanyCustomRole $role)
    {
        $company = Auth::user()->getActiveCompany();
        if (!$company || $company->company_type !== 'partner' || $role->company_id !== $company->id) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'role_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'permissions' => 'required|array',
            'is_active' => 'boolean',
        ]);

        $this->roleManagementService->updateCustomRole($role->id, [
            'role_name' => $request->role_name,
            'description' => $request->description,
            'permissions' => $request->permissions,
            'is_active' => $request->has('is_active'),
        ]);

        return redirect()->route('partner.roles.index')
            ->with('success', 'Custom role updated successfully.');
    }

    /**
     * Remove the specified custom role.
     */
    public function destroy(CompanyCustomRole $role)
    {
        $company = Auth::user()->getActiveCompany();
        if (!$company || $company->company_type !== 'partner' || $role->company_id !== $company->id) {
            abort(403, 'Unauthorized action.');
        }

        $this->roleManagementService->deleteCustomRole($role->id);

        return redirect()->route('partner.roles.index')
            ->with('success', 'Custom role deleted successfully.');
    }
}

