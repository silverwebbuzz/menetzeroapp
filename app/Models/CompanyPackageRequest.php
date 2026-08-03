<?php

namespace App\Models;

use App\Data\CompanyPackageOptions;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanyPackageRequest extends Model
{
    protected $fillable = [
        'company_id',
        'user_id',
        'package_code',
        'extras',
        'message',
        'status',
        'admin_notes',
    ];

    protected $casts = [
        'extras' => 'array',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function packageLabel(): string
    {
        return CompanyPackageOptions::label($this->package_code);
    }
}
