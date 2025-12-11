<?php

namespace App\Services;

use App\Models\RoleTemplate;
use App\Models\CompanyCustomRole;
use App\Models\Permission;

class RoleManagementService
{
    /**
     * Get available role templates.
     */
    public function getAvailableTemplates()
    {
        return RoleTemplate::active()
            ->orderBy('sort_order')
            ->get();
    }

    /**
     * Create custom role for company.
     * @param int $companyId
     * @param string $roleName
     * @param array $permissionIds Array of permission IDs
     * @param array $data Additional data
     * @return CompanyCustomRole
     */
    public function createCustomRole($companyId, $roleName, $permissionIds = [], $data = [])
    {
        // Ensure permission IDs are integers
        $permissionIds = array_filter(array_map('intval', $permissionIds));
        
        // Create the role
        $role = CompanyCustomRole::create([
            'company_id' => $companyId,
            'role_name' => $roleName,
            'description' => $data['description'] ?? null,
            'based_on_template' => $data['based_on_template'] ?? null,
            'is_active' => true,
        ]);

        // Attach permissions
        if (!empty($permissionIds)) {
            $role->permissions()->attach($permissionIds);
        }

        return $role;
    }

    /**
     * Update custom role.
     * @param int $roleId
     * @param array $data
     * @return CompanyCustomRole
     */
    public function updateCustomRole($roleId, $data)
    {
        $role = CompanyCustomRole::findOrFail($roleId);
        
        // Update role fields
        $role->update([
            'role_name' => $data['role_name'] ?? $role->role_name,
            'description' => $data['description'] ?? $role->description,
            'is_active' => $data['is_active'] ?? $role->is_active,
        ]);

        // Update permissions if provided
        if (isset($data['permission_ids'])) {
            $permissionIds = array_filter(array_map('intval', $data['permission_ids']));
            $role->permissions()->sync($permissionIds);
        }

        return $role;
    }

    /**
     * Delete custom role.
     */
    public function deleteCustomRole($roleId)
    {
        $role = CompanyCustomRole::findOrFail($roleId);
        return $role->delete();
    }

    /**
     * Get all permissions grouped by module.
     */
    public function getPermissionsGroupedByModule()
    {
        return Permission::getGroupedByModule();
    }
}

