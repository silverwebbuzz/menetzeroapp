<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LocationEmissionBoundary extends Model
{
    use HasFactory;

    protected $fillable = [
        'location_id',
        'scope',
        'selected_sources',
    ];

    protected $casts = [
        'selected_sources' => 'array',
    ];

    /**
     * Get the location that owns this boundary
     */
    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    /**
     * Get the emission sources for this scope
     */
    public function emissionSources()
    {
        return EmissionSourceMaster::whereIn('id', $this->selected_sources ?? [])
            ->where('scope', $this->scope)
            ->get();
    }

    /**
     * Check if a specific emission source is selected
     */
    public function hasSource($sourceId)
    {
        return in_array($sourceId, $this->selected_sources ?? []);
    }

    /**
     * Add a source to the selection
     */
    public function addSource($sourceId)
    {
        $sources = $this->selected_sources ?? [];
        if (!in_array($sourceId, $sources)) {
            $sources[] = $sourceId;
            $this->selected_sources = $sources;
        }
    }

    /**
     * Remove a source from the selection
     */
    public function removeSource($sourceId)
    {
        $sources = $this->selected_sources ?? [];
        $sources = array_filter($sources, function($id) use ($sourceId) {
            return $id != $sourceId;
        });
        $this->selected_sources = array_values($sources);
    }
}
