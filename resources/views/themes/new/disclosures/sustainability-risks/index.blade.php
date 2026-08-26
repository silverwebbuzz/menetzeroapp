{{--
    MENetZero 2.0 — Sustainability risk register (Phase 5.5, Governance).

    FORM CONTRACT verified against SustainabilityRiskController: name
    (required), topic (required), time_horizon (required enum), description,
    financial_impact, likelihood, mitigation, owner, status.

    The add and edit forms in the view being replaced expose only name, topic,
    time_horizon and description — the other five rules accept input the UI
    never sends. Reproduced as-is; adding fields is a behaviour change.

    D4 (redesign.md section 18): this register stays separate from
    climate_risks. Their schemas are in fact identical apart from the
    discriminator (topic vs risk_type), so no migration is needed.

    disclosures.partials.header is REUSED unchanged — it carries the
    reporting-year selector and depends on ReportingYearsComposer. Rewriting
    it would risk silently changing the user's year.

    Controller data: $risks $topics $fiscalYear
--}}
@extends('layouts.app')

@section('title', 'Sustainability Risks - IFRS S1')
@section('page-title', 'Sustainability Risk Register')

@section('content')
<div class="mnz-stack" data-pillar="g">

    @include('disclosures.partials.header', ['framework' => 'ifrs_s1'])

    <div class="mnz-pagehead">
        <div>
            <div class="mnz-kicker">Governance · IFRS S1</div>
            <h1>Sustainability risks</h1>
            <p class="mnz-lead">Broader than climate — water, workforce, supply chain and others.</p>
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

    <div class="mnz-panel">
        <div class="mnz-panel__head">
            <div>
                <h3>Add sustainability risk</h3>
                <p class="mnz-muted">{{ $fiscalYear }}</p>
            </div>
        </div>
        <div class="mnz-panel__body">
            <form method="POST" action="{{ route('disclosures.s1.sustainability-risks.store', ['fiscal_year' => $fiscalYear]) }}">
                @csrf
                <input type="hidden" name="fiscal_year" value="{{ $fiscalYear }}">

                <div class="mnz-field">
                    <label class="mnz-label" for="risk_name">Risk name *</label>
                    <input class="mnz-input" id="risk_name" type="text" name="name" required
                           value="{{ old('name') }}">
                </div>

                <div class="mnz-cols mnz-cols--half" style="margin-top:14px">
                    <div class="mnz-field">
                        <label class="mnz-label" for="risk_topic">Topic *</label>
                        <select class="mnz-select" id="risk_topic" name="topic" required>
                            @foreach ($topics as $key => $meta)
                                <option value="{{ $key }}" @selected(old('topic') === $key)>{{ $meta['label'] }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mnz-field">
                        <label class="mnz-label" for="risk_horizon">Time horizon *</label>
                        <select class="mnz-select" id="risk_horizon" name="time_horizon" required>
                            @foreach (\App\Models\SustainabilityRisk::HORIZONS as $value => $label)
                                <option value="{{ $value }}" @selected(old('time_horizon') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="mnz-field" style="margin-top:14px">
                    <label class="mnz-label" for="risk_description">Description</label>
                    <textarea class="mnz-input" id="risk_description" name="description" rows="2"
                              placeholder="Description">{{ old('description') }}</textarea>
                </div>

                <div style="margin-top:16px">
                    <button type="submit" class="mnz-btn mnz-btn--primary">Add risk</button>
                </div>
            </form>
        </div>
    </div>

    <div class="mnz-panel">
        <div class="mnz-panel__head">
            <div><h3>Registered risks ({{ $risks->count() }})</h3></div>
        </div>
        <div class="mnz-panel__body">
            @forelse ($risks as $risk)
                <details class="mnz-sec">
                    <summary class="mnz-sec__btn">
                        <span class="mnz-sec__chev">›</span>
                        <span style="flex:1;min-width:0">{{ $risk->name }}</span>
                        <span class="mnz-muted">{{ $risk->topicLabel() }}</span>
                    </summary>

                    <div class="mnz-sec__panel">
                        <form method="POST"
                              action="{{ route('disclosures.s1.sustainability-risks.update', ['sustainabilityRisk' => $risk, 'fiscal_year' => $fiscalYear]) }}">
                            @csrf @method('PUT')
                            <input type="hidden" name="fiscal_year" value="{{ $fiscalYear }}">

                            <div class="mnz-field">
                                <label class="mnz-label" for="name_{{ $risk->id }}">Risk name</label>
                                <input class="mnz-input" id="name_{{ $risk->id }}" type="text" name="name"
                                       value="{{ $risk->name }}" required>
                            </div>

                            <div class="mnz-cols mnz-cols--half" style="margin-top:14px">
                                <div class="mnz-field">
                                    <label class="mnz-label" for="topic_{{ $risk->id }}">Topic</label>
                                    <select class="mnz-select" id="topic_{{ $risk->id }}" name="topic">
                                        @foreach ($topics as $key => $meta)
                                            <option value="{{ $key }}" @selected($risk->topic === $key)>{{ $meta['label'] }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="mnz-field">
                                    <label class="mnz-label" for="horizon_{{ $risk->id }}">Time horizon</label>
                                    <select class="mnz-select" id="horizon_{{ $risk->id }}" name="time_horizon">
                                        @foreach (\App\Models\SustainabilityRisk::HORIZONS as $value => $label)
                                            <option value="{{ $value }}" @selected($risk->time_horizon === $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="mnz-field" style="margin-top:14px">
                                <label class="mnz-label" for="desc_{{ $risk->id }}">Description</label>
                                <textarea class="mnz-input" id="desc_{{ $risk->id }}" name="description" rows="2">{{ $risk->description }}</textarea>
                            </div>

                            <div style="margin-top:14px">
                                <button type="submit" class="mnz-btn mnz-btn--soft">Update</button>
                            </div>
                        </form>

                        <form method="POST"
                              action="{{ route('disclosures.s1.sustainability-risks.destroy', ['sustainabilityRisk' => $risk, 'fiscal_year' => $fiscalYear]) }}"
                              onsubmit="return confirm('Remove?')" style="margin-top:10px">
                            @csrf @method('DELETE')
                            <button type="submit" class="mnz-btn mnz-btn--ghost">Delete</button>
                        </form>
                    </div>
                </details>
            @empty
                <div class="mnz-empty">
                    <div class="mnz-empty__title">No risks registered</div>
                    <div class="mnz-empty__text">No sustainability risks for {{ $fiscalYear }} yet.</div>
                </div>
            @endforelse
        </div>
    </div>

</div>
@endsection
