{{--
    MENetZero 2.0 — Company reporting settings (Phase 5.7).

    Overrides settings/reporting.blade.php under the new theme.

    FORM CONTRACT preserved exactly — verified field by field against
    CompanyReportingSettingsController::update()'s 15 validation rules.
    Every name, type, step, min, max, maxlength and old() binding matches
    the view this replaces. A dropped attribute here is a validation
    failure or silent data loss, so nothing was "tidied".

    Controller data: $settings $fiscalYear $boundaries $scope3Categories
--}}
@extends('layouts.app')

@section('title', 'Company Reporting Settings — MENetZero')

@section('content')
<div class="mnz-stack" data-pillar="e">

    {{-- Base-year nudge; see the old-theme twin for the reasoning. --}}
    @if(($entryCount ?? 0) > 0 && blank($settings->base_year ?? null))
        <div class="mnz-panel" style="border-color:var(--warn-line,#e6d5a8);background:var(--warn-tint,#fdf8ec)">
            <div class="mnz-panel__body">
                <div style="font-weight:600;font-size:13px">Set a base year to track reductions</div>
                <p class="mnz-muted" style="margin:6px 0 0">
                    You have {{ number_format($entryCount) }}
                    {{ $entryCount === 1 ? 'entry' : 'entries' }}
                    @if(!empty($earliestEntry))
                        going back to {{ \Illuminate\Support\Carbon::parse($earliestEntry)->format('F Y') }}
                    @endif
                    . Reduction targets are measured against a base year, so nothing can be
                    reported as an increase or decrease until one is set.
                </p>
            </div>
        </div>
    @endif


    <div class="mnz-pagehead">
        <div>
            <div class="mnz-kicker">Settings</div>
            <h1>Reporting methodology</h1>
            <p class="mnz-lead">
                Organisational boundary, base year and Scope 3 coverage. These choices
                are disclosed alongside your inventory, so regulators can see how the
                numbers were produced.
            </p>
        </div>
    </div>

    @if ($errors->any())
        <div class="mnz-panel" style="border-color:var(--bad-line);background:var(--bad-tint)">
            <div class="mnz-panel__body" style="color:var(--bad)">
                <strong>Please correct the following</strong>
                <ul style="margin:8px 0 0;padding-left:18px;font-size:12.5px">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    <form method="POST" action="{{ route('settings.reporting.update') }}">
        @csrf

        {{-- Boundary & year --}}
        <div class="mnz-panel">
            <div class="mnz-panel__head">
                <div>
                    <h3>Boundary &amp; reporting year</h3>
                    <p class="mnz-muted">How the organisation is defined for this inventory</p>
                </div>
            </div>
            <div class="mnz-panel__body">
                <div class="mnz-cols mnz-cols--half">
                    <div class="mnz-field">
                        <label class="mnz-label" for="fiscal_year">Reporting year</label>
                        <input class="mnz-input" id="fiscal_year" type="number" name="fiscal_year"
                               value="{{ old('fiscal_year', $fiscalYear) }}"
                               min="2000" max="2100" required>
                    </div>

                    <div class="mnz-field">
                        <label class="mnz-label" for="base_year">Base year</label>
                        <input class="mnz-input" id="base_year" type="number" name="base_year"
                               value="{{ old('base_year', $settings->base_year) }}"
                               min="1990" max="2100">
                        <span class="mnz-help">The year reductions are measured against.</span>
                    </div>
                </div>

                <div class="mnz-cols mnz-cols--half" style="margin-top:14px">
                    <div class="mnz-field">
                        <label class="mnz-label" for="organisational_boundary">Organisational boundary</label>
                        <select class="mnz-select" id="organisational_boundary" name="organisational_boundary" required>
                            @foreach ($boundaries as $value => $label)
                                <option value="{{ $value }}"
                                    @selected(old('organisational_boundary', $settings->organisational_boundary) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mnz-field">
                        <label class="mnz-label" for="consolidation_approach">Consolidation approach</label>
                        <select class="mnz-select" id="consolidation_approach" name="consolidation_approach" required>
                            @foreach ($boundaries as $value => $label)
                                <option value="{{ $value }}"
                                    @selected(old('consolidation_approach', $settings->consolidation_approach) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="mnz-field" style="margin-top:14px">
                    <label class="mnz-label" for="gwp_version">GWP version</label>
                    <select class="mnz-select" id="gwp_version" name="gwp_version" required>
                        @foreach (['AR4', 'AR5', 'AR6'] as $gwp)
                            <option value="{{ $gwp }}"
                                @selected(old('gwp_version', $settings->gwp_version) === $gwp)>IPCC {{ $gwp }}</option>
                        @endforeach
                    </select>
                    <span class="mnz-help">Global warming potentials used to convert gases to CO₂e.</span>
                </div>
            </div>
        </div>

        {{-- Base year policy --}}
        <div class="mnz-panel">
            <div class="mnz-panel__head">
                <div>
                    <h3>Base year policy</h3>
                    <p class="mnz-muted">When and why the base year is recalculated</p>
                </div>
            </div>
            <div class="mnz-panel__body">
                <div class="mnz-field">
                    <label class="mnz-label" for="base_year_rationale">Base year rationale</label>
                    <textarea class="mnz-input" id="base_year_rationale" name="base_year_rationale" rows="3">{{ old('base_year_rationale', $settings->base_year_rationale) }}</textarea>
                </div>

                <div class="mnz-field" style="margin-top:14px">
                    <label class="mnz-label" for="recalculation_policy">Recalculation policy</label>
                    <textarea class="mnz-input" id="recalculation_policy" name="recalculation_policy" rows="3">{{ old('recalculation_policy', $settings->recalculation_policy) }}</textarea>
                </div>

                <div class="mnz-field" style="margin-top:14px;max-width:280px">
                    <label class="mnz-label" for="recalculation_threshold_percent">Significance threshold (%)</label>
                    <input class="mnz-input" id="recalculation_threshold_percent" type="number"
                           name="recalculation_threshold_percent" step="0.01" min="0" max="100"
                           value="{{ old('recalculation_threshold_percent', $settings->recalculation_threshold_percent) }}">
                    <span class="mnz-help">Structural change above this triggers recalculation.</span>
                </div>
            </div>
        </div>

        {{-- Intensity denominator --}}
        <div class="mnz-panel">
            <div class="mnz-panel__head">
                <div>
                    <h3>Emissions intensity denominator</h3>
                    <p class="mnz-muted">
                        Absolute emissions rise as the business grows. Intensity shows performance
                        adjusted for that growth — regulators expect both. Recorded per reporting year.
                    </p>
                </div>
            </div>
            <div class="mnz-panel__body">
                <div class="mnz-cols" style="grid-template-columns:repeat(3,minmax(0,1fr));gap:14px">
                    <div class="mnz-field">
                        <label class="mnz-label" for="intensity_denominator_type">Denominator</label>
                        <select class="mnz-select" id="intensity_denominator_type" name="intensity_denominator_type">
                            <option value="">— none —</option>
                            @foreach (\App\Models\CompanyReportingSetting::INTENSITY_DENOMINATORS as $denomKey => $denomMeta)
                                <option value="{{ $denomKey }}"
                                    @selected(old('intensity_denominator_type', $settings->intensity_denominator_type) === $denomKey)>
                                    {{ $denomMeta['label'] }} ({{ $denomMeta['unit'] }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mnz-field">
                        <label class="mnz-label" for="intensity_denominator_value">Value for this year</label>
                        <input class="mnz-input" id="intensity_denominator_value" type="number"
                               name="intensity_denominator_value" step="0.0001" min="0"
                               value="{{ old('intensity_denominator_value', $settings->intensity_denominator_value) }}">
                    </div>

                    <div class="mnz-field">
                        <label class="mnz-label" for="intensity_denominator_unit">Unit label</label>
                        <input class="mnz-input" id="intensity_denominator_unit" type="text"
                               name="intensity_denominator_unit" maxlength="40"
                               value="{{ old('intensity_denominator_unit', $settings->intensity_denominator_unit) }}"
                               placeholder="e.g. AED million">
                    </div>
                </div>
            </div>
        </div>

        {{-- Scope 3 coverage --}}
        <div class="mnz-panel">
            <div class="mnz-panel__head">
                <div>
                    <h3>Scope 3 category coverage</h3>
                    <p class="mnz-muted">
                        GHG Protocol / IFRS S2. Tick categories you measure and report;
                        unticked categories need a brief reason for exclusion.
                    </p>
                </div>
            </div>
            <div class="mnz-panel__body">
                @php
                    $policyByCat = collect($settings->scope3_category_policy ?? [])->keyBy('category');
                @endphp

                <div class="mnz-stack">
                    @foreach ($scope3Categories as $cat => $label)
                        @php
                            $row = $policyByCat->get($cat, ['included' => false, 'reason' => '']);
                            $included = old('scope3_included')
                                ? in_array($cat, old('scope3_included', []))
                                : ($row['included'] ?? false);
                        @endphp
                        <div class="mnz-panel mnz-panel--dashed">
                            <div class="mnz-panel__body" style="display:flex;flex-wrap:wrap;gap:12px;align-items:center">
                                <label style="display:flex;align-items:flex-start;gap:9px;flex:1;min-width:min(100%,260px)">
                                    <input type="checkbox" name="scope3_included[]" value="{{ $cat }}"
                                           @checked($included) style="margin-top:3px">
                                    <span>{{ $label }}</span>
                                </label>
                                <input class="mnz-input" type="text" name="scope3_reason[{{ $cat }}]"
                                       value="{{ old('scope3_reason.'.$cat, $row['reason'] ?? '') }}"
                                       placeholder="Reason if excluded"
                                       style="flex:1;min-width:min(100%,240px)"
                                       aria-label="Exclusion reason for {{ $label }}">
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
            <div class="mnz-panel__foot">
                <button type="submit" class="mnz-btn mnz-btn--primary">Save settings</button>
            </div>
        </div>

    </form>
</div>
@endsection
