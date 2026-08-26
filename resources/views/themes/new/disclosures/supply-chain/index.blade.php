{{--
    MENetZero 2.0 — Supply chain register (Phase 5.4, Social).

    FORM CONTRACT verified against SupplyChainSupplierController::store():
    supplier_name (required), category (required enum), spend_aed, country,
    screening_status (enum), human_rights_assessed, environmental_assessed,
    notes.

    NOTE scope3_category is validated by the controller but has NO input in
    the view this replaces. Not added here — adding a field is a behaviour
    change, and Phase 5 reproduces the existing contract.

    Checkbox booleans post value="1"; the controller re-reads them with
    $request->boolean(), so an unchecked box correctly becomes false.

    Controller data: $suppliers $fiscalYear $totalSpend $screenedCount
--}}
@extends('layouts.app')

@section('title', 'Supply Chain')
@section('page-title', 'Supply Chain')

@section('content')
<div class="mnz-stack" data-pillar="s">

    @include('theme-new::layouts.partials.nav-disclosures-esg-depth', ['fiscalYear' => $fiscalYear])

    <div class="mnz-pagehead">
        <div>
            <div class="mnz-kicker">Social · supply chain</div>
            <h1>Supplier register</h1>
            <p class="mnz-lead">Spend, screening status and assessment coverage for {{ $fiscalYear }}.</p>
        </div>
        <div class="mnz-pagehead__actions">
            <a href="{{ route('disclosures.gri.sections.edit', ['fiscal_year' => $fiscalYear, 'section' => 'supply_chain']) }}"
               class="mnz-btn mnz-btn--ghost">GRI narrative</a>
        </div>
    </div>

    @if (session('success'))
        <div class="mnz-panel" style="border-color:var(--ok-line);background:var(--ok-tint)">
            <div class="mnz-panel__body" style="color:var(--ok)">{{ session('success') }}</div>
        </div>
    @endif

    @if ($errors->any())
        <div class="mnz-panel" style="border-color:var(--bad-line);background:var(--bad-tint)">
            <div class="mnz-panel__body" style="color:var(--bad)">
                <ul style="margin:0;padding-left:18px;font-size:12.5px">
                    @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                </ul>
            </div>
        </div>
    @endif

    <div class="mnz-seam mnz-seam--3">
        <div class="mnz-kpi">
            <div class="mnz-label">Suppliers</div>
            <div class="mnz-kpi__value">{{ $suppliers->count() }}</div>
        </div>
        <div class="mnz-kpi">
            <div class="mnz-label">Total spend</div>
            <div class="mnz-kpi__value">{{ number_format($totalSpend ?? 0, 0) }}<span class="mnz-kpi__unit">AED</span></div>
        </div>
        <div class="mnz-kpi">
            <div class="mnz-label">Screened</div>
            <div class="mnz-kpi__value">{{ $screenedCount ?? 0 }}</div>
        </div>
    </div>

    <div class="mnz-panel">
        <div class="mnz-panel__head">
            <div>
                <h3>Add supplier</h3>
                <p class="mnz-muted">{{ $fiscalYear }}</p>
            </div>
        </div>
        <div class="mnz-panel__body">
            <x-field-help key="esg_depth.supply_chain.intro" class="mb-4" />

            <form method="POST" action="{{ route('disclosures.supply-chain.store', ['fiscal_year' => $fiscalYear]) }}">
                @csrf

                <div class="mnz-field">
                    <label class="mnz-label" for="supplier_name">Supplier name *</label>
                    <input class="mnz-input" id="supplier_name" type="text" name="supplier_name" required
                           value="{{ old('supplier_name') }}">
                    <x-field-help key="esg_depth.supply_chain.supplier_name" class="mt-1" />
                </div>

                <div class="mnz-cols mnz-cols--half" style="margin-top:14px">
                    <div class="mnz-field">
                        <label class="mnz-label" for="category">Category</label>
                        <select class="mnz-select" id="category" name="category">
                            @foreach (\App\Models\SupplyChainSupplier::CATEGORIES as $val => $label)
                                <option value="{{ $val }}" @selected(old('category') === $val)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <x-field-help key="esg_depth.supply_chain.category" class="mt-1" />
                    </div>

                    <div class="mnz-field">
                        <label class="mnz-label" for="screening_status">Screening status</label>
                        <select class="mnz-select" id="screening_status" name="screening_status">
                            @foreach (\App\Models\SupplyChainSupplier::SCREENING as $val => $label)
                                <option value="{{ $val }}" @selected(old('screening_status') === $val)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <x-field-help key="esg_depth.supply_chain.screening_status" class="mt-1" />
                    </div>
                </div>

                <div class="mnz-cols mnz-cols--half" style="margin-top:14px">
                    <div class="mnz-field">
                        <label class="mnz-label" for="spend_aed">Spend (AED)</label>
                        <input class="mnz-input" id="spend_aed" type="number" step="0.01" name="spend_aed"
                               value="{{ old('spend_aed') }}">
                        <x-field-help key="esg_depth.supply_chain.spend_aed" class="mt-1" />
                    </div>

                    <div class="mnz-field">
                        <label class="mnz-label" for="country">Country</label>
                        <input class="mnz-input" id="country" type="text" name="country"
                               value="{{ old('country') }}">
                        <x-field-help key="esg_depth.supply_chain.country" class="mt-1" />
                    </div>
                </div>

                <div style="display:flex;flex-wrap:wrap;gap:18px;margin-top:14px">
                    <label style="display:flex;align-items:center;gap:9px">
                        <input type="checkbox" name="human_rights_assessed" value="1" @checked(old('human_rights_assessed'))>
                        Human rights assessed
                    </label>
                    <label style="display:flex;align-items:center;gap:9px">
                        <input type="checkbox" name="environmental_assessed" value="1" @checked(old('environmental_assessed'))>
                        Environmental assessed
                    </label>
                </div>

                <div class="mnz-field" style="margin-top:14px">
                    <label class="mnz-label" for="notes">Notes</label>
                    <textarea class="mnz-input" id="notes" name="notes" rows="2">{{ old('notes') }}</textarea>
                    <x-field-help key="esg_depth.supply_chain.notes" class="mt-1" />
                </div>

                <div style="margin-top:16px">
                    <button type="submit" class="mnz-btn mnz-btn--primary">Add supplier</button>
                </div>
            </form>
        </div>
    </div>

    <div class="mnz-panel">
        <div class="mnz-panel__head">
            <div><h3>Register ({{ $suppliers->count() }})</h3></div>
        </div>

        @if ($suppliers->isEmpty())
            <div class="mnz-panel__body">
                <div class="mnz-empty">
                    <div class="mnz-empty__title">No suppliers recorded</div>
                    <div class="mnz-empty__text">Nothing recorded for {{ $fiscalYear }}.</div>
                </div>
            </div>
        @else
            <div class="mnz-table" style="--cols: 2fr 1fr 1fr 1fr 1fr 60px 60px 96px">
                <div class="mnz-table__head">
                    <span>Supplier</span><span>Category</span><span class="t-r">Spend AED</span>
                    <span>Country</span><span>Screening</span><span>H.R.</span><span>Env.</span>
                    <span class="t-r">Action</span>
                </div>
                @foreach ($suppliers as $s)
                    <div class="mnz-table__row">
                        <span class="t-name">{{ $s->supplier_name }}</span>
                        <span>{{ \App\Models\SupplyChainSupplier::CATEGORIES[$s->category] ?? $s->category }}</span>
                        <span class="t-r mnz-mono">{{ $s->spend_aed !== null ? number_format($s->spend_aed, 0) : '—' }}</span>
                        <span>{{ $s->country ?: '—' }}</span>
                        <span>{{ \App\Models\SupplyChainSupplier::SCREENING[$s->screening_status] ?? $s->screening_status }}</span>
                        <span>{{ $s->human_rights_assessed ? '✓' : '—' }}</span>
                        <span>{{ $s->environmental_assessed ? '✓' : '—' }}</span>
                        <span class="t-r">
                            <form method="POST"
                                  action="{{ route('disclosures.supply-chain.destroy', ['supplyChainSupplier' => $s, 'fiscal_year' => $fiscalYear]) }}"
                                  onsubmit="return confirm('Remove supplier?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="mnz-btn mnz-btn--ghost">Remove</button>
                            </form>
                        </span>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

</div>
@endsection
