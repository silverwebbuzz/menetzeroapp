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
        // Normalize permissions - handle array, JSON string, or null
        if (is_string($permissions)) {
            $permissions = json_decode($permissions, true) ?? [];
        }
        if (!is_array($permissions)) {
            $permissions = [];
        }
        
        // Ensure all permissions are strings and filter out empty values
        $permissions = array_values(array_filter(array_map('strval', $permissions), function($p) {
            return !empty($p);
        }));
        
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
        
        // Normalize permissions if provided
        $permissions = $data['permissions'] ?? $role->permissions;
        if (isset($data['permissions'])) {
            // Normalize permissions - handle array, JSON string, or null
            if (is_string($permissions)) {
                $permissions = json_decode($permissions, true) ?? [];
            }
            if (!is_array($permissions)) {
                $permissions = [];
            }
            
            // Ensure all permissions are strings and filter out empty values
            $permissions = array_values(array_filter(array_map('strval', $permissions), function($p) {
                return !empty($p);
            }));
        } else {
            // Use existing permissions and normalize them
            $permissions = $role->getNormalizedPermissions();
        }
        
        $role->update([
            'role_name' => $data['role_name'] ?? $role->role_name,
            'description' => $data['description'] ?? $role->description,
            'permissions' => $permissions,
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

