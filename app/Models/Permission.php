<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    use HasFactory;

    protected $fillable = [
        'module',
        'action',
        'name',
        'description',
        'group_name',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get all role templates that have this permission.
     */
    public function roleTemplates()
    {
        return $this->belongsToMany(RoleTemplate::class, 'role_template_permissions')
            ->withTimestamps();
    }

    /**
     * Get all company custom roles that have this permission.
     */
    public function companyCustomRoles()
    {
        return $this->belongsToMany(CompanyCustomRole::class, 'company_custom_role_permissions')
            ->withTimestamps();
    }

    /**
     * Scope for active permissions.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for a specific module.
     */
    public function scopeForModule($query, $module)
    {
        return $query->where('module', $module);
    }

    /**
     * Get permissions grouped by module.
     */
    public static function getGroupedByModule()
    {
        return static::active()
            ->orderBy('sort_order')
            ->get()
            ->groupBy('module');
    }
}

