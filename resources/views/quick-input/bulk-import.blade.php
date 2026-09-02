@extends('layouts.app')

@section('title', 'Bulk Import - MENetZero')
@section('page-title', 'Bulk Import')

@section('content')
<div class="w-full">
    <div class="mb-6">
        <p class="text-sm text-gray-500">Upload a year of data in one file instead of entering rows one at a time.</p>
        <p class="text-sm text-gray-500 mt-1">
            Prefer to enter rows individually?
            <a href="{{ route('quick-input.index') }}" class="text-brand-600 hover:underline">Go to View Entries</a>.
        </p>
    </div>

    @if(session('success'))
        <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
            {{ session('error') }}
        </div>
    @endif

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
                    <a href="{{ $gate->upgradeRoute() }}" class="btn btn-primary btn-sm">{{ $gate->upgradeButtonLabel('Upgrade Package') }}</a>
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

    <!-- Bulk Import — Scope 3 -->
    <div id="scope3-bulk-import" class="card mb-8">
        <div class="card-body">
        @if($gate->isScope3Locked())
            <div class="callout-panel callout-panel--brand callout-panel--row">
                <div>
                    <h2 class="callout-panel__title">Bulk import — Scope 3</h2>
                    @if($gate->isAgencyWorkspace())
                        <p class="callout-panel__body">Upload procurement spend, travel, commuting and waste totals in one file. {{ $gate->agencyLockedMessage('Scope 3') }}</p>
                    @else
                        <p class="callout-panel__body">Upload procurement spend, travel, commuting and waste totals in one file. Scope 3 is not available on your current package.</p>
                    @endif
                </div>
                <div class="callout-panel__actions">
                    <a href="{{ $gate->upgradeRoute() }}" class="btn btn-primary btn-sm">{{ $gate->upgradeButtonLabel('Upgrade Package') }}</a>
                </div>
            </div>
        @elseif(!$gate->canBulkImport())
            <div class="callout-panel callout-panel--brand callout-panel--row">
                <div>
                    <h2 class="callout-panel__title">Bulk import — Scope 3</h2>
                    @if($gate->isAgencyWorkspace())
                        <p class="callout-panel__body">Upload a year of value-chain data in one Excel file. {{ $gate->agencyLockedMessage('Bulk import') }}</p>
                    @else
                        <p class="callout-panel__body">Upload a year of value-chain data in one Excel file. Bulk import is available on paid packages.</p>
                    @endif
                </div>
                <div class="callout-panel__actions">
                    <a href="{{ $gate->upgradeRoute() }}" class="btn btn-primary btn-sm">{{ $gate->upgradeButtonLabel('Upgrade Package') }}</a>
                </div>
            </div>
        @else
        <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-6">
            <div class="flex-1">
                <h2 class="callout-panel__title">Bulk import — Scope 3</h2>
                <p class="callout-panel__body mb-3">
                    Report <strong>one total per category</strong> per year — not one row per employee or per flight.
                    The workbook includes calculators that turn your staff list or trip log into that total.
                    First time? Read the <a href="{{ route('quick-input.scope3-help-guide') }}" class="text-emerald-700 font-semibold underline hover:text-emerald-900">Scope 3 Help Guide</a>.
                </p>
                <a href="{{ route('quick-input.scope3-help-guide') }}"
                   class="btn btn-outline btn-sm mb-4">
                    <svg class="w-5 h-5 mr-2 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Open Scope 3 Help Guide
                </a>
                <ul class="text-sm text-gray-600 space-y-1 list-disc list-inside">
                    <li><strong>Reference sheet</strong> — all 66 valid Category / Activity Type / Unit combinations</li>
                    <li><strong>Calc: Commuting</strong> — one row per employee → total km</li>
                    <li><strong>Calc: Flights</strong> — one row per trip → total passenger.km</li>
                    <li>Copy <strong>Activity Type</strong> and <strong>Unit</strong> exactly — a wrong unit is the most common error</li>
                </ul>
            </div>
            <div class="flex flex-col gap-2 min-w-[220px]">
                <a href="{{ route('quick-input.scope3-bulk-import.template', ['format' => 'xlsx']) }}"
                   class="btn btn-primary btn-sm">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    Download Excel template
                </a>
                <a href="{{ route('quick-input.scope3-bulk-import.template', ['format' => 'csv', 'variant' => 'blank']) }}"
                   class="btn btn-secondary btn-sm">
                    Download blank CSV
                </a>
                <a href="{{ route('quick-input.scope3-bulk-import.template', ['format' => 'csv', 'variant' => 'sample']) }}"
                   class="btn btn-secondary btn-sm">
                    Download sample CSV (with examples)
                </a>
            </div>
        </div>

        <form action="{{ route('quick-input.scope3-bulk-import.import') }}" method="POST" enctype="multipart/form-data" class="mt-6 pt-6 border-t border-gray-200">
            @csrf
            <div class="flex flex-col sm:flex-row sm:items-end gap-4">
                <div class="flex-1 form-group mb-0">
                    <label for="scope3_import_file" class="form-label">Upload completed file</label>
                    <input type="file" name="import_file" id="scope3_import_file" accept=".xlsx,.xls,.csv,.txt" required class="form-control">
                    <p class="form-help">Excel (.xlsx) or CSV — max 5 MB. Only the <strong>Data Entry</strong> sheet is imported; the calculator sheets are ignored.</p>
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
</div>
@endsection
