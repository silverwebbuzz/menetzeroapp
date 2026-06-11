<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\RoleManagementService;
use App\Services\TeamAccessService;
use App\Models\CompanyCustomRole;
use Illuminate\Support\Facades\Auth;

class RoleManagementController extends Controller
{
    public function __construct(
        protected RoleManagementService $roleManagementService,
        protected TeamAccessService $teamAccess,
    ) {
    }

    public function index()
    {
        $this->requirePermission('roles_permissions', 'view');

        $company = Auth::user()->getActiveCompany();
        if (!$company) {
            return redirect()->route('client.dashboard')
                ->with('error', 'Please complete your company setup first.');
        }

        $this->teamAccess->ensureDefaultRoles($company->id);

        $customRoles = $company->customRoles()
            ->where('is_active', true)
            ->withCount(['users' => function ($query) {
                $query->where('is_active', true);
            }])
            ->get();

        $staffMembers = \App\Models\UserCompanyRole::where('company_id', $company->id)
            ->where('is_active', true)
            ->with(['user', 'companyCustomRole'])
            ->get();

        $pendingInvitations = \App\Models\CompanyInvitation::where('company_id', $company->id)
            ->where('status', 'pending')
            ->where('expires_at', '>', now())
            ->with('inviter')
            ->get();

        return view('roles.index', array_merge(
            compact('customRoles', 'staffMembers', 'pendingInvitations'),
            $this->teamAccess->viewShared($company),
        ));
    }

    public function create()
    {
        $this->requirePermission('roles_permissions', 'add');

        $company = Auth::user()->getActiveCompany();
        if (!$company) {
            return redirect()->route('client.dashboard')
                ->with('error', 'Please complete your company setup first.');
        }

        $permissions = $this->roleManagementService->getPermissionsGroupedByModule();

        return view('roles.create', array_merge(
            compact('permissions'),
            $this->teamAccess->viewShared($company),
        ));
    }

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

        return redirect()->route($this->teamAccess->indexRouteName($company))
            ->with('success', 'Custom role created successfully.');
    }

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

        return view('roles.edit', array_merge(
            compact('role', 'permissions', 'selectedPermissionIds'),
            $this->teamAccess->viewShared($company),
        ));
    }

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

        return redirect()->route($this->teamAccess->indexRouteName($company))
            ->with('success', 'Custom role updated successfully.');
    }

    public function destroy(CompanyCustomRole $role)
    {
        $this->requirePermission('roles_permissions', 'delete');

        $company = Auth::user()->getActiveCompany();
        if (!$company || $role->company_id !== $company->id) {
            abort(403, 'Unauthorized action.');
        }

        $this->roleManagementService->deleteCustomRole($role->id);

        return redirect()->route($this->teamAccess->indexRouteName($company))
            ->with('success', 'Custom role deleted successfully.');
    }
}
