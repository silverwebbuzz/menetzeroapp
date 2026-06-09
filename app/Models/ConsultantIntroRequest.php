<?php

namespace App\Models;

use App\Data\ConsultantOptions;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ConsultantIntroRequest extends Model
{
    protected $fillable = [
        'company_id',
        'user_id',
        'consultant_id',
        'pack_type',
        'message',
        'status',
        'admin_notes',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function consultant(): BelongsTo
    {
        return $this->belongsTo(Consultant::class);
    }

    public function order(): HasOne
    {
        return $this->hasOne(ConsultantOrder::class, 'intro_request_id');
    }

    public function packLabel(): string
    {
        return $this->pack_type
            ? ConsultantOptions::labelFor('pack', $this->pack_type)
            : 'General intro';
    }
}
