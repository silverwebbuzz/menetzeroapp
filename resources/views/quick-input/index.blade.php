@extends('layouts.app')

@section('title', 'Quick Input Entries - MENetZero')
@section('page-title', 'Quick Input Entries')

@section('content')
<div class="w-full">
    <div class="page-header">
        <div>
            <h1>Input data</h1>
            <p>View and manage emission entries across all scopes, locations, and reporting years.</p>
        </div>
        <div class="page-header-actions">
            <x-plan-gated-link
                :allowed="$gate->canHelpGuide()"
                :href="route('quick-input.help-guide')"
                :message="$gate->helpGuideMessage()"
                class="btn btn-secondary btn-sm"
                locked-class="btn btn-secondary btn-sm opacity-80">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Scope 1 &amp; 2 Help Guide
            </x-plan-gated-link>
        </div>
    </div>

    @if($errors->any())
        <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
            <ul class="list-disc list-inside">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if(session('import_errors') && count(session('import_errors')) > 0)
        <div class="mb-4 bg-amber-50 border border-amber-300 text-amber-900 px-4 py-3 rounded relative" role="alert">
            <p class="font-semibold mb-2">Import row errors:</p>
            <ul class="list-disc list-inside text-sm max-h-40 overflow-y-auto">
                @foreach(session('import_errors') as $importError)
                    <li>{{ $importError }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Bulk Import — Scope 1 & 2 -->
    <div id="bulk-import" class="card mb-8">
        <div class="card-body">
        @if(!$gate->canBulkImport())
            <div class="callout-panel callout-panel--brand callout-panel--row">
                <div>
                    <h2 class="callout-panel__title">Bulk import — Scope 1 &amp; 2</h2>
                    @if($gate->isAgencyWorkspace())
                        <p class="callout-panel__body">Upload DEWA bills, fuel receipts, and fleet data in one Excel or CSV file. {{ $gate->agencyLockedMessage('Bulk import') }}</p>
                    @else
                        <p class="callout-panel__body">Upload DEWA bills, fuel receipts, and fleet data in one Excel or CSV file. Available on <strong>Starter</strong> (AED 1,499/year) and above.</p>
                    @endif
                </div>
                <div class="callout-panel__actions">
                    <a href="{{ $gate->upgradeRoute() }}" class="btn btn-primary btn-sm">{{ $gate->upgradeButtonLabel('Upgrade to Starter') }}</a>
                </div>
            </div>
        @else
        <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-6">
            <div class="flex-1">
                <h2 class="callout-panel__title">Bulk import — Scope 1 &amp; 2</h2>
                <p class="callout-panel__body mb-3">
                    First time? Read the <a href="{{ route('quick-input.help-guide') }}" class="text-emerald-700 font-semibold underline hover:text-emerald-900">Scope 1 &amp; 2 Help Guide</a> first —
                    it explains every field, which unit to use, and where to find numbers on DEWA bills, fuel receipts, etc.
                </p>
                <a href="{{ route('quick-input.help-guide') }}"
                   class="btn btn-outline btn-sm mb-4">
                    <svg class="w-5 h-5 mr-2 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Open Help Guide — what data do I need?
                </a>
                <ul class="text-sm text-gray-600 space-y-1 list-disc list-inside">
                    <li><strong>Excel (recommended)</strong> — Instructions, Data Guide, examples, blank sheet, your locations</li>
                    <li><strong>CSV</strong> — Blank template or sample file with dummy data</li>
                    <li>One row = one bill (e.g. January DEWA invoice)</li>
                </ul>
            </div>
            <div class="flex flex-col gap-2 min-w-[220px]">
                <a href="{{ route('quick-input.bulk-import.template', ['format' => 'xlsx']) }}"
                   class="btn btn-primary btn-sm">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    Download Excel template
                </a>
                <a href="{{ route('quick-input.bulk-import.template', ['format' => 'csv', 'variant' => 'blank']) }}"
                   class="btn btn-secondary btn-sm">
                    Download blank CSV
                </a>
                <a href="{{ route('quick-input.bulk-import.template', ['format' => 'csv', 'variant' => 'sample']) }}"
                   class="btn btn-secondary btn-sm">
                    Download sample CSV (with examples)
                </a>
            </div>
        </div>

        <form action="{{ route('quick-input.bulk-import.import') }}" method="POST" enctype="multipart/form-data" class="mt-6 pt-6 border-t border-gray-200">
            @csrf
            <div class="flex flex-col sm:flex-row sm:items-end gap-4">
                <div class="flex-1 form-group mb-0">
                    <label for="import_file" class="form-label">Upload completed file</label>
                    <input type="file" name="import_file" id="import_file" accept=".xlsx,.xls,.csv,.txt" required class="form-control">
                    <p class="form-help">Excel (.xlsx) or CSV — max 5 MB. Use the <strong>Data Entry</strong> sheet for Excel uploads.</p>
                </div>
                @if(!empty($canAddEntries))
                <button type="submit" class="btn btn-primary whitespace-nowrap">
                    Upload &amp; import
                </button>
                @else
                <p class="text-sm text-gray-500 italic">You need add permission to upload data.</p>
                @endif
            </div>
        </form>
        @endif
        </div>
    </div>

    <!-- Input Forms - Grouped by Scope -->
    <div class="mb-8">
        <h2 class="section-heading">Input forms</h2>
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
                <div class="scope-block">
                    <div class="scope-block__head">
                        <h3 class="scope-block__title">{{ $meta['title'] }}</h3>
                        <span class="scope-block__subtitle">{{ $meta['subtitle'] }}</span>
                        @if($scopeKey === 'Scope 3' && $gate->isScope3Locked())
                            <span class="badge-plan">Starter+</span>
                        @endif
                    </div>
                    @if($scopeKey === 'Scope 3' && $gate->isScope3Locked())
                        <div class="callout-panel callout-panel--brand callout-panel--row">
                            @if($gate->isAgencyWorkspace())
                                <p class="callout-panel__body">Scope 3 covers your value chain — purchased goods, travel, commuting, and more. {{ $gate->agencyLockedMessage('Scope 3') }}</p>
                            @else
                                <p class="callout-panel__body">Scope 3 covers your value chain — purchased goods, travel, commuting, and more. Unlock preview mode on <strong>Starter</strong>.</p>
                            @endif
                            <div class="callout-panel__actions">
                                <a href="{{ $gate->upgradeRoute() }}" class="btn btn-primary btn-sm">{{ $gate->upgradeButtonLabel('View agency packs') }}</a>
                            </div>
                        </div>
                    @else
                    <div class="source-grid">
                        @foreach($scopeSources as $source)
                            @php
                                $scopeNumber = str_replace('Scope ', '', $source->scope);
                            @endphp
                            <a href="{{ route('quick-input.show', ['scope' => $scopeNumber, 'slug' => $source->quick_input_slug]) }}"
                               class="source-card">
                        <div class="source-card__icon">
                            @php
                                $slug = $source->quick_input_slug ?? '';
                                $iconClass = 'w-6 h-6 text-green-600';
                            @endphp
                            @if($slug == 'natural-gas')
                                <svg class="{{ $iconClass }}" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3zM6 8a2 2 0 11-4 0 2 2 0 014 0zM16 18v-3a5.972 5.972 0 00-.75-2.906A3.005 3.005 0 0119 15v3h-3zM4.75 12.094A5.973 5.973 0 004 15v3H1v-3a3 3 0 013.75-2.906z"></path>
                                </svg>
                            @elseif($slug == 'fuel')
                                <svg class="{{ $iconClass }}" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4zM3 10a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H4a1 1 0 01-1-1v-6zM14 9a1 1 0 00-1 1v6a1 1 0 001 1h2a1 1 0 001-1v-6a1 1 0 00-1-1h-2z"></path>
                                </svg>
                            @elseif($slug == 'vehicle')
                                <svg class="{{ $iconClass }}" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M8 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM15 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z"></path>
                                    <path d="M3 4a1 1 0 00-1 1v10a1 1 0 001 1h1.05a2.5 2.5 0 014.9 0H10a1 1 0 001-1V5a1 1 0 00-1-1H3zM14 7a1 1 0 00-1 1v6.05A2.5 2.5 0 0115.95 16H17a1 1 0 001-1v-5a1 1 0 00-.293-.707l-2-2A1 1 0 0015 7h-1z"></path>
                                </svg>
                            @elseif($slug == 'refrigerants')
                                <svg class="{{ $iconClass }}" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M12.316 3.051a1 1 0 01.633 1.265l-4 12a1 1 0 11-1.898-.632l4-12a1 1 0 011.265-.633zM5.707 6.293a1 1 0 010 1.414L3.414 10l2.293 2.293a1 1 0 11-1.414 1.414l-3-3a1 1 0 010-1.414l3-3a1 1 0 011.414 0zm8.586 0a1 1 0 011.414 0l3 3a1 1 0 010 1.414l-3 3a1 1 0 11-1.414-1.414L16.586 10l-2.293-2.293a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                                </svg>
                            @elseif($slug == 'process')
                                <svg class="{{ $iconClass }}" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M11.49 3.17c-.38-1.56-2.6-1.56-2.98 0a1.532 1.532 0 01-2.286.948c-1.372-.836-2.942.734-2.106 2.106.54.886.061 2.042-.947 2.287-1.561.379-1.561 2.6 0 2.978a1.532 1.532 0 01.947 2.287c-.836 1.372.734 2.942 2.106 2.106a1.532 1.532 0 012.287.947c.379 1.561 2.6 1.561 2.978 0a1.533 1.533 0 012.287-.947c1.372.836 2.942-.734 2.106-2.106a1.533 1.533 0 01.947-2.287c1.561-.379 1.561-2.6 0-2.978a1.532 1.532 0 01-.947-2.287c.836-1.372-.734-2.942-2.106-2.106a1.532 1.532 0 01-2.287-.947zM10 13a3 3 0 100-6 3 3 0 000 6z" clip-rule="evenodd"></path>
                                </svg>
                            @elseif($slug == 'electricity')
                                <svg class="{{ $iconClass }}" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M11 3a1 1 0 100 2h2.586l-6.293 6.293a1 1 0 101.414 1.414L15 6.414V9a1 1 0 102 0V4a1 1 0 00-1-1h-5z"></path>
                                    <path d="M5 5a2 2 0 00-2 2v8a2 2 0 002 2h8a2 2 0 002-2v-3a1 1 0 10-2 0v3H5V7h3a1 1 0 000-2H5z"></path>
                                </svg>
                            @elseif($slug == 'heat-steam-cooling')
                                <svg class="{{ $iconClass }}" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M12.316 3.051a1 1 0 01.633 1.265l-4 12a1 1 0 11-1.898-.632l4-12a1 1 0 011.265-.633zM5.707 6.293a1 1 0 010 1.414L3.414 10l2.293 2.293a1 1 0 11-1.414 1.414l-3-3a1 1 0 010-1.414l3-3a1 1 0 011.414 0zm8.586 0a1 1 0 011.414 0l3 3a1 1 0 010 1.414l-3 3a1 1 0 11-1.414-1.414L16.586 10l-2.293-2.293a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                                </svg>
                            @elseif($slug == 'flights')
                                <svg class="{{ $iconClass }}" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M10.894 2.553a1 1 0 00-1.788 0l-7 14a1 1 0 001.169 1.409l5-1.429A1 1 0 009 15.571V11a1 1 0 112 0v4.571a1 1 0 00.725.962l5 1.428a1 1 0 001.17-1.408l-7-14z"></path>
                                </svg>
                            @elseif($slug == 'public-transport')
                                <svg class="{{ $iconClass }}" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M8 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM15 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z"></path>
                                    <path d="M3 4a1 1 0 00-1 1v10a1 1 0 001 1h1.05a2.5 2.5 0 014.9 0H10a1 1 0 001-1V5a1 1 0 00-1-1H3zM14 7a1 1 0 00-1 1v6.05A2.5 2.5 0 0115.95 16H17a1 1 0 001-1v-5a1 1 0 00-.293-.707l-2-2A1 1 0 0015 7h-1z"></path>
                                </svg>
                            @elseif($slug == 'home-workers')
                                <svg class="{{ $iconClass }}" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"></path>
                                </svg>
                            @else
                                <svg class="{{ $iconClass }}" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                </svg>
                            @endif
                        </div>
                        @php
                            $name = $source->name ?? '';
                            $mainText = $name;
                            $bracketText = '';
                            if (preg_match('/^(.+?)\s*\((.+?)\)$/', $name, $matches)) {
                                $mainText = trim($matches[1]);
                                $bracketText = trim($matches[2]);
                            }
                        @endphp
                        <div class="source-card__title">{{ $mainText }}</div>
                        @if($bracketText)
                            <div class="source-card__meta">({{ $bracketText }})</div>
                        @endif
                            </a>
                        @endforeach
                    </div>
                    @endif
                </div>
            @endif
        @endforeach
    </div>

    @if(isset($summary))
    <div class="stat-grid">
        <div class="stat-card">
            <div class="stat-card-label">Total entries</div>
            <div class="stat-card-value">{{ number_format($summary->total_entries ?? 0) }}</div>
        </div>
        <div class="stat-card">
            <div class="stat-card-label">Total emissions</div>
            <div class="stat-card-value">{{ co2e_t($summary->total_co2e ?? 0) }} <span class="text-sm font-medium text-gray-500">tCO₂e</span></div>
        </div>
        <div class="stat-card">
            <div class="stat-card-label">Scope 1</div>
            <div class="stat-card-value">{{ co2e_t($summary->scope_1_co2e ?? 0) }} <span class="text-sm font-medium text-gray-500">tCO₂e</span></div>
        </div>
        <div class="stat-card">
            <div class="stat-card-label">Scope 2</div>
            <div class="stat-card-value">{{ co2e_t($summary->scope_2_co2e ?? 0, 4) }} <span class="text-sm font-medium text-gray-500">tCO₂e</span></div>
        </div>
    </div>
    @endif

    <div class="filter-toolbar mb-6">
        <div class="filter-toolbar__layout">
            <form method="GET" action="{{ route('quick-input.index') }}" id="filter-form" class="filter-toolbar__grid flex-1">
                <div class="field-stack">
                    <label for="scope" class="form-label">Scope</label>
                    <select name="scope" id="scope" class="form-select">
                        <option value="">All scopes</option>
                        <option value="Scope 1" {{ request('scope') == 'Scope 1' ? 'selected' : '' }}>Scope 1</option>
                        <option value="Scope 2" {{ request('scope') == 'Scope 2' ? 'selected' : '' }}>Scope 2</option>
                        <option value="Scope 3" {{ request('scope') == 'Scope 3' ? 'selected' : '' }}>Scope 3</option>
                    </select>
                </div>
                <div class="field-stack">
                    <label for="location_id" class="form-label">Location</label>
                    <select name="location_id" id="location_id" class="form-select">
                        <option value="">All locations</option>
                        @foreach($locations as $location)
                            <option value="{{ $location->id }}" {{ request('location_id') == $location->id ? 'selected' : '' }}>{{ $location->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field-stack">
                    <label for="fiscal_year" class="form-label">Year</label>
                    <select name="fiscal_year" id="fiscal_year" class="form-select">
                        <option value="">All years</option>
                        @if(isset($yearsWithEntries) && count($yearsWithEntries) > 0)
                            @foreach($yearsWithEntries as $year)
                                <option value="{{ $year }}" {{ request('fiscal_year') == $year ? 'selected' : '' }}>{{ $year }}</option>
                            @endforeach
                        @endif
                    </select>
                </div>
                <div class="filter-toolbar__actions md:hidden">
                    <button type="submit" class="btn btn-primary btn-sm w-full">Apply filters</button>
                </div>
            </form>
            <div class="filter-toolbar__actions hidden md:flex">
                <button type="submit" form="filter-form" class="btn btn-primary btn-sm">Apply filters</button>
                <x-plan-gated-link
                    :allowed="$gate->canBulkExport()"
                    :href="route('quick-input.export', request()->all())"
                    :message="$gate->bulkExportMessage()"
                    class="btn btn-secondary btn-sm"
                    locked-class="btn btn-secondary btn-sm opacity-80">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    Export CSV
                </x-plan-gated-link>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="table-wrap">
            <table class="table min-w-full">
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
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($entries as $entry)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ $entry->entry_date ? $entry->entry_date->format('Y-m-d') : 'N/A' }}
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-900">
                                <div>
                                    <div class="font-medium">{{ $entry->emissionSource->name ?? 'N/A' }}</div>
                                    @php
                                        // Get type information from entry
                                        $additionalData = decode_json_field($entry->additional_data ?? []);
                                        
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
                                        <div class="text-xs text-gray-500 mt-1">
                                            {{ $typeInfo }}
                                        </div>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $entry->measurement->location->name ?? 'N/A' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $entry->measurement->fiscal_year ?? 'N/A' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ number_format($entry->quantity, 2) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $entry->unit }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                {{ co2e_t($entry->calculated_co2e, 4) }}
                            </td>
                            <td class="cell-muted whitespace-nowrap">
                                <span class="badge badge-neutral">{{ $entry->scope }}</span>
                            </td>
                            <td class="text-right">
                                <div class="row-actions">
                                    <a href="{{ route('quick-input.view', $entry->id) }}" class="text-blue-600 hover:text-blue-900" title="View">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                                        <a href="{{ route('quick-input.show', ['scope' => $scopeNumber, 'slug' => $slug, 'edit' => $entry->id, 'location_id' => $entry->measurement->location_id, 'fiscal_year' => $entry->measurement->fiscal_year]) }}" class="text-indigo-600 hover:text-indigo-900" title="Edit">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                            </svg>
                                        </a>
                                    @else
                                        <span class="text-gray-400 cursor-not-allowed" title="Cannot edit - missing emission source information">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                            </svg>
                                        </span>
                                    @endif
                                    <form action="{{ route('quick-input.destroy', $entry->id) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this entry?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-900" title="Delete">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9">
                                <div class="empty-state">
                                    <p class="empty-state__title">No entries yet</p>
                                    <p class="empty-state__text">Start by adding electricity, fuel, or other emission sources above.</p>
                                    <div class="empty-state__action">
                                        <a href="{{ route('quick-input.show', ['scope' => 2, 'slug' => 'electricity']) }}" class="btn btn-primary btn-sm">Add electricity entry</a>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($entries->hasPages())
            <div class="card-footer">
                {{ $entries->withQueryString()->links() }}
            </div>
        @endif
    </div>
</div>
@endsection

