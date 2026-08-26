{{--
    MENetZero 2.0 — Quick Input entries (Phase 6 body migration).

    The highest-traffic company page: daily emission data entry. Migrated first
    per §29.6, ahead of any change to THEME_DEFAULT.

    PLAN GATING preserved verbatim — two x-plan-gated-link blocks:
        Help guide  quick-input.help-guide  $gate->canHelpGuide()
        Export CSV  quick-input.export      $gate->canBulkExport()
    plus the Scope 3 lock ($gate->isScope3Locked()), which swaps the whole
    source grid for an upgrade callout and branches again on
    $gate->isAgencyWorkspace(). Rendering the grid unlocked would hand Scope 3
    entry to every tier (risk R-1).

    PERMISSION GATING: $canDeleteEntries controls the select-all column, the
    bulk-actions bar, and the per-row checkbox — carried across on all four
    sites. The empty-state colspan depends on it too (10 vs 9).

    ALPINE.JS: bulk selection uses x-data / x-for / x-show / x-cloak / x-model.
    Verified both shells load Alpine 3.x, and [x-cloak] is defined in
    app-shell.css which this shell loads — without that rule the bulk bar
    flashes visible on every page load.

    SHARED PARTIAL: the 48-line SVG icon map is included from
    quick-input/partials/source-icon (§22 precedent) — not duplicated.

    Controller data: $entries $locations $sources $summary $yearsWithEntries
    $canAddEntries $canDeleteEntries
    Composer data: $gate (PlanGateComposer)

    DELIBERATE OMISSION: $canAddEntries is provided by the controller but is
    not referenced by the original view either — carried across unused rather
    than inventing a gate the old page never had.
--}}
@extends('layouts.app')

@section('title', 'Quick Input Entries - MENetZero')
@section('page-title', 'Quick Input Entries')

@push('styles')
    <style>
        .qi-sources { display: grid; gap: 1px; background: var(--line);
            grid-template-columns: repeat(auto-fill, minmax(190px, 1fr));
            border: 1px solid var(--line); }
        .qi-source { background: var(--surface); padding: 16px 18px; display: block;
            color: inherit; text-decoration: none; }
        .qi-source:hover { background: var(--canvas-2); text-decoration: none; }
        /* The shared icon partial applies this class to its <svg>. Without a
           size rule the SVGs render at their intrinsic size and blow out the grid. */
        .qi-source__icon { width: 22px; height: 22px; color: var(--accent); }
        .qi-source__title { font-size: 13px; font-weight: 500; margin-top: 10px; }
        .qi-source__meta { font-size: 11.5px; color: var(--ink-3); margin-top: 3px; }
        .qi-scope { margin-bottom: 24px; }
        .qi-scope__head { display: flex; align-items: baseline; gap: 10px;
            flex-wrap: wrap; margin-bottom: 10px; }
        .qi-scope__title { font-size: 13.5px; font-weight: 600; margin: 0; }
        .qi-scope__sub { font-size: 12px; color: var(--ink-3); }
        .qi-filters { display: grid; gap: 12px; align-items: end;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); }
        .qi-actions { display: flex; gap: 6px; justify-content: flex-end; align-items: center; }
        .qi-actions a, .qi-actions button { color: var(--ink-3); }
        .qi-actions a:hover { color: var(--accent); }
        table.mnz-table td.qi-tight { white-space: nowrap; }
        /* Selected-row highlight. The original used Tailwind's bg-brand-50;
           this is the theme-token equivalent. Without a rule here the Alpine
           :class binding would set a class that styles nothing. */
        table.mnz-table tr.is-selected > td { background: var(--accent-tint); }
    </style>
@endpush

