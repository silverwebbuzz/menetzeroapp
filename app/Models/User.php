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
        'company_id',
        'role',
        'is_active',
        'google_id',
        'avatar',
        'provider',
        // New fields for enhancements
        'custom_role_id',
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
            
            if ($userCompanyRole && $userCompanyRole->companyCustomRole) {
                return $userCompanyRole->companyCustomRole;
            }
        } catch (\Exception $e) {
            // Table doesn't exist, continue to check direct custom_role_id
        }

        // Fallback: Check if user has direct custom_role_id for this company
        if ($this->custom_role_id && $this->company_id == $companyId) {
            return $this->customRole;
        }

        return null;
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

        // Company admin has all permissions
        if ($this->isCompanyAdmin()) {
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
        if ($this->isAdmin() || $this->isCompanyAdmin()) {
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
        // Super admin and company admin have all permissions
        if ($this->isAdmin() || $this->isCompanyAdmin()) {
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
            // If table doesn't exist, fall back to company_id check
            return $this->company_id == $companyId;
        }
    }

    /**
     * Get current active company.
     */
    public function getActiveCompany()
    {
        // Super admin doesn't have active company
        if ($this->isAdmin()) {
            return null;
        }

        try {
            $context = $this->activeContext;
            if ($context && $context->active_company_id) {
                return Company::find($context->active_company_id);
            }
            
            // Fallback: If only one company access, use that
            $userCompanyRole = $this->companyRoles()->where('is_active', true)->first();
            if ($userCompanyRole) {
                return Company::find($userCompanyRole->company_id);
            }
        } catch (\Exception $e) {
            // If tables don't exist yet, fall back to company_id
        }
        
        // Fallback: Internal user's company
        return $this->company;
    }

    /**
     * Check if user has multiple company access.
     */
    public function hasMultipleCompanyAccess()
    {
        // Super admin doesn't need company access
        if ($this->isAdmin()) {
            return false;
        }

        try {
            $accessCount = $this->companyRoles()->where('is_active', true)->count();
            
            // If user has company_id set, count that too
            if ($this->company_id) {
                $accessCount++;
            }
            
            return $accessCount > 1;
        } catch (\Exception $e) {
            // If table doesn't exist yet, just check company_id
            // This handles the case where migration hasn't been run
            return false;
        }
    }

    /**
     * Switch active company.
     */
    public function switchToCompany($companyId)
    {
        if (!$this->hasAccessToCompany($companyId) && $this->company_id != $companyId) {
            throw new \Exception('User does not have access to this company');
        }
        
        UserActiveContext::updateOrCreate(
            ['user_id' => $this->id],
            [
                'active_company_id' => $companyId,
                'last_switched_at' => now(),
            ]
        );
    }

    /**
     * Check if user is an admin.
     */
    public function isAdmin()
    {
        return $this->role === 'admin';
    }

    /**
     * Check if user is a company admin.
     */
    public function isCompanyAdmin()
    {
        return $this->role === 'company_admin';
    }

    /**
     * Check if user is a company user.
     */
    public function isCompanyUser()
    {
        return $this->role === 'company_user';
    }
}
