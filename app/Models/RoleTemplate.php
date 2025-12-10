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
        'permissions',
        'category',
        'is_system_template',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_system_template' => 'boolean',
        'is_active' => 'boolean',
        'permissions' => 'array',
    ];

    /**
     * Scope for active templates.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for client templates.
     */
    public function scopeForClients($query)
    {
        return $query->where(function($q) {
            $q->where('category', 'client')
              ->orWhere('category', 'both');
        });
    }

}

