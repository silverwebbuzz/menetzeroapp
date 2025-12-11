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
     * Display a listing of custom roles and staff members.
     */
    public function index()
    {
        $this->requirePermission('roles_permissions', 'view');
        
        $company = Auth::user()->getActiveCompany();
        if (!$company) {
            return redirect()->route('client.dashboard')
                ->with('error', 'Please complete your company setup first.');
        }

        // Get roles with user counts
        $customRoles = $company->customRoles()
            ->where('is_active', true)
            ->withCount(['users' => function($query) {
                $query->where('is_active', true);
            }])
            ->get();

        // Get all staff members (users with roles in this company)
        $staffMembers = \App\Models\UserCompanyRole::where('company_id', $company->id)
            ->where('is_active', true)
            ->with(['user', 'companyCustomRole'])
            ->get();

        // Get pending invitations
        $pendingInvitations = \App\Models\CompanyInvitation::where('company_id', $company->id)
            ->where('status', 'pending')
            ->where('expires_at', '>', now())
            ->with('inviter')
            ->get();

        // Check if can add more users
        $subscriptionService = app(\App\Services\SubscriptionService::class);
        $canAddUser = $subscriptionService->canPerformAction($company->id, 'users', 1);
        $userLimitMessage = $canAddUser['allowed'] ? null : $canAddUser['message'];

        return view('roles.index', compact('customRoles', 'staffMembers', 'pendingInvitations', 'canAddUser', 'userLimitMessage'));
    }

    /**
     * Show the form for creating a new custom role.
     */
    public function create()
    {
        $this->requirePermission('roles_permissions', 'add');
        
        $company = Auth::user()->getActiveCompany();
        if (!$company) {
            return redirect()->route('client.dashboard')
                ->with('error', 'Please complete your company setup first.');
        }

        $permissions = $this->roleManagementService->getPermissionsGroupedByModule();

        return view('roles.create', compact('permissions'));
    }

    /**
     * Store a newly created custom role.
     */
    public function store(Request $request)
    {
        $this->requirePermission('roles_permissions', 'add');
        
        $company = Auth::user()->getActiveCompany();
        if (!$company) {
            return redirect()->route('client.dashboard')
                ->with('error', 'Please complete your company setup first.');
        }

        $request->validate([
            'role_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'permission_ids' => 'required|array|min:1',
            'permission_ids.*' => 'exists:permissions,id',
        ]);

        $this->roleManagementService->createCustomRole(
            $company->id,
            $request->role_name,
            $request->permission_ids,
            [
                'description' => $request->description,
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
        $this->requirePermission('roles_permissions', 'edit');
        
        $company = Auth::user()->getActiveCompany();
        if (!$company || $role->company_id !== $company->id) {
            abort(403, 'Unauthorized action.');
        }

        $role->load('permissions');
        $permissions = $this->roleManagementService->getPermissionsGroupedByModule();
        $selectedPermissionIds = $role->getPermissionIds();

        return view('roles.edit', compact('role', 'permissions', 'selectedPermissionIds'));
    }

    /**
     * Update the specified custom role.
     */
    public function update(Request $request, CompanyCustomRole $role)
    {
        $this->requirePermission('roles_permissions', 'edit');
        
        $company = Auth::user()->getActiveCompany();
        if (!$company || $role->company_id !== $company->id) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'role_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'permission_ids' => 'required|array|min:1',
            'permission_ids.*' => 'exists:permissions,id',
            'is_active' => 'boolean',
        ]);

        $this->roleManagementService->updateCustomRole($role->id, [
            'role_name' => $request->role_name,
            'description' => $request->description,
            'permission_ids' => $request->permission_ids,
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

