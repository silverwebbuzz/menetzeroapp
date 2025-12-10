<?php

namespace App\Services;

use App\Models\RoleTemplate;
use App\Models\CompanyCustomRole;

class RoleManagementService
{
    /**
     * Get available role templates.
     */
    public function getAvailableTemplates($companyType)
    {
        return RoleTemplate::active()
            ->where(function($query) use ($companyType) {
                $query->where('category', $companyType)
                      ->orWhere('category', 'both');
            })
            ->orderBy('sort_order')
            ->get();
    }

    /**
     * Create custom role for company.
     */
    public function createCustomRole($companyId, $roleName, $permissions, $data = [])
    {
        return CompanyCustomRole::create([
            'company_id' => $companyId,
            'role_name' => $roleName,
            'description' => $data['description'] ?? null,
            'permissions' => $permissions,
            'based_on_template' => $data['based_on_template'] ?? null,
            'is_active' => true,
        ]);
    }

    /**
     * Update custom role.
     */
    public function updateCustomRole($roleId, $data)
    {
        $role = CompanyCustomRole::findOrFail($roleId);
        
        $role->update([
            'role_name' => $data['role_name'] ?? $role->role_name,
            'description' => $data['description'] ?? $role->description,
            'permissions' => $data['permissions'] ?? $role->permissions,
            'is_active' => $data['is_active'] ?? $role->is_active,
        ]);

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
     * Assign role to user.
     */
    public function assignRoleToUser($user, $role, $companyId = null)
    {
        // If role is a Spatie role
        if (is_numeric($role) || is_string($role)) {
            try {
                $spatieRole = \Spatie\Permission\Models\Role::findByName($role);
                if ($spatieRole) {
                    $user->assignRole($spatieRole);
                }
            } catch (\Exception $e) {
                // Role doesn't exist in Spatie
            }
        }

        // If company_id is provided, update user's company role
        if ($companyId) {
            $user->update(['company_id' => $companyId]);
        }
    }
}

