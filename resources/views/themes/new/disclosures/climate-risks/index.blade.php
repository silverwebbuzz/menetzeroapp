{{--
    MENetZero 2.0 -- Climate risk register (Environmental, IFRS S2 section 10).

    FORM CONTRACT verified against ClimateRiskController: name (required),
    risk_type (required, physical|transition), time_horizon (required enum),
    description, financial_impact, likelihood (enum low/medium/high),
    mitigation, owner, status (enum open/monitoring/closed).

    This is the FIRST new-theme copy of this page -- until now the route fell
    through to the Tailwind view, so the register was the only Environmental
    page that did not follow the 2.0 shell. Field coverage is unchanged from
    that view; only the presentation is new.

    Kept SEPARATE from sustainability_risks (D4, redesign.md section 18). The
    TYPE column here is the S2 physical/transition taxonomy and is never
    applied to S1 topics.

    disclosures.partials.header is REUSED unchanged -- it carries the
    reporting-year selector and depends on ReportingYearsComposer. Note it is
    included WITHOUT a framework key, matching the non-themed view.

    Controller data: $risks $fiscalYear $company
--}}
@extends('layouts.app')

@section('title', 'Climate Risks - IFRS S2')
@section('page-title', 'Climate Risk Register')

@section('content')
@php
    $totalRisks   = $risks->count();
    $highRisks    = $risks->where('likelihood', 'high')->count();
    $quantified   = $risks->filter(fn ($r) => filled($r->financial_impact))->count();
    $withoutOwner = $risks->filter(fn ($r) => blank($r->owner))->count();

    $likelihoodChip = ['high' => 'mnz-chip--bad', 'medium' => 'mnz-chip--warn', 'low' => 'mnz-chip--ok'];
