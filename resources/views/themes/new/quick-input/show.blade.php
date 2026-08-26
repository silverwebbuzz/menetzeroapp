{{--
    MENetZero 2.0 — Quick Input entry page (Phase 6 body migration).

    SCOPE OF THIS MIGRATION — read before editing.

    The page CHROME is themed: header, year/location selector, the results
    table, and the layout. The ENTRY FORM is NOT themed. It is included
    verbatim from quick-input/partials/entry-form, shared with the old theme.

    WHY THE FORM IS EXCLUDED: public/js/quick-input.js (1,360 lines) binds ~35
    element ids, traverses .form-group-stacked / .form-group-horizontal with
    closest(), injects .field-error and reads .form-help-text at runtime, and
    posts to /api/quick-input/calculate — the endpoint that produces the
    emission numbers users save. Re-skinning that markup would break field
    show/hide, validation display and the calculation preview, and those
    failures surface as WRONG NUMBERS rather than visible layout breaks.

    Consequently this page still loads public/css/quick-input.css, so the form
    region renders exactly as it does today.

    PLAN GATING lives inside the shared partial (the Scope 3 limit branch uses
    $gate->isAgencyWorkspace(), agencyLockedMessage(), upgradeRoute() and
    upgradeButtonLabel()), so it is preserved automatically and cannot drift.

    KNOWN PRE-EXISTING BUG, carried across deliberately, NOT introduced here:
    $industryLabel is built in QuickInputController (line ~282) but is absent
    from the compact() at line ~364, so it never reaches the view. Because line
    23 of the original reads it inside a ?? chain, PHP suppresses the notice and
    it silently falls back to $emissionSource->instructions. Net effect: the
    industry-specific description, the "Common Equipment" panel and the
    "Typical Units" chip are dead code that never render, in BOTH themes. The
    behaviour is reproduced exactly here rather than silently "fixed", because
    fixing it would change what the live page shows. Logged in redesign.md §31.

    Controller data: $emissionSource $formFields $userFriendlyName $locations
    $availableUnits $scope $slug $selectedLocationId $selectedFiscalYear
    $measurement $existingEntries $yearsWithMeasurements $editEntry
    $scope3Limit $scope3LimitReached
    Composer data: $gate (PlanGateComposer)
--}}
@extends('layouts.app')

@section('title', ($userFriendlyName ?? $emissionSource->name) . ' - Quick Input - MENetZero')
@section('page-title', $userFriendlyName ?? $emissionSource->name)

@push('styles')
{{-- Load-bearing: the shared entry-form partial is styled entirely by this file. --}}
<link rel="stylesheet" href="{{asset('css/quick-input.css?v=20260620')}}">
<style>
    .qis-head { display: flex; align-items: flex-start; justify-content: space-between;
        gap: 20px; flex-wrap: wrap; }
    .qis-selector { display: grid; gap: 12px; align-items: end;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); }
    .qis-actions { display: flex; gap: 6px; justify-content: flex-end; align-items: center; }
    .qis-actions a, .qis-actions button { color: var(--ink-3); }
    .qis-actions a:hover { color: var(--accent); }
    table.mnz-table td.qis-tight { white-space: nowrap; }
</style>
@endpush

