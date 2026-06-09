<?php

namespace App\Models;

use App\Data\ConsultantOptions;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class ConsultantDocument extends Model
{
    protected $fillable = [
        'consultant_id',
        'document_type',
        'file_path',
        'original_filename',
        'status',
        'admin_notes',
    ];

    public function consultant(): BelongsTo
    {
        return $this->belongsTo(Consultant::class);
    }

    public function typeLabel(): string
    {
        return ConsultantOptions::labelFor('document', $this->document_type);
    }

    public function deleteFile(): void
    {
        if ($this->file_path && Storage::disk('local')->exists($this->file_path)) {
            Storage::disk('local')->delete($this->file_path);
        }
    }
}
