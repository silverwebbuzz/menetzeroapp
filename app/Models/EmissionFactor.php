<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmissionFactor extends Model
{
    use HasFactory;

    protected $fillable = [
        'category', 'subcategory', 'factor_value', 'unit', 'source', 'year', 'region',
    ];
}