@section('content')
<div class="mnz-stack" data-pillar="e">

    <div class="mnz-pagehead">
        <div>
            <div class="mnz-kicker">Environmental · Measure</div>
            <h1>
                @if($editEntry)
                    <span style="color:var(--accent)">Edit:</span>
                @endif
                {{ $userFriendlyName ?? $emissionSource->name }}
            </h1>
            @php
                $instructions = $industryLabel->user_friendly_description ?? $emissionSource->instructions ?? null;
            @endphp
            @if($instructions)
                <p class="mnz-lead">{{ $instructions }}</p>
            @endif
            <x-field-help key="sections.quick_input.{{ $slug }}" class="mt-3" />
        </div>
    </div>

    @if(isset($industryLabel) && $industryLabel && $industryLabel->common_equipment)
        <div class="mnz-panel" style="border-color:var(--accent-line);background:var(--accent-tint)">
            <div class="mnz-panel__body">
                <p style="margin:0 0 4px;font-size:12.5px;font-weight:600">Common Equipment:</p>
                <p style="margin:0;font-size:12.5px">{{ $industryLabel->common_equipment }}</p>
            </div>
        </div>
    @endif

    @if(isset($industryLabel) && $industryLabel && $industryLabel->typical_units)
        <div>
            <span class="mnz-chip">Typical Units: {{ $industryLabel->typical_units }}</span>
        </div>
    @endif

    @if($errors->any())
        <div class="mnz-panel" style="border-color:var(--bad-line);background:var(--bad-tint)">
            <div class="mnz-panel__body" style="color:var(--bad)">
                <p style="margin:0 0 8px;font-weight:600;font-size:12.5px">Please correct the following errors:</p>
                <ul style="margin:0;padding-left:18px;font-size:12.5px">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    {{-- Year and location selection --}}
    <div class="mnz-panel">
        <div class="mnz-panel__body">
            <form method="GET" action="{{ route('quick-input.show', ['scope' => $scope, 'slug' => $slug]) }}">
                <div style="display:flex;gap:12px;align-items:end;flex-wrap:wrap">
                    <div class="qis-selector" style="flex:1;min-width:min(100%,360px)">
                        <div class="mnz-field">
                            <label for="fiscal_year" class="mnz-label">Year *</label>
                            <select name="fiscal_year" id="fiscal_year" required class="mnz-select">
                                <option value="">Select year…</option>
                                @foreach($yearsWithMeasurements as $year)
                                    <option value="{{ $year }}" {{ (string) $selectedFiscalYear === (string) $year ? 'selected' : '' }}>{{ $year }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mnz-field">
                            <label for="location_id" class="mnz-label">Location *</label>
                            <select name="location_id" id="location_id" required class="mnz-select">
                                <option value="">Select location…</option>
                                @foreach($locations as $location)
                                    <option value="{{ $location->id }}" {{ (string) $selectedLocationId === (string) $location->id ? 'selected' : '' }}>{{ $location->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <button type="submit" class="mnz-btn mnz-btn--primary">Load</button>
                </div>
            </form>
        </div>
    </div>

    @if($selectedLocationId && $selectedFiscalYear && $measurement)
        @php
            // Hoisted out of quick-input/partials/entry-form (Phase 6 extraction).
            //
            // These four are DEFINED inside that partial, but the hidden
            // *_initial_value inputs below it read them. @include receives a COPY
            // of the parent scope, so variables set inside a partial do not flow
            // back out — without this block those inputs render empty and
            // quick-input.js loses the edit-mode initial values, silently resetting
            // fuel category / fuel type / process type / unit when editing an entry.
            // Values are identical to the partial's own copy.
            $editAdditionalData = $editEntry
                ? decode_json_field($editEntry->additional_data ?? [])
                : [];
            $editFuelCategory = $editEntry ? ($editAdditionalData['fuel_category'] ?? null) : null;
            $editFuelType = $editEntry ? ($editEntry->fuel_type ?? null) : null;
            $editUnit = $editEntry ? ($editEntry->unit ?? null) : null;
            $editProcessType = $editEntry ? ($editEntry->fuel_type ?? null) : null;
        @endphp

        {{-- Shared VERBATIM with the old theme. Do not re-skin — see the header
             comment above and the partial's own header. --}}
        @include('quick-input.partials.entry-form')
    @else
        <div class="mnz-panel" style="border-color:var(--warn-line);background:var(--warn-tint)">
            <div class="mnz-panel__body" style="color:var(--warn)">
                <p style="margin:0 0 4px;font-weight:600;font-size:12.5px">Action Required</p>
                <p style="margin:0;font-size:12.5px">
                    Please select a <strong>Fiscal Year</strong> and <strong>Location</strong> above to start entering data.
                </p>
            </div>
        </div>
    @endif

    {{-- Results --}}
    @if($measurement)
        <div class="mnz-panel">
            <div class="mnz-panel__head">
                <div>
                    <h2 style="font-size:14px;font-weight:600;margin:0">Results</h2>
                    <p style="font-size:12px;color:var(--ink-3);margin:3px 0 0">
                        All entries for {{ $userFriendlyName ?? $emissionSource->name }} —
                        {{ $measurement->location->name ?? 'N/A' }} ({{ $selectedFiscalYear }})
                    </p>
                </div>
            </div>

            @if($existingEntries->count() > 0)
                <div style="overflow-x:auto">
                    <table class="mnz-table" style="width:100%">
                        <thead>
                            <tr>
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
                            @foreach($existingEntries as $entry)
                                <tr>
                                    <td class="qis-tight">{{ $entry->entry_date ? $entry->entry_date->format('Y-m-d') : 'N/A' }}</td>
                                    <td>
                                        <div style="font-weight:500">{{ $entry->emissionSource->name ?? 'N/A' }}</div>
                                        @php
                                            // Get type information from entry
                                            $additionalData = [];
                                            if ($entry->additional_data) {
                                                $additionalData = decode_json_field($entry->additional_data ?? []);
                                            }

                                            $energyType = $additionalData['energy_type'] ?? null;
                                            $fuelCategory = $entry->fuel_category ?? ($additionalData['fuel_category'] ?? null);
                                            $fuelType = $entry->fuel_type ?? null;

                                            // Determine what to display
                                            $typeInfo = null;
                                            if ($energyType) {
                                                // For Heat/Steam/Cooling
                                                $typeInfo = 'Type: ' . $energyType;
                                            } elseif ($fuelCategory && $fuelType) {
                                                // For Fuel sources: show category -> type
                                                $typeInfo = 'Type: ' . $fuelCategory . ' → ' . $fuelType;
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
                                    <td class="qis-tight">{{ $entry->measurement->location->name ?? 'N/A' }}</td>
                                    <td class="qis-tight">{{ $entry->measurement->fiscal_year ?? 'N/A' }}</td>
                                    <td class="qis-tight">{{ number_format($entry->quantity, 2) }}</td>
                                    <td class="qis-tight">{{ $entry->unit }}</td>
                                    <td class="qis-tight" style="font-weight:500">{{ co2e_t($entry->calculated_co2e, 4) }}</td>
                                    <td class="qis-tight"><span class="mnz-chip">{{ $entry->scope }}</span></td>
                                    <td style="text-align:right">
                                        <div class="qis-actions">
                                            <a href="{{ route('quick-input.view', $entry->id) }}" title="View">
                                                <svg style="width:17px;height:17px" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                                </svg>
                                            </a>
                                            @php
                                                $entryScopeNumber = $entry->scope ? str_replace('Scope ', '', $entry->scope) : null;
                                                $entrySlug = $entry->emissionSource->quick_input_slug ?? null;
                                                $isCurrentlyEditing = $editEntry && $editEntry->id == $entry->id;
                                            @endphp
                                            @if(!$isCurrentlyEditing && $entrySlug && $entryScopeNumber)
                                                <a href="{{ route('quick-input.show', ['scope' => $entryScopeNumber, 'slug' => $entrySlug, 'edit' => $entry->id, 'location_id' => $entry->measurement->location_id, 'fiscal_year' => $entry->measurement->fiscal_year]) }}" title="Edit">
                                                    <svg style="width:17px;height:17px" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                                    </svg>
                                                </a>
                                            @elseif($isCurrentlyEditing)
                                                <span style="color:var(--accent);font-weight:500;font-size:11px" title="Currently editing">Editing…</span>
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
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="mnz-empty">
                    <div class="mnz-empty__title">No entries yet</div>
                    <div class="mnz-empty__text">No entries, please add some data above.</div>
                </div>
            @endif
        </div>
    @endif

    {{-- Hidden inputs for edit mode initial values (for JavaScript).
         READ BY quick-input.js — ids are load-bearing, do not rename. --}}
    @if($editEntry)
        <input type="hidden" id="fuel_category_initial_value" value="{{ $editFuelCategory ?? '' }}">
        <input type="hidden" id="fuel_type_initial_value" value="{{ $editFuelType ?? '' }}">
        <input type="hidden" id="process_type_initial_value" value="{{ $editProcessType ?? '' }}">
        <input type="hidden" id="unit_of_measure_initial_value" value="{{ $editUnit ?? '' }}">
        <input type="hidden" id="unit_initial_value" value="{{ $editUnit ?? '' }}">
    @endif
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/quick-input.js')}}?v={{ time() }}"></script>
@endpush
