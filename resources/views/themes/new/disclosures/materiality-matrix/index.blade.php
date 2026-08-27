{{--
    MENetZero 2.0 — Double materiality matrix (Phase 5.5, Governance).

    FORM CONTRACT: a single bulk-edit form posting a nested array —
    topics[{key}][impact_materiality], [financial_materiality], [is_material].
    MaterialityMatrixController::update() reads $request->input('topics', [])
    and hands it to DisclosureService::syncMaterialityMatrix().

    The {key} in each name is the topic key from the controller's $topics
    array. Renaming or re-indexing it would silently write to the wrong topic,
    so the loop key is used verbatim.

    Controller data: $topics $levels $fiscalYear
--}}
@extends('layouts.app')

@section('title', 'Materiality Matrix')
@section('page-title', 'Materiality Assessment Matrix')

@section('content')
<div class="mnz-stack" data-pillar="g">

    {{-- Framework tab strip removed: this page is a register owned by

         its pillar, not a section of a framework. The lineage line names

         the reports that read it instead. --}}

    @include('layouts.partials.register-lineage')

    <div class="mnz-pagehead">
        <div>
            <div class="mnz-kicker">Governance · GRI 3 / IFRS S1</div>
            <h1>Double materiality</h1>
            <p class="mnz-lead">
                Impact and financial materiality for {{ $fiscalYear }}. Topics marked
                medium or high on either axis are flagged as material.
            </p>
        </div>
        <div class="mnz-pagehead__actions">
            <a href="{{ route('disclosures.s1.material-topics', ['fiscal_year' => $fiscalYear]) }}"
               class="mnz-btn mnz-btn--ghost">Material topics list</a>
        </div>
    </div>

    @if (session('success'))
        <div class="mnz-panel" style="border-color:var(--ok-line);background:var(--ok-tint)">
            <div class="mnz-panel__body" style="color:var(--ok)">{{ session('success') }}</div>
        </div>
    @endif

    <div class="mnz-panel">
        <div class="mnz-panel__body">
            <x-field-help key="esg_depth.materiality.intro" class="mb-4" />

            <form method="POST" action="{{ route('disclosures.materiality-matrix.update', ['fiscal_year' => $fiscalYear]) }}">
                @csrf

                <div class="mnz-table" style="--cols: 2fr 100px 1fr 1fr 90px">
                    <div class="mnz-table__head">
                        <span>Topic</span><span>GRI</span>
                        <span>Impact materiality</span><span>Financial materiality</span>
                        <span class="t-r">Material</span>
                    </div>

                    @foreach ($topics as $key => $topic)
                        <div class="mnz-table__row">
                            <span class="t-name">{{ $topic['label'] }}</span>
                            <span class="mnz-mono mnz-muted">{{ $topic['gri'] }}</span>

                            <span>
                                <select class="mnz-select" name="topics[{{ $key }}][impact_materiality]"
                                        aria-label="Impact materiality for {{ $topic['label'] }}">
                                    <option value="">—</option>
                                    @foreach ($levels as $val => $label)
                                        <option value="{{ $val }}" @selected($topic['impact_materiality'] === $val)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </span>

                            <span>
                                <select class="mnz-select" name="topics[{{ $key }}][financial_materiality]"
                                        aria-label="Financial materiality for {{ $topic['label'] }}">
                                    <option value="">—</option>
                                    @foreach ($levels as $val => $label)
                                        <option value="{{ $val }}" @selected($topic['financial_materiality'] === $val)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </span>

                            <span class="t-r">
                                <input type="checkbox" name="topics[{{ $key }}][is_material]" value="1"
                                       @checked($topic['is_material'])
                                       aria-label="{{ $topic['label'] }} is material">
                            </span>
                        </div>
                    @endforeach
                </div>

                <div style="margin-top:16px">
                    <button type="submit" class="mnz-btn mnz-btn--primary">Save materiality matrix</button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
