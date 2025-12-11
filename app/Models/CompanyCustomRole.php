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
        'permissions',
        'based_on_template',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'permissions' => 'array',
    ];

    /**
     * Get the company that owns this custom role.
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Scope for active roles.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get normalized permissions as array.
     */
    public function getNormalizedPermissions()
    {
        $permissions = $this->permissions;
        
        // If it's a string, decode it
        if (is_string($permissions)) {
            $permissions = json_decode($permissions, true);
        }
        
        // Ensure it's an array
        if (!is_array($permissions)) {
            $permissions = [];
        }
        
        // Filter out empty values and ensure all are strings
        return array_values(array_filter(array_map('strval', $permissions), function($p) {
            return !empty($p);
        }));
    }
}

