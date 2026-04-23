@extends('layouts.app')

@section('title', 'Measurements - MenetZero')
@section('page-title', 'Measurements')

@section('content')
<div class="page-header">
    <div>
        <h1>Measurements</h1>
        <p>Manage your carbon footprint measurement periods and review collected data.</p>
    </div>
    <div class="page-header-actions">
        <a href="{{ route('locations.index') }}" class="btn btn-secondary">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            Manage Locations
        </a>
    </div>
</div>

{{-- Filters --}}
<div class="card mb-5">
    <div class="card-body">
        <form method="GET" action="{{ route('measurements.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-3 items-end">
            <div>
                <label class="form-label">Location</label>
                <select name="location_id" class="form-select">
                    <option value="">All locations</option>
                    @foreach($locations as $location)
                        <option value="{{ $location->id }}" {{ request('location_id') == $location->id ? 'selected' : '' }}>
                            {{ $location->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="">All statuses</option>
                    <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>Draft</option>
                    <option value="submitted" {{ request('status') == 'submitted' ? 'selected' : '' }}>Submitted</option>
                    <option value="under_review" {{ request('status') == 'under_review' ? 'selected' : '' }}>Under review</option>
                    <option value="not_verified" {{ request('status') == 'not_verified' ? 'selected' : '' }}>Not verified</option>
                    <option value="verified" {{ request('status') == 'verified' ? 'selected' : '' }}>Verified</option>
                </select>
            </div>
            <div>
                <label class="form-label">Fiscal Year</label>
                <select name="fiscal_year" class="form-select">
                    <option value="">All years</option>
                    @for($year = date('Y'); $year >= 2020; $year--)
                        <option value="{{ $year }}" {{ request('fiscal_year') == $year ? 'selected' : '' }}>{{ $year }}</option>
                    @endfor
                </select>
            </div>
            <div>
                <label class="form-label">Search</label>
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="Search measurements…"
                       class="form-control">
            </div>
            <div class="md:col-span-4 flex gap-2">
                <button type="submit" class="btn btn-primary btn-sm">Apply filters</button>
                <a href="{{ route('measurements.index') }}" class="btn btn-ghost btn-sm">Clear</a>
            </div>
        </form>
    </div>
</div>

{{-- Measurements List - Grouped by Location and Year --}}
@if($measurements->count() > 0)
    <div class="space-y-5">
        @foreach($groupedMeasurements as $locationName => $yearGroups)
            <div class="card">
                {{-- Location header --}}
                <div class="card-header">
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="flex-shrink-0 w-10 h-10 rounded-lg bg-brand-soft border border-brand-100 flex items-center justify-center">
                            <svg class="w-5 h-5 text-brand-dark" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <h2 class="card-title truncate">{{ $locationName }}</h2>
                            <p class="card-subtitle">{{ $yearGroups->flatten()->count() }} measurement(s) across {{ $yearGroups->count() }} year(s)</p>
                        </div>
                    </div>
                    <div class="text-right flex-shrink-0">
                        <div class="text-xs uppercase tracking-wider text-slate-500 font-medium">Total CO₂e</div>
                        <div class="font-semibold text-brand-dark">{{ number_format($yearGroups->flatten()->sum('total_co2e'), 2) }} <span class="text-xs font-medium text-slate-400">kg</span></div>
                    </div>
                </div>

                <div class="card-body">
                    <div class="space-y-4">
                        @foreach($yearGroups as $year => $yearMeasurements)
                            <div class="border border-slate-200 rounded-lg overflow-hidden">
                                {{-- Year header --}}
                                <div class="flex items-center justify-between px-4 py-2.5 bg-slate-50 border-b border-slate-200 gap-3 flex-wrap">
                                    <h3 class="text-sm font-semibold text-slate-900">Fiscal Year {{ $year }}</h3>
                                    <div class="flex items-center gap-4 text-xs text-slate-500">
                                        <span>{{ $yearMeasurements->count() }} measurement(s)</span>
                                        <span class="text-brand-dark font-medium">{{ number_format($yearMeasurements->sum('total_co2e'), 2) }} kg CO₂e</span>
                                    </div>
                                </div>

                                {{-- Measurements rows --}}
                                @foreach($yearMeasurements as $measurement)
                                    <div class="flex items-center justify-between gap-3 px-4 py-3 border-b border-slate-100 last:border-b-0 hover:bg-slate-50 transition-colors flex-wrap">
                                        <div class="flex items-center gap-6 flex-wrap min-w-0 flex-1">
                                            <div class="min-w-0">
                                                <div class="text-sm font-medium text-slate-900">
                                                    {{ $measurement->period_start->format('M d') }} – {{ $measurement->period_end->format('M d, Y') }}
                                                </div>
                                                <div class="text-xs text-slate-500">
                                                    {{ ucfirst(str_replace('_', ' ', $measurement->frequency)) }} · {{ $measurement->measurementData->count() }} data point(s)
                                                </div>
                                            </div>
                                            @switch($measurement->status)
                                                @case('draft')        <span class="badge badge-neutral badge-dot">Draft</span> @break
                                                @case('submitted')    <span class="badge badge-info badge-dot">Submitted</span> @break
                                                @case('under_review') <span class="badge badge-warning badge-dot">Under Review</span> @break
                                                @case('not_verified') <span class="badge badge-danger badge-dot">Not Verified</span> @break
                                                @case('verified')     <span class="badge badge-success badge-dot">Verified</span> @break
                                                @default              <span class="badge badge-neutral">{{ ucfirst(str_replace('_', ' ', $measurement->status)) }}</span>
                                            @endswitch
                                            <div class="text-right">
                                                <div class="text-xs uppercase tracking-wider text-slate-500 font-medium">CO₂e</div>
                                                <div class="text-sm font-semibold text-slate-900">{{ number_format($measurement->total_co2e, 2) }} <span class="text-xs font-medium text-slate-400">kg</span></div>
                                            </div>
                                        </div>

                                        <div class="row-actions flex-shrink-0">
                                            <a href="{{ route('measurements.show', $measurement) }}" class="btn btn-secondary btn-xs">View &amp; Edit</a>
                                            @if($measurement->canBeSubmitted())
                                                <form method="POST" action="{{ route('measurements.submit', $measurement) }}" class="inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-primary btn-xs">Submit</button>
                                                </form>
                                            @endif
                                            <form method="POST" action="{{ route('measurements.destroy', $measurement) }}" class="inline"
                                                  onsubmit="return confirmDelete('{{ $measurement->period_start->format('M d, Y') }} – {{ $measurement->period_end->format('M d, Y') }}')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-ghost btn-xs" style="color: var(--danger);">Delete</button>
                                            </form>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    @if(method_exists($measurements, 'hasPages') && $measurements->hasPages())
        <div class="mt-5">
            {{ $measurements->links() }}
        </div>
    @endif
@else
    <div class="card">
        <div class="card-body text-center py-12">
            <div class="w-12 h-12 bg-brand-soft rounded-full flex items-center justify-center mx-auto mb-3 border border-brand-100">
                <svg class="w-5 h-5 text-brand-dark" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3v18h18"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 14l4-4 4 4 5-7"/>
                </svg>
            </div>
            <h3 class="text-slate-900 font-semibold mb-1">No measurements yet</h3>
            <p class="text-sm text-slate-500 mb-4">Measurement periods are created automatically when you add a location.</p>
            <a href="{{ route('locations.index') }}" class="btn btn-primary">
                Manage Locations
            </a>
        </div>
    </div>
@endif

<script>
function confirmDelete(period) {
    return confirm(`Delete the measurement for ${period}?\n\nThis action cannot be undone. All associated emission data will also be deleted.`);
}
</script>
@endsection
