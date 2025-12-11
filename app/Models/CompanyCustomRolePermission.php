<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompanyCustomRolePermission extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_custom_role_id',
        'permission_id',
    ];

    /**
     * Get the company custom role.
     */
    public function companyCustomRole()
    {
        return $this->belongsTo(CompanyCustomRole::class);
    }

    /**
     * Get the permission.
     */
    public function permission()
    {
        return $this->belongsTo(Permission::class);
    }
}

