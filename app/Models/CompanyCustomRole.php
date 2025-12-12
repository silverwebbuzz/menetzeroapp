<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompanyCustomRole extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'role_name',
        'description',
        'based_on_template',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the company that owns this custom role.
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get permissions for this role.
     */
    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'company_custom_role_permissions')
            ->withTimestamps();
    }

    /**
     * Get users assigned to this role.
     */
    public function users()
    {
        return $this->hasMany(UserCompanyRole::class, 'company_custom_role_id');
    }

    /**
     * Scope for active roles.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get permission IDs as array.
     */
    public function getPermissionIds()
    {
        return $this->permissions()->pluck('permissions.id')->toArray();
    }

    /**
     * Get permission names as array (for backward compatibility).
     * Returns both the permission name field and constructed module.action format.
     */
    public function getPermissionNames()
    {
        $permissions = $this->permissions()->get();
        $names = [];
        
        foreach ($permissions as $permission) {
            // Add the name field if it exists
            if ($permission->name) {
                $names[] = $permission->name;
            }
            
            // Also add module.action format for compatibility
            if ($permission->module && $permission->action) {
                $moduleAction = $permission->module . '.' . $permission->action;
                if (!in_array($moduleAction, $names)) {
                    $names[] = $moduleAction;
                }
            }
        }
        
        return array_unique($names);
    }

    /**
     * Get permissions grouped by module.
     */
    public function getPermissionsGroupedByModule()
    {
        return $this->permissions()
            ->orderBy('sort_order')
            ->get()
            ->groupBy('module');
    }
}

