<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id', 'plan_type', 'status', 'stripe_customer_id', 'started_at', 'expires_at',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}


