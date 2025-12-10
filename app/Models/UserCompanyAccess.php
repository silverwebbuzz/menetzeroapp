<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserCompanyAccess extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'company_id',
        'role_id',
        'custom_role_id',
        'access_level',
        'status',
        'invited_by',
        'invited_at',
        'last_accessed_at',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'invited_at' => 'datetime',
        'last_accessed_at' => 'datetime',
        'metadata' => 'array',
    ];

    /**
     * Get the user.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the company.
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the Spatie role.
     */
    public function role()
    {
        return $this->belongsTo(\Spatie\Permission\Models\Role::class, 'role_id');
    }

    /**
     * Get the custom role.
     */
    public function customRole()
    {
        return $this->belongsTo(CompanyCustomRole::class, 'custom_role_id');
    }

    /**
     * Get the user who invited.
     */
    public function inviter()
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    /**
     * Scope for active access.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}

