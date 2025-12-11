<?php

namespace App\Http\Controllers;

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
        $this->requirePermission('manage_settings');
        
        $company = Auth::user()->getActiveCompany();
        if (!$company) {
            return redirect()->route('client.dashboard')
                ->with('error', 'Please complete your company setup first.');
        }

        $customRoles = $company->customRoles()->where('is_active', true)->get();
        $templates = $this->roleManagementService->getAvailableTemplates($company->company_type);

        return view('roles.index', compact('customRoles', 'templates'));
    }

    /**
     * Show the form for creating a new custom role.
     */
    public function create()
    {
        $this->requirePermission('manage_settings');
        
        $company = Auth::user()->getActiveCompany();
        if (!$company) {
            return redirect()->route('client.dashboard')
                ->with('error', 'Please complete your company setup first.');
        }

        $templates = $this->roleManagementService->getAvailableTemplates($company->company_type);

        return view('roles.create', compact('templates'));
    }

    /**
     * Store a newly created custom role.
     */
    public function store(Request $request)
    {
        $this->requirePermission('manage_settings');
        
        $company = Auth::user()->getActiveCompany();
        if (!$company) {
            return redirect()->route('client.dashboard')
                ->with('error', 'Please complete your company setup first.');
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

        return redirect()->route('roles.index')
            ->with('success', 'Custom role created successfully.');
    }

    /**
     * Show the form for editing the specified custom role.
     */
    public function edit(CompanyCustomRole $role)
    {
        $this->requirePermission('manage_settings');
        
        $company = Auth::user()->getActiveCompany();
        if (!$company || $role->company_id !== $company->id) {
            abort(403, 'Unauthorized action.');
        }

        // Refresh the role to ensure casts are applied
        $role->refresh();
        
        $templates = $this->roleManagementService->getAvailableTemplates($company->company_type);

        return view('roles.edit', compact('role', 'templates'));
    }

    /**
     * Update the specified custom role.
     */
    public function update(Request $request, CompanyCustomRole $role)
    {
        $this->requirePermission('manage_settings');
        
        $company = Auth::user()->getActiveCompany();
        if (!$company || $role->company_id !== $company->id) {
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

        return redirect()->route('roles.index')
            ->with('success', 'Custom role updated successfully.');
    }

    /**
     * Remove the specified custom role.
     */
    public function destroy(CompanyCustomRole $role)
    {
        $this->requirePermission('manage_settings');
        
        $company = Auth::user()->getActiveCompany();
        if (!$company || $role->company_id !== $company->id) {
            abort(403, 'Unauthorized action.');
        }

        $this->roleManagementService->deleteCustomRole($role->id);

        return redirect()->route('roles.index')
            ->with('success', 'Custom role deleted successfully.');
    }
}

