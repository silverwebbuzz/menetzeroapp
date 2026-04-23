@extends('layouts.app')

@section('title', 'Business Locations - MenetZero')
@section('page-title', 'Locations')

@section('content')
<div class="page-header">
    <div>
        <h1>Business Locations</h1>
        <p>Add every location your business operated from during the period. This should cover everything under your operational control.</p>
    </div>
    <div class="page-header-actions">
        <a href="{{ route('locations.create') }}" class="btn btn-primary" style="white-space: nowrap;">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
            </svg>
            Add Location
        </a>
    </div>
</div>

<div class="card mb-5">
    <div class="card-body">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-3 items-end">
            <div class="md:col-span-2">
                <label class="form-label">Search</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by name…" class="form-control">
            </div>
            <div>
                <label class="form-label">Filter</label>
                <select name="filter" class="form-select">
                    <option value="">All locations</option>
                    <option value="active" {{ request('filter') == 'active' ? 'selected' : '' }}>Active only</option>
                    <option value="inactive" {{ request('filter') == 'inactive' ? 'selected' : '' }}>Inactive only</option>
                    <option value="head_office" {{ request('filter') == 'head_office' ? 'selected' : '' }}>Head office</option>
                </select>
            </div>
            <div>
                <label class="form-label">Sort</label>
                <select name="sort" class="form-select">
                    <option value="name" {{ request('sort') == 'name' ? 'selected' : '' }}>Name (A–Z)</option>
                    <option value="created" {{ request('sort') == 'created' ? 'selected' : '' }}>Recently created</option>
                    <option value="staff" {{ request('sort') == 'staff' ? 'selected' : '' }}>Staff count</option>
                </select>
            </div>
            <div class="md:col-span-4 flex gap-2">
                <button type="submit" class="btn btn-primary btn-sm">Apply filters</button>
                <a href="{{ route('locations.index') }}" class="btn btn-ghost btn-sm">Clear</a>
            </div>
        </form>
    </div>
</div>

@if($locations->count() > 0)
    <div class="card">
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th style="min-width: 20rem;">Location</th>
                        <th>Type</th>
                        <th>Staff</th>
                        <th>Fiscal year</th>
                        <th>Status</th>
                        <th class="text-right" style="min-width: 14rem;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($locations as $location)
                        <tr>
                            <td>
                                <div class="flex items-start gap-3">
                                    <div class="flex-shrink-0 w-9 h-9 rounded-md bg-brand-soft text-brand-dark flex items-center justify-center font-semibold border border-brand-100">
                                        @if($location->country === 'UAE' || $location->country === 'United Arab Emirates')
                                            🇦🇪
                                        @else
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            </svg>
                                        @endif
                                    </div>
                                    <div class="min-w-0">
                                        <div class="cell-strong flex items-center gap-2 flex-wrap">
                                            <span>{{ $location->name }}</span>
                                            @if($location->is_head_office)
                                                <span class="badge badge-brand">Head Office</span>
                                            @endif
                                        </div>
                                        <div class="text-xs text-slate-500 mt-0.5" style="word-break: break-word;">{{ $location->full_address }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="cell-muted whitespace-nowrap">{{ $location->location_type ?? '—' }}</td>
                            <td class="cell-muted">{{ $location->staff_count ?? '—' }}</td>
                            <td class="cell-muted whitespace-nowrap">{{ $location->fiscal_year_start ?? '—' }}</td>
                            <td>
                                @if($location->is_active)
                                    <span class="badge badge-success badge-dot">Active</span>
                                @else
                                    <span class="badge badge-neutral badge-dot">Inactive</span>
                                @endif
                            </td>
                            <td>
                                <div class="row-actions flex-wrap">
                                    <a href="{{ route('locations.edit', $location) }}" class="btn btn-secondary btn-xs">Edit</a>
                                    <a href="{{ route('emission-boundaries.index', $location) }}" class="btn btn-primary btn-xs">
                                        Boundaries
                                    </a>
                                    <div class="relative" x-data="{ open: false }" @click.away="open = false">
                                        <button type="button" class="btn btn-ghost btn-xs" @click="open = !open" aria-label="More actions">
                                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width: 1rem; height: 1rem;">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01"/>
                                            </svg>
                                        </button>
                                        <div x-show="open" x-transition class="dropdown-menu" style="display: none; min-width: 12rem;">
                                            <form method="POST" action="{{ route('locations.toggle-head-office', $location) }}">
                                                @csrf
                                                <button type="submit" class="dropdown-item">
                                                    {{ $location->is_head_office ? 'Unset head office' : 'Mark as head office' }}
                                                </button>
                                            </form>
                                            <form method="POST" action="{{ route('locations.toggle-status', $location) }}">
                                                @csrf
                                                <button type="submit" class="dropdown-item">
                                                    {{ $location->is_active ? 'Deactivate' : 'Activate' }}
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if(method_exists($locations, 'hasPages') && $locations->hasPages())
            <div class="card-footer">
                {{ $locations->links() }}
            </div>
        @endif
    </div>
@else
    <div class="card">
        <div class="card-body text-center py-12">
            <div class="w-12 h-12 bg-brand-soft rounded-full flex items-center justify-center mx-auto mb-3 border border-brand-100">
                <svg class="w-5 h-5 text-brand-dark" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
            </div>
            <h3 class="text-slate-900 font-semibold mb-1">No locations yet</h3>
            <p class="text-sm text-slate-500 mb-4">Start by adding your first business location.</p>
            <a href="{{ route('locations.create') }}" class="btn btn-primary">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
                Add your first location
            </a>
        </div>
    </div>
@endif
@endsection
