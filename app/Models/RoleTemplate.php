<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RoleTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'template_code',
        'template_name',
        'description',
        'is_system_template',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_system_template' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Get permissions for this role template.
     */
    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'role_template_permissions')
            ->withTimestamps();
    }

    /**
     * Scope for active templates.
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
}

