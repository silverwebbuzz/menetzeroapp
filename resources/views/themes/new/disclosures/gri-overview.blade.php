{{--
    MENetZero 2.0 - GRI disclosures overview (Phase 6 body migration).

    HEAVIEST GATING OF ANY MIGRATED PAGE - 7 gate calls across FOUR exports.
    Every one is preserved exactly:

      Content Index CSV        gri_content_index           x-plan-gated-link
      Full Index (UNGC/WEF/SDG) gri_content_index          x-plan-gated-link
      Enterprise Index (80+)   gri_content_index_extended  see below
      Download PDF             gri_pdf                     x-plan-gated-link

    THE ENTERPRISE INDEX IS DOUBLE-GATED, and that is deliberate in the
    original: an outer conditional HIDES the button entirely unless the tier
    allows it, and the inner link is then passed allowed="true". Collapsing that
    into a single gate would change behaviour - a disallowed tier would see a
    locked button where today it sees nothing at all. Reproduced as-is.

    The Preview link is deliberately ungated, exactly as in the original.

    TWELVE route-map keys drive the completeness rows. A dropped key renders
    that row linking to '#'. Note gri_305 links to reports.index, not a GRI
    section editor - that is correct, GRI 305 emissions come from the inventory.

    SHARED PARTIAL: disclosures.partials.header included UNCHANGED (section 38.2).

    Controller data: $company $fiscalYear $completeness
    Composer data: $gate (PlanGateComposer), $availableYears (read by the partial)
--}}
@extends('layouts.app')

@section('title', 'GRI Disclosures - MenetZero')
@section('page-title', 'GRI Sustainability Reporting')

@push('styles')
    <style>
        .dz-bar { height: 8px; background: var(--line-2); overflow: hidden; margin-bottom: 20px; }
        .dz-bar span { display: block; height: 100%; background: var(--accent); }
        .dz-item { display: flex; align-items: center; justify-content: space-between;
            gap: 12px; padding: 11px 14px; border: 1px solid var(--line);
            color: inherit; text-decoration: none; }
        .dz-item + .dz-item { margin-top: 8px; }
        .dz-item:hover { background: var(--canvas-2); border-color: var(--accent-line);
            text-decoration: none; }
        .dz-tick { flex-shrink: 0; width: 22px; height: 22px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center; font-size: 11px; }
        .dz-tick--on { background: var(--ok-tint); color: var(--ok); font-weight: 700; }
        .dz-tick--off { background: var(--canvas-2); color: var(--ink-4); }
    </style>
@endpush

