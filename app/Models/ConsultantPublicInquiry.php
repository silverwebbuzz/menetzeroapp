<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConsultantPublicInquiry extends Model
{
    protected $fillable = [
        'consultant_id',
        'requester_name',
        'requester_email',
        'requester_phone',
        'requester_company',
        'message',
        'status',
        'ip_address',
    ];

    public function consultant(): BelongsTo
    {
        return $this->belongsTo(Consultant::class);
    }
}
