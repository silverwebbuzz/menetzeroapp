{{--
    MENetZero 2.0 — Stakeholder register (Phase 5.4, Social).

    FORM CONTRACT verified against StakeholderEngagementController::
    validateEngagement(): stakeholder_group (required), engagement_method,
    frequency (enum), topics_discussed, outcomes, last_engaged_at.

    Both the store and destroy routes carry ['fiscal_year' => $fiscalYear] —
    without it the controller falls back to the session year and the record
    lands in the wrong reporting year.

    Controller data: $engagements $fiscalYear
--}}
@extends('layouts.app')

@section('title', 'Stakeholder Register')
@section('page-title', 'Stakeholder Engagement')

@section('content')
<div class="mnz-stack" data-pillar="s">

    {{-- Framework tab strip removed: this page is a register owned by

         its pillar, not a section of a framework. The lineage line names

         the reports that read it instead. --}}

    @include('layouts.partials.register-lineage')

    <div class="mnz-pagehead">
        <div>
            <div class="mnz-kicker">Social · GRI 2-29</div>
            <h1>Stakeholder engagement</h1>
            <p class="mnz-lead">Who you engage, how often, and what came of it — for {{ $fiscalYear }}.</p>
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
                <h3>Add stakeholder engagement</h3>
                <p class="mnz-muted">GRI 2-29 — {{ $fiscalYear }}</p>
            </div>
        </div>
        <div class="mnz-panel__body">
            <x-field-help key="esg_depth.stakeholders.intro" class="mb-4" />

            <form method="POST" action="{{ route('disclosures.stakeholders.store', ['fiscal_year' => $fiscalYear]) }}">
                @csrf

                <div class="mnz-cols mnz-cols--half">
                    <div class="mnz-field">
                        <label class="mnz-label" for="stakeholder_group">Stakeholder group</label>
                        <input class="mnz-input" id="stakeholder_group" type="text" name="stakeholder_group" required
                               value="{{ old('stakeholder_group') }}"
                               placeholder="e.g. Employees, Investors, Regulators">
                        <x-field-help key="esg_depth.stakeholders.stakeholder_group" class="mt-1" />
                    </div>

                    <div class="mnz-field">
                        <label class="mnz-label" for="engagement_method">Engagement method</label>
                        <input class="mnz-input" id="engagement_method" type="text" name="engagement_method"
                               value="{{ old('engagement_method') }}"
                               placeholder="e.g. Survey, workshops, meetings">
                        <x-field-help key="esg_depth.stakeholders.engagement_method" class="mt-1" />
                    </div>
                </div>

                <div class="mnz-cols mnz-cols--half" style="margin-top:14px">
                    <div class="mnz-field">
                        <label class="mnz-label" for="frequency">Frequency</label>
                        <select class="mnz-select" id="frequency" name="frequency">
                            <option value="">— not set —</option>
                            @foreach (\App\Models\StakeholderEngagement::FREQUENCIES as $value => $label)
                                <option value="{{ $value }}" @selected(old('frequency') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <x-field-help key="esg_depth.stakeholders.frequency" class="mt-1" />
                    </div>

                    <div class="mnz-field">
                        <label class="mnz-label" for="last_engaged_at">Last engaged</label>
                        <input class="mnz-input" id="last_engaged_at" type="date" name="last_engaged_at"
                               value="{{ old('last_engaged_at') }}">
                        <x-field-help key="esg_depth.stakeholders.last_engaged_at" class="mt-1" />
                    </div>
                </div>

                <div class="mnz-cols mnz-cols--half" style="margin-top:14px">
                    <div class="mnz-field">
                        <label class="mnz-label" for="topics_discussed">Topics discussed</label>
                        <textarea class="mnz-input" id="topics_discussed" name="topics_discussed" rows="2">{{ old('topics_discussed') }}</textarea>
                        <x-field-help key="esg_depth.stakeholders.topics_discussed" class="mt-1" />
                    </div>

                    <div class="mnz-field">
                        <label class="mnz-label" for="outcomes">Outcomes</label>
                        <textarea class="mnz-input" id="outcomes" name="outcomes" rows="2">{{ old('outcomes') }}</textarea>
                        <x-field-help key="esg_depth.stakeholders.outcomes" class="mt-1" />
                    </div>
                </div>

                <div style="margin-top:16px">
                    <button type="submit" class="mnz-btn mnz-btn--primary">Add engagement</button>
                </div>
            </form>
        </div>
    </div>

    <div class="mnz-panel">
        <div class="mnz-panel__head">
            <div><h3>Register ({{ $engagements->count() }})</h3></div>
        </div>

        @if ($engagements->isEmpty())
            <div class="mnz-panel__body">
                <div class="mnz-empty">
                    <div class="mnz-empty__title">Nothing recorded yet</div>
                    <div class="mnz-empty__text">No stakeholder engagements for {{ $fiscalYear }}.</div>
                </div>
            </div>
        @else
            <div class="mnz-table" style="--cols: 2fr 1fr 1fr 1fr 96px">
                <div class="mnz-table__head">
                    <span>Group</span><span>Method</span><span>Frequency</span>
                    <span>Last engaged</span><span class="t-r">Action</span>
                </div>
                @foreach ($engagements as $eng)
                    <div class="mnz-table__row">
                        <span>
                            <span class="t-name">{{ $eng->stakeholder_group }}</span>
                            @if ($eng->topics_discussed)
                                <span class="mnz-muted" style="display:block">{{ Str::limit($eng->topics_discussed, 80) }}</span>
                            @endif
                        </span>
                        <span>{{ $eng->engagement_method ?: '—' }}</span>
                        <span>{{ $eng->frequency ? (\App\Models\StakeholderEngagement::FREQUENCIES[$eng->frequency] ?? $eng->frequency) : '—' }}</span>
                        <span>{{ $eng->last_engaged_at?->format('d M Y') ?? '—' }}</span>
                        <span class="t-r">
                            <form method="POST"
                                  action="{{ route('disclosures.stakeholders.destroy', ['stakeholderEngagement' => $eng, 'fiscal_year' => $fiscalYear]) }}"
                                  onsubmit="return confirm('Remove this entry?')">
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
