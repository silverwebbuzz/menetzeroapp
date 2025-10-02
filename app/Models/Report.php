<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Report extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id', 'type', 'period_start', 'period_end', 'file_path',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}


