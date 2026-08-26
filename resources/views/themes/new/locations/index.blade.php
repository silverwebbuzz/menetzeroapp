{{--
    MENetZero 2.0 - Business locations (Phase 6 body migration).

    FORM CONTRACT: GET filter form with search / filter / sort (no action, so it
    submits to the current URL, exactly as the original does).

    Two POST toggles per row, both preserved with their csrf tokens:
        locations.toggle-head-office
        locations.toggle-status

    ALPINE: the row overflow menu uses x-data / x-show / x-transition and
    click.away. Both shells load Alpine 3.x and app-shell.css defines
    .dropdown-menu / .dropdown-item UNSCOPED, so the menu works here; the
    tokens below only restyle it to match the new theme.

    No plan gating on this page - verified against the original, which has no
    gate calls and no gated links.

    Controller data: $locations $company

    DELIBERATE OMISSION: $company is passed by LocationController but is not
    referenced by the original view either. Carried across unused rather than
    inventing a use the old page never had.
--}}
@extends('layouts.app')

@section('title', 'Business Locations - MenetZero')
@section('page-title', 'Locations')

@push('styles')
    <style>
        .loc-filters { display: grid; gap: 12px; align-items: end;
            grid-template-columns: 2fr 1fr 1fr; }
        @media (max-width: 860px) { .loc-filters { grid-template-columns: 1fr; } }
        .loc-flag { flex-shrink: 0; width: 32px; height: 32px; border-radius: 3px;
            background: var(--accent-tint); border: 1px solid var(--accent-line);
            color: var(--accent); display: flex; align-items: center;
            justify-content: center; font-size: 15px; }
        .loc-addr { font-size: 11.5px; color: var(--ink-3); margin-top: 2px;
            word-break: break-word; }
        .loc-actions { display: flex; gap: 6px; justify-content: flex-end;
            align-items: center; flex-wrap: wrap; }
        /* Restyle the shared dropdown to the new theme's tokens. */
        .loc-menu { position: absolute; right: 0; top: calc(100% + 4px);
            min-width: 12rem; background: var(--surface); border: 1px solid var(--line);
            box-shadow: 0 8px 24px rgba(20,22,26,.12); padding: 4px; z-index: 50; }
        .loc-menu button { display: block; width: 100%; text-align: left;
            padding: 7px 10px; font-size: 12.5px; background: none; border: 0;
            cursor: pointer; color: var(--ink); }
        .loc-menu button:hover { background: var(--canvas-2); }
    </style>
@endpush

