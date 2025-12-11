<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RoleTemplatePermission extends Model
{
    use HasFactory;

    protected $fillable = [
        'role_template_id',
        'permission_id',
    ];

    /**
     * Get the role template.
     */
    public function roleTemplate()
    {
        return $this->belongsTo(RoleTemplate::class);
    }

    /**
     * Get the permission.
     */
    public function permission()
    {
        return $this->belongsTo(Permission::class);
    }
}

