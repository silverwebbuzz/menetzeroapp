<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserActiveContext extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'active_company_id',
        'last_switched_at',
    ];

    protected $casts = [
        'last_switched_at' => 'datetime',
    ];

    /**
     * Get the user.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the active company.
     */
    public function activeCompany()
    {
        return $this->belongsTo(Company::class, 'active_company_id');
    }
}