@section('content')
<div class="mnz-stack" data-pillar="s">

    @include('disclosures.partials.header', ['framework' => 'gri'])

    @if(session('success'))
        <div class="mnz-panel" style="border-color:var(--ok-line);background:var(--ok-tint)">
            <div class="mnz-panel__body" style="color:var(--ok);font-size:12.5px">{{ session('success') }}</div>
        </div>
    @endif

    <div class="mnz-panel">
        <div class="mnz-panel__head">
            <div>
                <h3 style="font-size:14px;font-weight:600;margin:0">GRI completeness</h3>
                <p style="font-size:12px;color:var(--ink-3);margin:3px 0 0">Progress for {{ $fiscalYear }} reporting cycle.</p>
            </div>
            <div style="font-size:26px;font-weight:600;letter-spacing:-.03em;color:var(--accent);line-height:1">{{ $completeness['percent'] }}%</div>
        </div>
        <div class="mnz-panel__body">
            <div class="dz-bar"><span style="width: {{ $completeness['percent'] }}%"></span></div>

            @foreach($completeness['items'] as $key => $item)
                @php
                    $routes = [
                        'material_topics' => route('disclosures.gri.material-topics', ['fiscal_year' => $fiscalYear]),
                        'material_topics_process' => route('disclosures.gri.sections.edit', ['fiscal_year' => $fiscalYear, 'section' => 'material_topics_process']),
                        'general' => route('disclosures.gri.sections.edit', ['fiscal_year' => $fiscalYear, 'section' => 'general']),
                        'energy' => route('disclosures.gri.sections.edit', ['fiscal_year' => $fiscalYear, 'section' => 'energy']),
                        'water' => route('disclosures.gri.sections.edit', ['fiscal_year' => $fiscalYear, 'section' => 'water']),
                        'waste' => route('disclosures.gri.sections.edit', ['fiscal_year' => $fiscalYear, 'section' => 'waste']),
                        'social_hr' => route('disclosures.gri.sections.edit', ['fiscal_year' => $fiscalYear, 'section' => 'social_hr']),
                        'diversity' => route('disclosures.gri.sections.edit', ['fiscal_year' => $fiscalYear, 'section' => 'diversity']),
                        'health_safety' => route('disclosures.gri.sections.edit', ['fiscal_year' => $fiscalYear, 'section' => 'health_safety']),
                        'supply_chain' => route('disclosures.gri.sections.edit', ['fiscal_year' => $fiscalYear, 'section' => 'supply_chain']),
                        'governance_metrics' => route('disclosures.gri.sections.edit', ['fiscal_year' => $fiscalYear, 'section' => 'governance_metrics']),
                        'gri_305' => route('reports.index'),
                    ];
                @endphp
                <a href="{{ $routes[$key] ?? '#' }}" class="dz-item">
                    <div style="display:flex;align-items:center;gap:11px">
                        <span class="dz-tick {{ $item['complete'] ? 'dz-tick--on' : 'dz-tick--off' }}">{{ $item['complete'] ? '✓' : '—' }}</span>
                        <div>
                            <div style="font-weight:500;font-size:12.5px">{{ $item['label'] }}</div>
                            @if(isset($item['count']))
                                <div style="font-size:11px;color:var(--ink-3);margin-top:2px">{{ $item['count'] }} topic(s)</div>
                            @endif
                        </div>
                    </div>
                    <span style="font-size:11px;color:var(--ink-4)">{{ $item['weight'] }}%</span>
                </a>
            @endforeach
        </div>
    </div>

    <div class="mnz-panel">
        <div class="mnz-panel__body" style="display:flex;gap:16px;align-items:center;justify-content:space-between;flex-wrap:wrap">
            <div style="min-width:min(100%,280px)">
                <h4 style="font-size:13px;font-weight:600;margin:0">Exports</h4>
                <p style="font-size:12.5px;color:var(--ink-3);margin:4px 0 0">GRI report PDF and content index for stakeholders.</p>
            </div>
            <div style="display:flex;flex-wrap:wrap;gap:8px">
                <a href="{{ route('disclosures.gri.report.preview', ['fiscal_year' => $fiscalYear]) }}" class="mnz-btn">Preview</a>

                <x-plan-gated-link
                    :allowed="$gate->canDisclosureExportType('gri_content_index', $fiscalYear)"
                    :href="route('disclosures.gri.content-index', ['fiscal_year' => $fiscalYear])"
                    :message="$gate->disclosureExportMessage()"
                    class="mnz-btn"
                    locked-class="mnz-btn">
                    Content Index CSV
                </x-plan-gated-link>

                <x-plan-gated-link
                    :allowed="$gate->canDisclosureExportType('gri_content_index', $fiscalYear)"
                    :href="route('disclosures.gri.content-index-full', ['fiscal_year' => $fiscalYear])"
                    :message="$gate->disclosureExportMessage()"
                    class="mnz-btn"
                    locked-class="mnz-btn">
                    Full Index (UNGC/WEF/SDG)
                </x-plan-gated-link>

                {{-- Double-gated on purpose - see the header comment. --}}
                @if($gate->canDisclosureExportType('gri_content_index_extended', $fiscalYear))
                <x-plan-gated-link
                    :allowed="true"
                    :href="route('disclosures.gri.content-index-enterprise', ['fiscal_year' => $fiscalYear])"
                    class="mnz-btn">
                    Enterprise Index (80+)
                </x-plan-gated-link>
                @endif

                <x-plan-gated-link
                    :allowed="$gate->canDisclosureExportType('gri_pdf', $fiscalYear)"
                    :href="route('disclosures.gri.report.pdf', ['fiscal_year' => $fiscalYear])"
                    :message="$gate->disclosureExportMessage()"
                    class="mnz-btn mnz-btn--primary"
                    locked-class="mnz-btn">
                    Download PDF
                </x-plan-gated-link>
            </div>
        </div>
    </div>
</div>
@endsection