@section('content')
<div class="mnz-stack" data-pillar="e">

    <div class="mnz-pagehead">
        <div>
            <div class="mnz-kicker">Environmental · Boundaries</div>
            <h1>Business Locations</h1>
            <p class="mnz-lead">
                Add every location your business operated from during the period.
                This should cover everything under your operational control.
            </p>
        </div>
        <div class="mnz-pagehead__actions">
            <a href="{{ route('locations.create') }}" class="mnz-btn mnz-btn--primary">Add Location</a>
        </div>
    </div>

    {{-- Filters --}}
    <div class="mnz-panel">
        <div class="mnz-panel__body">
            <form method="GET">
                <div class="loc-filters">
                    <div class="mnz-field">
                        <label class="mnz-label" for="search">Search</label>
                        <input type="text" name="search" id="search" value="{{ request('search') }}"
                               placeholder="Search by name…" class="mnz-input">
                    </div>
                    <div class="mnz-field">
                        <label class="mnz-label" for="filter">Filter</label>
                        <select name="filter" id="filter" class="mnz-select">
                            <option value="">All locations</option>
                            <option value="active" {{ request('filter') == 'active' ? 'selected' : '' }}>Active only</option>
                            <option value="inactive" {{ request('filter') == 'inactive' ? 'selected' : '' }}>Inactive only</option>
                            <option value="head_office" {{ request('filter') == 'head_office' ? 'selected' : '' }}>Head office</option>
                        </select>
                    </div>
                    <div class="mnz-field">
                        <label class="mnz-label" for="sort">Sort</label>
                        <select name="sort" id="sort" class="mnz-select">
                            <option value="name" {{ request('sort') == 'name' ? 'selected' : '' }}>Name (A–Z)</option>
                            <option value="created" {{ request('sort') == 'created' ? 'selected' : '' }}>Recently created</option>
                            <option value="staff" {{ request('sort') == 'staff' ? 'selected' : '' }}>Staff count</option>
                        </select>
                    </div>
                </div>
                <div style="display:flex;gap:8px;margin-top:14px">
                    <button type="submit" class="mnz-btn mnz-btn--primary">Apply filters</button>
                    <a href="{{ route('locations.index') }}" class="mnz-btn mnz-btn--ghost">Clear</a>
                </div>
            </form>
        </div>
    </div>

    @if($locations->count() > 0)
        <div class="mnz-panel">
            <div style="overflow-x:auto">
                <table class="mnz-table" style="width:100%">
                    <thead>
                        <tr>
                            <th style="min-width:18rem">Location</th>
                            <th>Type</th>
                            <th>Staff</th>
                            <th>Fiscal year</th>
                            <th>Status</th>
                            <th style="text-align:right;min-width:13rem">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($locations as $location)
                            <tr>
                                <td>
                                    <div style="display:flex;align-items:flex-start;gap:10px">
                                        <div class="loc-flag">
                                            @if($location->country === 'UAE' || $location->country === 'United Arab Emirates')
                                                🇦🇪
                                            @else
                                                <svg style="width:15px;height:15px" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                </svg>
                                            @endif
                                        </div>
                                        <div style="min-width:0">
                                            <div style="display:flex;align-items:center;gap:7px;flex-wrap:wrap;font-weight:500">
                                                <span>{{ $location->name }}</span>
                                                @if($location->is_head_office)
                                                    <span class="mnz-chip mnz-chip--ok">Head Office</span>
                                                @endif
                                            </div>
                                            <div class="loc-addr">{{ $location->full_address }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td style="white-space:nowrap;color:var(--ink-2)">{{ $location->location_type ?? '—' }}</td>
                                <td style="color:var(--ink-2)">{{ $location->staff_count ?? '—' }}</td>
                                <td style="white-space:nowrap;color:var(--ink-2)">{{ $location->fiscal_year_start ?? '—' }}</td>
                                <td>
                                    @if($location->is_active)
                                        <span class="mnz-chip mnz-chip--ok">Active</span>
                                    @else
                                        <span class="mnz-chip">Inactive</span>
                                    @endif
                                </td>
                                <td style="text-align:right">
                                    <div class="loc-actions">
                                        <a href="{{ route('locations.edit', $location) }}" class="mnz-btn">Edit</a>
                                        <a href="{{ route('emission-boundaries.index', $location) }}" class="mnz-btn mnz-btn--primary">Boundaries</a>
                                        <div style="position:relative" x-data="{ open: false }" @click.away="open = false">
                                            <button type="button" class="mnz-btn mnz-btn--ghost" @click="open = !open" aria-label="More actions">
                                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width:15px;height:15px">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01"/>
                                                </svg>
                                            </button>
                                            <div x-show="open" x-transition x-cloak class="loc-menu">
                                                <form method="POST" action="{{ route('locations.toggle-head-office', $location) }}">
                                                    @csrf
                                                    <button type="submit">
                                                        {{ $location->is_head_office ? 'Unset head office' : 'Mark as head office' }}
                                                    </button>
                                                </form>
                                                <form method="POST" action="{{ route('locations.toggle-status', $location) }}">
                                                    @csrf
                                                    <button type="submit">
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
                <div class="mnz-panel__foot">
                    {{ $locations->links() }}
                </div>
            @endif
        </div>
    @else
        <div class="mnz-panel">
            <div class="mnz-empty">
                <div class="mnz-empty__title">No locations yet</div>
                <div class="mnz-empty__text">Start by adding your first business location.</div>
                <a href="{{ route('locations.create') }}" class="mnz-btn mnz-btn--primary">Add your first location</a>
            </div>
        </div>
    @endif
</div>
@endsection