@section('content')
<div class="mnz-stack" data-pillar="e">

    <div class="mnz-pagehead">
        <div>
            <div class="mnz-kicker">Environmental · Measure</div>
            <h1>Input data</h1>
            <p class="mnz-lead">
                View and manage emission entries across all scopes, locations, and reporting years.
            </p>
        </div>
        <div class="mnz-pagehead__actions">
            <x-plan-gated-link
                :allowed="$gate->canHelpGuide()"
                :href="route('quick-input.help-guide')"
                :message="$gate->helpGuideMessage()"
                class="mnz-btn"
                locked-class="mnz-btn">
                Scope 1 &amp; 2 Help Guide
            </x-plan-gated-link>
            <a href="{{ route('quick-input.bulk-import.index') }}" class="mnz-btn">
                Bulk import
            </a>
        </div>
    </div>

    @if($errors->any())
        <div class="mnz-panel" style="border-color:var(--bad-line);background:var(--bad-tint)">
            <div class="mnz-panel__body" style="color:var(--bad)">
                <ul style="margin:0;padding-left:18px">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    {{-- A partially successful import lands here (so the user sees the rows that
         did import) and carries the skipped-row detail with it. --}}
    @if(session('import_errors') && count(session('import_errors')) > 0)
        <div class="mnz-panel" style="border-color:var(--warn-line);background:var(--warn-tint)">
            <div class="mnz-panel__body" style="color:var(--warn)">
                <p style="font-weight:600;margin:0 0 8px">Rows skipped during import:</p>
                <ul style="margin:0;padding-left:18px;font-size:12.5px;max-height:160px;overflow-y:auto">
                    @foreach(session('import_errors') as $importError)
                        <li>{{ $importError }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    {{-- Input forms, grouped by scope --}}
    <div>
        @php
            // Group the (already ordered) sources by their scope so we can render
            // a labelled section for Scope 1, 2 and 3.
            $sourcesByScope = collect($sources)->groupBy('scope');
            $scopeSections = [
                'Scope 1' => ['title' => 'Scope 1 — Direct Emissions', 'subtitle' => 'Emissions from sources you own or control'],
                'Scope 2' => ['title' => 'Scope 2 — Purchased Energy', 'subtitle' => 'Emissions from purchased electricity, heat, steam & cooling'],
                'Scope 3' => ['title' => 'Scope 3 — Value Chain', 'subtitle' => 'Indirect emissions across your value chain (15 GHG Protocol categories)'],
            ];
        @endphp

        @foreach($scopeSections as $scopeKey => $meta)
            @php $scopeSources = $sourcesByScope->get($scopeKey, collect()); @endphp
            @if($scopeSources->isNotEmpty())
                <div class="qi-scope">
                    <div class="qi-scope__head">
                        <h3 class="qi-scope__title">{{ $meta['title'] }}</h3>
                        <span class="qi-scope__sub">{{ $meta['subtitle'] }}</span>
                        @if($scopeKey === 'Scope 3' && $gate->isScope3Locked())
                            <span class="mnz-chip mnz-chip--warn">Starter+</span>
                        @endif
                    </div>

                    @if($scopeKey === 'Scope 3' && $gate->isScope3Locked())
                        <div class="mnz-panel" style="border-color:var(--accent-line);background:var(--accent-tint)">
                            <div class="mnz-panel__body" style="display:flex;gap:16px;align-items:center;flex-wrap:wrap">
                                <p style="margin:0;flex:1;min-width:min(100%,320px);font-size:12.5px">
                                    @if($gate->isAgencyWorkspace())
                                        Scope 3 covers your value chain — purchased goods, travel, commuting, and more. {{ $gate->agencyLockedMessage('Scope 3') }}
                                    @else
                                        Scope 3 covers your value chain — purchased goods, travel, commuting, and more. Unlock preview mode on <strong>Starter</strong>.
                                    @endif
                                </p>
                                <a href="{{ $gate->upgradeRoute() }}" class="mnz-btn mnz-btn--accent">
                                    {{ $gate->upgradeButtonLabel('View agency packs') }}
                                </a>
                            </div>
                        </div>
                    @else
                        <div class="qi-sources">
                            @foreach($scopeSources as $source)
                                @php
                                    $scopeNumber = str_replace('Scope ', '', $source->scope);
                                    $slug = $source->quick_input_slug ?? '';
                                    $iconClass = 'qi-source__icon';
                                    $name = $source->name ?? '';
                                    $mainText = $name;
                                    $bracketText = '';
                                    if (preg_match('/^(.+?)\s*\((.+?)\)$/', $name, $matches)) {
                                        $mainText = trim($matches[1]);
                                        $bracketText = trim($matches[2]);
                                    }
                                @endphp
                                <a href="{{ route('quick-input.show', ['scope' => $scopeNumber, 'slug' => $source->quick_input_slug]) }}"
                                   class="qi-source">
                                    <span style="display:inline-flex">
                                        @include('quick-input.partials.source-icon', ['slug' => $slug, 'iconClass' => 'qi-source__icon'])
                                    </span>
                                    <div class="qi-source__title">{{ $mainText }}</div>
                                    @if($bracketText)
                                        <div class="qi-source__meta">({{ $bracketText }})</div>
                                    @endif
                                </a>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endif
        @endforeach
    </div>

    {{-- Summary --}}
    @if(isset($summary))
        <div class="mnz-seam mnz-seam--4">
            <div class="mnz-kpi">
                <div class="mnz-label">Total entries</div>
                <div class="mnz-kpi__value"><b>{{ number_format($summary->total_entries ?? 0) }}</b></div>
            </div>
            <div class="mnz-kpi">
                <div class="mnz-label">Total emissions</div>
                <div class="mnz-kpi__value"><b>{{ co2e_t($summary->total_co2e ?? 0) }}</b><span class="mnz-kpi__unit">tCO₂e</span></div>
            </div>
            <div class="mnz-kpi">
                <div class="mnz-label">Scope 1</div>
                <div class="mnz-kpi__value"><b>{{ co2e_t($summary->scope_1_co2e ?? 0) }}</b><span class="mnz-kpi__unit">tCO₂e</span></div>
            </div>
            <div class="mnz-kpi">
                <div class="mnz-label">Scope 2</div>
                <div class="mnz-kpi__value"><b>{{ co2e_t($summary->scope_2_co2e ?? 0, 4) }}</b><span class="mnz-kpi__unit">tCO₂e</span></div>
            </div>
        </div>
    @endif

    {{-- Filters --}}
    <div class="mnz-panel">
        <div class="mnz-panel__body">
            <div style="display:flex;gap:12px;align-items:end;flex-wrap:wrap">
                <form method="GET" action="{{ route('quick-input.index') }}" id="filter-form"
                      class="qi-filters" style="flex:1;min-width:min(100%,420px)">
                    <div class="mnz-field">
                        <label for="scope" class="mnz-label">Scope</label>
                        <select name="scope" id="scope" class="mnz-select">
                            <option value="">All scopes</option>
                            <option value="Scope 1" {{ request('scope') == 'Scope 1' ? 'selected' : '' }}>Scope 1</option>
                            <option value="Scope 2" {{ request('scope') == 'Scope 2' ? 'selected' : '' }}>Scope 2</option>
                            <option value="Scope 3" {{ request('scope') == 'Scope 3' ? 'selected' : '' }}>Scope 3</option>
                        </select>
                    </div>
                    <div class="mnz-field">
                        <label for="location_id" class="mnz-label">Location</label>
                        <select name="location_id" id="location_id" class="mnz-select">
                            <option value="">All locations</option>
                            @foreach($locations as $location)
                                <option value="{{ $location->id }}" {{ request('location_id') == $location->id ? 'selected' : '' }}>{{ $location->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mnz-field">
                        <label for="fiscal_year" class="mnz-label">Year</label>
                        <select name="fiscal_year" id="fiscal_year" class="mnz-select">
                            <option value="">All years</option>
                            @if(isset($yearsWithEntries) && count($yearsWithEntries) > 0)
                                @foreach($yearsWithEntries as $year)
                                    <option value="{{ $year }}" {{ request('fiscal_year') == $year ? 'selected' : '' }}>{{ $year }}</option>
                                @endforeach
                            @endif
                        </select>
                    </div>
                </form>
                <div style="display:flex;gap:8px">
                    <button type="submit" form="filter-form" class="mnz-btn mnz-btn--primary">Apply filters</button>
                    <x-plan-gated-link
                        :allowed="$gate->canBulkExport()"
                        :href="route('quick-input.export', request()->all())"
                        :message="$gate->bulkExportMessage()"
                        class="mnz-btn"
                        locked-class="mnz-btn">
                        Export CSV
                    </x-plan-gated-link>
                </div>
            </div>
        </div>
    </div>

    @php
        // Ids on the current page drive the select-all checkbox. Encoded here so
        // the x-data attribute below stays free of embedded quotes.
        $pageEntryIds = $entries->pluck('id')->values()->toJson();
    @endphp

    <div class="mnz-panel"
         x-data='{
            selected: [],
            pageIds: {!! $pageEntryIds !!},
            toggleAll(checked) {
                this.selected = checked ? [...this.pageIds] : [];
            },
            allSelected() {
                return this.pageIds.length > 0 && this.selected.length === this.pageIds.length;
            }
         }'>

        @if($canDeleteEntries)
            {{-- Bulk actions bar — only present once something is selected. --}}
            <div class="mnz-panel__head" x-show="selected.length > 0" x-cloak>
                <span style="font-size:12.5px">
                    <strong x-text="selected.length"></strong>
                    <span x-text="selected.length === 1 ? 'entry' : 'entries'"></span> selected
                </span>
                <div style="display:flex;align-items:center;gap:10px">
                    <button type="button" class="mnz-btn mnz-btn--ghost" @click="selected = []">
                        Clear selection
                    </button>
                    {{-- Alpine @submit ignores a returned false, so cancel explicitly. --}}
                    <form method="POST" action="{{ route('quick-input.bulk-destroy') }}"
                          @submit="if (!confirm('Delete ' + selected.length + ' selected ' + (selected.length === 1 ? 'entry' : 'entries') + '? This cannot be undone.')) $event.preventDefault()">
                        @csrf
                        @method('DELETE')
                        <template x-for="id in selected" :key="id">
                            <input type="hidden" name="entry_ids[]" :value="id">
                        </template>
                        <button type="submit" class="mnz-btn" style="border-color:var(--bad-line);color:var(--bad)">
                            Delete selected
                        </button>
                    </form>
                </div>
            </div>
        @endif

        <div style="overflow-x:auto">
            <table class="mnz-table" style="width:100%">
                <thead>
                    <tr>
                        @if($canDeleteEntries)
                            <th style="width:34px">
                                <input type="checkbox" aria-label="Select all entries on this page"
                                       @change="toggleAll($event.target.checked)"
                                       :checked="allSelected()">
                            </th>
                        @endif
                        <th>Date</th>
                        <th>Source</th>
                        <th>Location</th>
                        <th>Year</th>
                        <th>Quantity</th>
                        <th>Unit</th>
                        <th>tCO₂e</th>
                        <th>Scope</th>
                        <th style="text-align:right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($entries as $entry)
                        <tr :class="selected.includes({{ $entry->id }}) ? 'is-selected' : ''">
                            @if($canDeleteEntries)
                                <td>
                                    <input type="checkbox" value="{{ $entry->id }}" x-model.number="selected"
                                           aria-label="Select entry {{ $entry->id }}">
                                </td>
                            @endif
                            <td class="qi-tight">
                                {{ $entry->entry_date ? $entry->entry_date->format('Y-m-d') : 'N/A' }}
                            </td>
                            <td>
                                <div style="font-weight:500">{{ $entry->emissionSource->name ?? 'N/A' }}</div>
                                @php
                                    // Get type information from entry
                                    $additionalData = decode_json_field($entry->additional_data ?? []);

                                    $energyType = $additionalData['energy_type'] ?? null;
                                    $fuelCategory = $entry->fuel_category ?? ($additionalData['fuel_category'] ?? null);
                                    $fuelType = $entry->fuel_type ?? null;

                                    // Determine what to display
                                    $typeInfo = null;
                                    if ($energyType) {
                                        $typeInfo = 'Type: ' . $energyType;
                                    } elseif ($fuelType && $fuelCategory) {
                                        $typeInfo = 'Type: ' . $fuelCategory . ' — ' . $fuelType;
                                    } elseif ($fuelType) {
                                        // Just fuel type if no category
                                        $typeInfo = 'Type: ' . $fuelType;
                                    } elseif ($fuelCategory) {
                                        // Just category if no type
                                        $typeInfo = 'Type: ' . $fuelCategory;
                                    }
                                @endphp
                                @if($typeInfo)
                                    <div style="font-size:11.5px;color:var(--ink-3);margin-top:3px">{{ $typeInfo }}</div>
                                @endif
                            </td>
                            <td class="qi-tight">{{ $entry->measurement->location->name ?? 'N/A' }}</td>
                            <td class="qi-tight">{{ $entry->measurement->fiscal_year ?? 'N/A' }}</td>
                            <td class="qi-tight">{{ number_format($entry->quantity, 2) }}</td>
                            <td class="qi-tight">{{ $entry->unit }}</td>
                            <td class="qi-tight" style="font-weight:500">{{ co2e_t($entry->calculated_co2e, 4) }}</td>
                            <td class="qi-tight"><span class="mnz-chip">{{ $entry->scope }}</span></td>
                            <td style="text-align:right">
                                <div class="qi-actions">
                                    <a href="{{ route('quick-input.view', $entry->id) }}" title="View">
                                        <svg style="width:17px;height:17px" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                        </svg>
                                    </a>
                                    @php
                                        $scopeNumber = null;
                                        $slug = null;

                                        if ($entry->scope) {
                                            $scopeNumber = str_replace('Scope ', '', $entry->scope);
                                        }

                                        if ($entry->emissionSource && $entry->emissionSource->quick_input_slug) {
                                            $slug = $entry->emissionSource->quick_input_slug;
                                        }
                                    @endphp
                                    @if($slug && $scopeNumber && $entry->emissionSource)
                                        <a href="{{ route('quick-input.show', ['scope' => $scopeNumber, 'slug' => $slug, 'edit' => $entry->id, 'location_id' => $entry->measurement->location_id, 'fiscal_year' => $entry->measurement->fiscal_year]) }}" title="Edit">
                                            <svg style="width:17px;height:17px" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                            </svg>
                                        </a>
                                    @else
                                        <span style="color:var(--ink-4);cursor:not-allowed" title="Cannot edit - missing emission source information">
                                            <svg style="width:17px;height:17px" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                            </svg>
                                        </span>
                                    @endif
                                    <form action="{{ route('quick-input.destroy', $entry->id) }}" method="POST" style="display:inline" onsubmit="return confirm('Are you sure you want to delete this entry?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" style="background:none;border:0;padding:0;cursor:pointer;color:var(--bad)" title="Delete">
                                            <svg style="width:17px;height:17px" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $canDeleteEntries ? 10 : 9 }}">
                                <div class="mnz-empty">
                                    <div class="mnz-empty__title">No entries yet</div>
                                    <div class="mnz-empty__text">Start by adding electricity, fuel, or other emission sources above.</div>
                                    <a href="{{ route('quick-input.show', ['scope' => 2, 'slug' => 'electricity']) }}" class="mnz-btn mnz-btn--primary">Add electricity entry</a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($entries->hasPages())
            <div class="mnz-panel__foot">
                {{ $entries->withQueryString()->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
