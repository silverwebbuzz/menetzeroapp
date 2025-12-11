<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'designation',
        'company_id', // Kept for backward compatibility, but not used in new logic
        'is_active',
        'google_id',
        'avatar',
        'provider',
        // Legacy fields (kept for backward compatibility)
        'role', // Kept but not used - all roles in user_company_roles
        'custom_role_id', // Kept but not used - all roles in user_company_roles
        'external_company_name',
        'notes',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get the company that the user belongs to.
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the carbon emissions created by the user.
     */
    public function carbonEmissions()
    {
        return $this->hasMany(CarbonEmission::class);
    }

    /**
     * Get all companies this user has access to (multi-account access).
     */
    public function accessibleCompanies()
    {
        return $this->hasMany(UserCompanyRole::class);
    }

    /**
     * Get user company roles.
     */
    public function companyRoles()
    {
        return $this->hasMany(UserCompanyRole::class);
    }

    /**
     * Get active company context.
     */
    public function activeContext()
    {
        return $this->hasOne(UserActiveContext::class);
    }

    /**
     * Get custom role.
     */
    public function customRole()
    {
        return $this->belongsTo(CompanyCustomRole::class, 'custom_role_id');
    }

    /**
     * Get custom role for a specific company (from UserCompanyRole).
     * Returns NULL if user is owner (company_custom_role_id = 0 or NULL).
     */
    public function getCustomRoleForCompany($companyId = null)
    {
        if (!$companyId) {
            $companyId = $this->getActiveCompany()?->id;
        }

        if (!$companyId) {
            return null;
        }

        // Check UserCompanyRole for this company
        try {
            $userCompanyRole = UserCompanyRole::where('user_id', $this->id)
                ->where('company_id', $companyId)
                ->where('is_active', true)
                ->with('companyCustomRole')
                ->first();
            
            // If company_custom_role_id = 0 or NULL, user is owner (no custom role)
            if ($userCompanyRole) {
                if ($userCompanyRole->company_custom_role_id == 0 || $userCompanyRole->company_custom_role_id === null) {
                    return null; // Owner has no custom role
                }
                
                if ($userCompanyRole->companyCustomRole) {
                    return $userCompanyRole->companyCustomRole;
                }
            }
        } catch (\Exception $e) {
            // Table doesn't exist, fallback to old logic
            if ($this->custom_role_id && $this->company_id == $companyId) {
                return $this->customRole;
            }
        }

        return null;
    }

    /**
     * Get UserCompanyRole record for a specific company.
     */
    public function getUserCompanyRole($companyId = null)
    {
        if (!$companyId) {
            $companyId = $this->getActiveCompany()?->id;
        }

        if (!$companyId) {
            return null;
        }

        try {
            return UserCompanyRole::where('user_id', $this->id)
                ->where('company_id', $companyId)
                ->where('is_active', true)
                ->first();
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Get user's permissions for the active company.
     * Returns array of permission names for backward compatibility.
     */
    public function getPermissions($companyId = null)
    {
        // Super admin has all permissions
        if ($this->isAdmin()) {
            return ['*'];
        }

        // Company owner/admin has all permissions
        if ($this->isCompanyAdmin($companyId)) {
            return ['*'];
        }

        $customRole = $this->getCustomRoleForCompany($companyId);
        
        if (!$customRole) {
            return [];
        }

        // Get permissions from the new structure
        $permissions = $customRole->getPermissionNames();
        
        // If empty, return empty array
        if (empty($permissions)) {
            return [];
        }

        return $permissions;
    }

    /**
     * Get user's permission IDs for the active company.
     */
    public function getPermissionIds($companyId = null)
    {
        // Super admin has all permissions
        if ($this->isAdmin() || $this->isCompanyAdmin($companyId)) {
            return ['*'];
        }

        $customRole = $this->getCustomRoleForCompany($companyId);
        
        if (!$customRole) {
            return [];
        }

        return $customRole->getPermissionIds();
    }

    /**
     * Check if user has a specific permission (backward compatibility).
     */
    public function hasPermission($permission, $companyId = null)
    {
        $permissions = $this->getPermissions($companyId);
        
        // If user has all permissions
        if (in_array('*', $permissions)) {
            return true;
        }

        // Check exact permission name
        if (in_array($permission, $permissions)) {
            return true;
        }

        // Check wildcard permissions (e.g., 'measurements.*' matches 'measurements.create')
        foreach ($permissions as $perm) {
            if (str_ends_with($perm, '.*')) {
                $prefix = str_replace('.*', '', $perm);
                if (str_starts_with($permission, $prefix . '.')) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Check if user has permission for a specific module and action.
     */
    public function hasModulePermission($module, $action, $companyId = null)
    {
        // Super admin and company owner/admin have all permissions
        if ($this->isAdmin() || $this->isCompanyAdmin($companyId)) {
            return true;
        }

        $customRole = $this->getCustomRoleForCompany($companyId);
        
        if (!$customRole) {
            return false;
        }

        // Check if the role has this specific permission
        $hasPermission = $customRole->permissions()
            ->where('module', $module)
            ->where('action', $action)
            ->exists();

        return $hasPermission;
    }

    /**
     * Check if user has any of the given permissions.
     */
    public function hasAnyPermission(array $permissions, $companyId = null)
    {
        foreach ($permissions as $permission) {
            if ($this->hasPermission($permission, $companyId)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if user has access to a company.
     */
    public function hasAccessToCompany($companyId)
    {
        try {
            return $this->companyRoles()
                ->where('company_id', $companyId)
                ->where('is_active', true)
                ->exists();
        } catch (\Exception $e) {
            // If table doesn't exist, fall back to company_id check (backward compatibility)
            return $this->company_id == $companyId;
        }
    }

    /**
     * Get current active company.
     * Simplified: 1 user = 1 company only.
     */
    public function getActiveCompany()
    {
        // Super admin doesn't have active company
        if ($this->isAdmin()) {
            return null;
        }

        try {
            // Get the single company from user_company_roles
            $userCompanyRole = $this->companyRoles()->where('is_active', true)->first();
            if ($userCompanyRole) {
                return Company::find($userCompanyRole->company_id);
            }
        } catch (\Exception $e) {
            // If tables don't exist yet, fall back to company_id
        }
        
        // Fallback: Internal user's company (backward compatibility)
        return $this->company;
    }

    /**
     * Check if user already has a company (1 user = 1 company only).
     */
    public function hasCompany()
    {
        try {
            return $this->companyRoles()->where('is_active', true)->exists();
        } catch (\Exception $e) {
            // If table doesn't exist, check company_id
            return !empty($this->company_id);
        }
    }

    /**
     * Check if user is a super admin (system admin).
     * Super admin check: Keep role='admin' for now, or use a flag in future
     */
    public function isAdmin()
    {
        // Keep backward compatibility check
        return $this->role === 'admin';
    }

    /**
     * Check if user is a company owner/admin for a specific company.
     * Owner = company_custom_role_id = 0 or NULL in user_company_roles
     */
    public function isCompanyAdmin($companyId = null)
    {
        if (!$companyId) {
            $companyId = $this->getActiveCompany()?->id;
        }
        
        if (!$companyId) {
            return false;
        }

        try {
            $userCompanyRole = UserCompanyRole::where('user_id', $this->id)
                ->where('company_id', $companyId)
                ->where('is_active', true)
                ->first();
            
            // Owner = company_custom_role_id = 0 or NULL
            if ($userCompanyRole && ($userCompanyRole->company_custom_role_id == 0 || $userCompanyRole->company_custom_role_id === null)) {
                return true;
            }
        } catch (\Exception $e) {
            // Fallback to old logic if table doesn't exist
            return $this->role === 'company_admin' && $this->company_id == $companyId;
        }

        return false;
    }

    /**
     * Check if user is a company user (staff member).
     * Staff = company_custom_role_id > 0 in user_company_roles
     */
    public function isCompanyUser($companyId = null)
    {
        if (!$companyId) {
            $companyId = $this->getActiveCompany()?->id;
        }
        
        if (!$companyId) {
            return false;
        }

        try {
            $userCompanyRole = UserCompanyRole::where('user_id', $this->id)
                ->where('company_id', $companyId)
                ->where('is_active', true)
                ->first();
            
            // Staff = company_custom_role_id > 0
            if ($userCompanyRole && $userCompanyRole->company_custom_role_id > 0) {
                return true;
            }
        } catch (\Exception $e) {
            // Fallback to old logic
            return $this->role === 'company_user' && $this->company_id == $companyId;
        }

        return false;
    }

    /**
     * Check if user is owner of a specific company.
     */
    public function isCompanyOwner($companyId = null)
    {
        return $this->isCompanyAdmin($companyId);
    }

    /**
     * Get all companies where user is owner (company_custom_role_id = NULL).
     */
    public function getOwnedCompanies()
    {
        try {
            return UserCompanyRole::where('user_id', $this->id)
                ->whereNull('company_custom_role_id') // NULL = Owner
                ->where('is_active', true)
                ->with('company')
                ->get()
                ->pluck('company')
                ->filter();
        } catch (\Exception $e) {
            // Fallback: return company if company_id is set
            if ($this->company_id) {
                return collect([$this->company]);
            }
            return collect([]);
        }
    }
}