@endphp
<div class="mnz-stack" data-pillar="e">

    @include('disclosures.partials.header', ['context' => 'register'])

    <div class="mnz-pagehead">
        <div>
            <div class="mnz-kicker">Environmental · IFRS S2</div>
            <h1>Climate risks</h1>
            <p class="mnz-lead">Physical and transition risks for {{ $fiscalYear }}, with horizon,
                likelihood, owner and financial effect.</p>
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

    {{-- Completeness tiles: every figure is counted from a stored column,
         none is scored or weighted. --}}
    <div class="mnz-seam mnz-seam--4">
        <div class="mnz-kpi">
            <div class="mnz-kicker">Total risks</div>
            <div class="mnz-kpi__value"><b>{{ $totalRisks }}</b></div>
        </div>
        <div class="mnz-kpi">
            <div class="mnz-kicker">High likelihood</div>
            <div class="mnz-kpi__value"><b style="{{ $highRisks > 0 ? 'color:var(--bad)' : '' }}">{{ $highRisks }}</b></div>
        </div>
        <div class="mnz-kpi">
            <div class="mnz-kicker">Quantified</div>
            <div class="mnz-kpi__value"><b>{{ $quantified }}</b><span class="mnz-kpi__unit">of {{ $totalRisks }}</span></div>
        </div>
        <div class="mnz-kpi">
            <div class="mnz-kicker">Without owner</div>
            <div class="mnz-kpi__value"><b style="{{ $withoutOwner > 0 ? 'color:var(--warn)' : '' }}">{{ $withoutOwner }}</b></div>
        </div>
    </div>

    <div class="mnz-panel">
        <div class="mnz-panel__head">
            <div><h3>Registered risks ({{ $totalRisks }})</h3></div>
        </div>

        @if ($totalRisks)
            <div style="overflow-x:auto">
                <div class="mnz-table" style="--cols: minmax(180px,2fr) minmax(100px,1fr) minmax(96px,.9fr) minmax(78px,.8fr) minmax(110px,1.2fr) minmax(90px,.9fr) 64px; min-width:900px">
                    <div class="mnz-table__head">
                        <span>Risk</span><span>Type</span><span>Horizon</span>
                        <span class="t-r">Likelihood</span><span class="t-r">Financial effect</span>
                        <span class="t-r">Owner</span><span class="t-r">Edit</span>
                    </div>
                    @foreach ($risks as $risk)
                        <div class="mnz-table__row">
                            <span class="t-name">{{ $risk->name }}</span>
                            <span class="mnz-muted">{{ \App\Models\ClimateRisk::TYPES[$risk->risk_type] ?? $risk->risk_type }}</span>
                            <span class="mnz-muted">{{ \App\Models\ClimateRisk::HORIZONS[$risk->time_horizon] ?? $risk->time_horizon }}</span>
                            <span class="t-r">
                                @if ($risk->likelihood)
                                    <span class="mnz-chip {{ $likelihoodChip[$risk->likelihood] ?? '' }}">{{ \App\Models\ClimateRisk::LIKELIHOODS[$risk->likelihood] ?? $risk->likelihood }}</span>
                                @else
                                    <span class="mnz-muted">—</span>
                                @endif
                            </span>
                            {{-- financial_impact is free text, not a number: shown
                                 verbatim, never parsed into a figure. --}}
                            <span class="t-r" style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap"
                                  title="{{ $risk->financial_impact }}">
                                {{ $risk->financial_impact ?: 'Not quantified' }}
                            </span>
                            <span class="t-r mnz-muted">{{ $risk->owner ?: 'Unassigned' }}</span>
                            <span class="t-r"><a class="mnz-lineage__link" href="#risk-{{ $risk->id }}">Open</a></span>
                        </div>
                    @endforeach
                </div>
            </div>
        @else
            <div class="mnz-panel__body">
                <div class="mnz-empty">
                    <div class="mnz-empty__title">No risks registered</div>
                    <div class="mnz-empty__text">No climate risks for {{ $fiscalYear }} yet.</div>
                </div>
            </div>
        @endif
    </div>

    <div class="mnz-panel">
        <div class="mnz-panel__head">
            <div>
                <h3>Add climate risk</h3>
                <p class="mnz-muted">IFRS S2 §10 — {{ $fiscalYear }}</p>
            </div>
        </div>
        <div class="mnz-panel__body">
            <form method="POST" action="{{ route('disclosures.s2.climate-risks.store', ['fiscal_year' => $fiscalYear]) }}">
                @csrf
                <input type="hidden" name="fiscal_year" value="{{ $fiscalYear }}">

                <div class="mnz-field">
                    <label class="mnz-label" for="risk_name">Risk name *</label>
                    <input class="mnz-input" id="risk_name" type="text" name="name" required value="{{ old('name') }}">
                </div>

                <div class="mnz-cols mnz-cols--half" style="margin-top:14px">
                    <div class="mnz-field">
                        <label class="mnz-label" for="risk_type">Type *</label>
                        <select class="mnz-select" id="risk_type" name="risk_type" required>
                            @foreach (\App\Models\ClimateRisk::TYPES as $value => $label)
                                <option value="{{ $value }}" @selected(old('risk_type') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mnz-field">
                        <label class="mnz-label" for="risk_horizon">Time horizon *</label>
                        <select class="mnz-select" id="risk_horizon" name="time_horizon" required>
                            @foreach (\App\Models\ClimateRisk::HORIZONS as $value => $label)
                                <option value="{{ $value }}" @selected(old('time_horizon') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="mnz-cols mnz-cols--half" style="margin-top:14px">
                    <div class="mnz-field">
                        <label class="mnz-label" for="risk_likelihood">Likelihood</label>
                        <select class="mnz-select" id="risk_likelihood" name="likelihood">
                            <option value="">—</option>
                            @foreach (\App\Models\ClimateRisk::LIKELIHOODS as $value => $label)
                                <option value="{{ $value }}" @selected(old('likelihood') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mnz-field">
                        <label class="mnz-label" for="risk_owner">Owner</label>
                        <input class="mnz-input" id="risk_owner" type="text" name="owner" value="{{ old('owner') }}"
                               placeholder="Role or team, e.g. CFO">
                        <p class="mnz-help">Who is accountable for managing this risk.</p>
                    </div>
                </div>

                <div class="mnz-field" style="margin-top:14px">
                    <label class="mnz-label" for="risk_description">Description</label>
                    <textarea class="mnz-textarea" id="risk_description" name="description" rows="2">{{ old('description') }}</textarea>
                </div>

                <div class="mnz-field" style="margin-top:14px">
                    <label class="mnz-label" for="risk_financial">Financial effect</label>
                    <textarea class="mnz-textarea" id="risk_financial" name="financial_impact" rows="2"
                              placeholder="Anticipated effect on financial position, performance or cash flows">{{ old('financial_impact') }}</textarea>
                    <p class="mnz-help">IFRS S2 asks for the anticipated financial effect. A range is acceptable.</p>
                </div>

                <div class="mnz-field" style="margin-top:14px">
                    <label class="mnz-label" for="risk_mitigation">Mitigation</label>
                    <textarea class="mnz-textarea" id="risk_mitigation" name="mitigation" rows="2">{{ old('mitigation') }}</textarea>
                </div>

                <div style="margin-top:16px">
                    <button type="submit" class="mnz-btn mnz-btn--primary">Add risk</button>
                </div>
            </form>
        </div>
    </div>

    @foreach ($risks as $risk)
        <details class="mnz-sec" id="risk-{{ $risk->id }}">
            <summary class="mnz-sec__btn">
                <span class="mnz-sec__chev">›</span>
                <span style="flex:1;min-width:0">{{ $risk->name }}</span>
                <span class="mnz-muted">{{ \App\Models\ClimateRisk::TYPES[$risk->risk_type] ?? $risk->risk_type }}</span>
            </summary>

            <div class="mnz-sec__panel">
                <form method="POST"
                      action="{{ route('disclosures.s2.climate-risks.update', ['climateRisk' => $risk, 'fiscal_year' => $fiscalYear]) }}">
                    @csrf @method('PUT')
                    <input type="hidden" name="fiscal_year" value="{{ $fiscalYear }}">

                    <div class="mnz-field">
                        <label class="mnz-label" for="name_{{ $risk->id }}">Risk name</label>
                        <input class="mnz-input" id="name_{{ $risk->id }}" type="text" name="name"
                               value="{{ $risk->name }}" required>
                    </div>

                    <div class="mnz-cols mnz-cols--half" style="margin-top:14px">
                        <div class="mnz-field">
                            <label class="mnz-label" for="type_{{ $risk->id }}">Type</label>
                            <select class="mnz-select" id="type_{{ $risk->id }}" name="risk_type">
                                @foreach (\App\Models\ClimateRisk::TYPES as $value => $label)
                                    <option value="{{ $value }}" @selected($risk->risk_type === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mnz-field">
                            <label class="mnz-label" for="horizon_{{ $risk->id }}">Time horizon</label>
                            <select class="mnz-select" id="horizon_{{ $risk->id }}" name="time_horizon">
                                @foreach (\App\Models\ClimateRisk::HORIZONS as $value => $label)
                                    <option value="{{ $value }}" @selected($risk->time_horizon === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="mnz-cols mnz-cols--half" style="margin-top:14px">
                        <div class="mnz-field">
                            <label class="mnz-label" for="likelihood_{{ $risk->id }}">Likelihood</label>
                            <select class="mnz-select" id="likelihood_{{ $risk->id }}" name="likelihood">
                                <option value="">—</option>
                                @foreach (\App\Models\ClimateRisk::LIKELIHOODS as $value => $label)
                                    <option value="{{ $value }}" @selected($risk->likelihood === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mnz-field">
                            <label class="mnz-label" for="owner_{{ $risk->id }}">Owner</label>
                            <input class="mnz-input" id="owner_{{ $risk->id }}" type="text" name="owner"
                                   value="{{ $risk->owner }}">
                        </div>
                    </div>

                    <div class="mnz-field" style="margin-top:14px">
                        <label class="mnz-label" for="desc_{{ $risk->id }}">Description</label>
                        <textarea class="mnz-textarea" id="desc_{{ $risk->id }}" name="description" rows="2">{{ $risk->description }}</textarea>
                    </div>

                    <div class="mnz-field" style="margin-top:14px">
                        <label class="mnz-label" for="fin_{{ $risk->id }}">Financial effect</label>
                        <textarea class="mnz-textarea" id="fin_{{ $risk->id }}" name="financial_impact" rows="2">{{ $risk->financial_impact }}</textarea>
                    </div>

                    <div class="mnz-field" style="margin-top:14px">
                        <label class="mnz-label" for="mit_{{ $risk->id }}">Mitigation</label>
                        <textarea class="mnz-textarea" id="mit_{{ $risk->id }}" name="mitigation" rows="2">{{ $risk->mitigation }}</textarea>
                    </div>

                    <div style="margin-top:14px">
                        <button type="submit" class="mnz-btn mnz-btn--soft">Update</button>
                    </div>
                </form>

                <form method="POST"
                      action="{{ route('disclosures.s2.climate-risks.destroy', ['climateRisk' => $risk, 'fiscal_year' => $fiscalYear]) }}"
                      onsubmit="return confirm('Remove this risk?')" style="margin-top:10px">
                    @csrf @method('DELETE')
                    <input type="hidden" name="fiscal_year" value="{{ $fiscalYear }}">
                    <button type="submit" class="mnz-btn mnz-btn--ghost">Delete</button>
                </form>
            </div>
        </details>
    @endforeach

</div>
@endsection
