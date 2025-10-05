<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MeasurementAuditTrail extends Model
{
    use HasFactory;

    protected $fillable = [
        'measurement_id',
        'action',
        'old_values',
        'new_values',
        'changed_by',
        'changed_at',
        'reason',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'changed_at' => 'datetime',
    ];

    /**
     * Get the measurement that owns this audit trail entry
     */
    public function measurement(): BelongsTo
    {
        return $this->belongsTo(Measurement::class);
    }

    /**
     * Get the user who made the change
     */
    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }

    /**
     * Get formatted action display name
     */
    public function getActionDisplayAttribute()
    {
        return match($this->action) {
            'created' => 'Created',
            'updated' => 'Updated',
            'status_changed' => 'Status Changed',
            'data_added' => 'Data Added',
            'data_updated' => 'Data Updated',
            'data_deleted' => 'Data Deleted',
            'deleted' => 'Deleted',
            'submitted' => 'Submitted',
            'verified' => 'Verified',
            'rejected' => 'Rejected',
            default => ucfirst(str_replace('_', ' ', $this->action))
        };
    }

    /**
     * Get action badge color
     */
    public function getActionColorAttribute()
    {
        return match($this->action) {
            'created' => 'green',
            'updated' => 'blue',
            'status_changed' => 'yellow',
            'data_added' => 'green',
            'data_updated' => 'blue',
            'data_deleted' => 'red',
            'deleted' => 'red',
            'submitted' => 'blue',
            'verified' => 'green',
            'rejected' => 'red',
            default => 'gray'
        };
    }

    /**
     * Get formatted change description
     */
    public function getChangeDescriptionAttribute()
    {
        $description = $this->action_display;
        
        if ($this->action === 'status_changed' && isset($this->old_values['status']) && isset($this->new_values['status'])) {
            $description .= " from {$this->old_values['status']} to {$this->new_values['status']}";
        }
        
        if ($this->reason) {
            $description .= " - {$this->reason}";
        }
        
        return $description;
    }

    /**
     * Scope a query to only include entries for a given measurement
     */
    public function scopeForMeasurement($query, $measurementId)
    {
        return $query->where('measurement_id', $measurementId);
    }

    /**
     * Scope a query to only include entries of a given action
     */
    public function scopeByAction($query, $action)
    {
        return $query->where('action', $action);
    }

    /**
     * Scope a query to only include entries by a given user
     */
    public function scopeByUser($query, $userId)
    {
        return $query->where('changed_by', $userId);
    }

    /**
     * Scope a query to only include entries within a date range
     */
    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('changed_at', [$startDate, $endDate]);
    }
}
