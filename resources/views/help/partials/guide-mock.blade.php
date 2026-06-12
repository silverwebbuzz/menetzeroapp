@php
    $variant = $variant ?? 'default';
    $theme = $theme ?? 'company';
    $isConsultant = $theme === 'consultant';
@endphp

@switch($variant)
    @case('kpi-total')
        <div class="ent-kpi-card" style="max-width: 16rem;">
            <div class="ent-kpi-card__head">
                <span class="ent-label">Total Emissions</span>
            </div>
            <div class="ent-kpi-value">124.50<span class="ent-kpi-unit">tCO₂e</span></div>
            <div class="ent-kpi-card__trend down">↓ 3.2% vs last month</div>
        </div>
        @break

    @case('kpi-scopes')
        <div class="ent-grid-3" style="grid-template-columns: repeat(3, 1fr); gap: 0.5rem;">
            @foreach(['Scope 1' => '18.2', 'Scope 2' => '92.1', 'Scope 3' => '14.2'] as $label => $val)
                <div class="ent-kpi-card" style="padding: 0.75rem;">
                    <span class="ent-label" style="font-size: 0.6875rem;">{{ $label }}</span>
                    <div class="ent-kpi-value" style="font-size: 1.25rem;">{{ $val }}<span class="ent-kpi-unit">t</span></div>
                </div>
            @endforeach
        </div>
        @break

    @case('setup-prompt')
        <div class="callout-panel callout-panel--brand" style="margin: 0;">
            <p class="callout-panel__title" style="margin-bottom: 0.25rem;">Complete your setup</p>
            <p class="callout-panel__body" style="margin: 0; font-size: 0.8125rem;">Add your first location to unlock Quick Input forms.</p>
        </div>
        @break

    @case('location-card')
        <div class="card" style="margin: 0; max-width: 22rem;">
            <div class="card-body" style="padding: 1rem;">
                <div class="flex items-center gap-2 mb-1">
                    <strong style="font-size: 0.875rem;">Head Office — Dubai</strong>
                    <span class="badge badge-success">Head Office</span>
                </div>
                <p class="text-sm text-slate-600 mb-2" style="margin: 0;">Business Bay, Dubai, UAE</p>
                <div class="flex gap-2">
                    <span class="btn btn-secondary btn-sm" style="pointer-events: none;">Edit</span>
                    <span class="btn btn-ghost btn-sm" style="pointer-events: none;">Emission boundaries →</span>
                </div>
            </div>
        </div>
        @break

    @case('location-search')
        <div class="card" style="margin: 0;">
            <div class="card-body" style="padding: 0.875rem;">
                <label class="form-label">Search</label>
                <input type="text" class="form-control" value="" placeholder="Search by name…" readonly tabindex="-1">
                <div class="flex gap-2 mt-2">
                    <span class="btn btn-primary btn-sm" style="pointer-events: none;">Apply filters</span>
                    <span class="btn btn-ghost btn-sm" style="pointer-events: none;">Clear</span>
                </div>
            </div>
        </div>
        @break

    @case('boundary-checklist')
        <div class="card" style="margin: 0; max-width: 20rem;">
            <div class="card-header" style="padding: 0.75rem 1rem;">
                <h3 class="card-title" style="font-size: 0.875rem; margin: 0;">Emission boundaries</h3>
            </div>
            <div class="card-body" style="padding: 0.75rem 1rem;">
                @foreach(['Purchased electricity (Scope 2)', 'Natural gas (Scope 1)', 'Company fleet (Scope 1)'] as $i => $item)
                    <label class="flex items-center gap-2 text-sm mb-2" style="margin-bottom: 0.5rem;">
                        <input type="checkbox" @if($i < 2) checked @endif disabled>
                        <span>{{ $item }}</span>
                    </label>
                @endforeach
            </div>
        </div>
        @break

    @case('year-location-form')
        <div class="card" style="margin: 0; max-width: 24rem;">
            <div class="card-body" style="padding: 1rem;">
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="form-label">Year <span class="text-red-500">*</span></label>
                        <select class="form-select" disabled tabindex="-1">
                            <option selected>2026</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label">Location <span class="text-red-500">*</span></label>
                        <select class="form-select" disabled tabindex="-1">
                            <option selected>Head Office — Dubai</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
        @break

    @case('action-required')
        <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900" style="max-width: 24rem;">
            <strong>Action Required</strong><br>
            Select a <strong>Year</strong> and <strong>Location</strong> above to start entering data.
        </div>
        @break

    @case('entry-row')
        <div class="table-wrap" style="max-height: none;">
            <table class="table" style="font-size: 0.8125rem;">
                <thead>
                    <tr>
                        <th>Source</th>
                        <th>Location</th>
                        <th>Year</th>
                        <th>Quantity</th>
                        <th>tCO₂e</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Electricity (DEWA)</td>
                        <td>Head Office</td>
                        <td>2026</td>
                        <td>12,400 kWh</td>
                        <td><strong>8.42</strong></td>
                    </tr>
                </tbody>
            </table>
        </div>
        @break

    @case('scope-nav')
        <div class="card" style="margin: 0; max-width: 14rem;">
            <div class="card-body" style="padding: 0.5rem;">
                <p class="text-xs font-semibold text-slate-500 uppercase px-2 py-1 mb-1">Quick Input</p>
                @foreach(['View Entries', 'Natural Gas', 'Electricity', 'Company fleet'] as $i => $item)
                    <div class="px-2 py-1.5 rounded text-sm {{ $i === 2 ? 'bg-brand-soft text-brand-darker font-medium' : 'text-slate-600' }}" style="{{ $i === 2 ? 'background: var(--brand-soft); color: var(--brand-darker);' : '' }}">
                        {{ $item }}
                    </div>
                @endforeach
            </div>
        </div>
        @break

    @case('report-filters')
        <div class="card" style="margin: 0;">
            <div class="card-body flex flex-wrap items-end gap-3" style="padding: 1rem;">
                <div>
                    <label class="form-label">Reporting year</label>
                    <select class="form-select" disabled style="min-width: 7rem;"><option selected>2026</option></select>
                </div>
                <span class="btn btn-primary btn-sm" style="pointer-events: none;">Export Excel</span>
                <span class="btn btn-secondary btn-sm" style="pointer-events: none;">Export PDF</span>
            </div>
        </div>
        @break

    @case('report-scope-table')
        <div class="table-wrap" style="max-height: none;">
            <table class="table" style="font-size: 0.8125rem;">
                <thead><tr><th>Scope</th><th>Category</th><th>tCO₂e</th></tr></thead>
                <tbody>
                    <tr><td>Scope 1</td><td>Natural gas</td><td>18.20</td></tr>
                    <tr><td>Scope 2</td><td>Purchased electricity</td><td>92.10</td></tr>
                </tbody>
            </table>
        </div>
        @break

    @case('disclosure-card')
        <div class="card" style="margin: 0; max-width: 18rem;">
            <div class="card-body" style="padding: 1rem;">
                <div class="flex justify-between items-start mb-2">
                    <strong class="text-sm">IFRS S2 — Climate</strong>
                    <span class="badge badge-neutral">62% complete</span>
                </div>
                <div class="w-full bg-slate-100 rounded-full h-2 mb-2">
                    <div class="bg-brand h-2 rounded-full" style="width: 62%; background: var(--brand);"></div>
                </div>
                <span class="btn btn-secondary btn-sm" style="pointer-events: none;">Continue section →</span>
            </div>
        </div>
        @break

    @case('disclosure-hub')
        <div class="grid grid-cols-2 gap-2" style="max-width: 20rem;">
            @foreach(['IFRS S2', 'IFRS S1', 'GRI', 'ESG'] as $fw)
                <div class="card" style="margin: 0;">
                    <div class="card-body text-center" style="padding: 0.75rem;">
                        <span class="text-sm font-semibold">{{ $fw }}</span>
                    </div>
                </div>
            @endforeach
        </div>
        @break

    @case('team-invite')
        <div class="card" style="margin: 0;">
            <div class="card-body flex items-center justify-between gap-3" style="padding: 0.875rem 1rem;">
                <div class="flex items-center gap-3">
                    <span class="avatar">SK</span>
                    <div>
                        <div class="text-sm font-medium">Sara Khan</div>
                        <div class="text-xs text-slate-500">Data entry · Measurements only</div>
                    </div>
                </div>
                <span class="btn btn-primary btn-sm" style="pointer-events: none;">+ Invite</span>
            </div>
        </div>
        @break

    @case('plan-card')
        <div class="card" style="margin: 0; max-width: 14rem; border-color: var(--brand);">
            <div class="card-body" style="padding: 1rem;">
                <div class="text-sm font-bold mb-1">Growth</div>
                <div class="text-lg font-bold mb-2" style="color: var(--brand);">AED 4,999<span class="text-xs font-normal text-slate-500">/yr</span></div>
                <ul class="portal-guide-list text-xs" style="padding-left: 1rem;">
                    <li>Scope 3 preview</li>
                    <li>PDF exports</li>
                    <li>Bulk import</li>
                </ul>
            </div>
        </div>
        @break

    @case('consultant-card')
        <div class="card" style="margin: 0; max-width: 16rem;">
            <div class="card-body" style="padding: 1rem;">
                <div class="font-medium text-sm mb-1">GreenPath Advisory</div>
                <div class="text-xs text-slate-500 mb-2">Dubai · IFRS &amp; GRI</div>
                <span class="btn btn-primary btn-sm w-full" style="pointer-events: none;">Request intro</span>
            </div>
        </div>
        @break

    {{-- Consultant portal --}}
    @case('agency-header')
        <div class="flex items-center justify-between gap-3 rounded-lg px-3 py-2 text-sm" style="max-width: 28rem; background: var(--brand-soft); border: 1px solid var(--brand-softer);">
            <span><span class="text-slate-600">Agency mode —</span> <strong>Al Noor Trading LLC</strong> <span class="text-slate-500">· PRY 2026</span></span>
            <span class="text-brand font-medium whitespace-nowrap" style="color: var(--brand-dark);">Back to Agency Hub</span>
        </div>
        @break

    @case('client-row')
        <div class="card" style="margin: 0;">
            <div class="card-body flex items-center justify-between gap-3" style="padding: 0.875rem 1rem;">
                <div>
                    <div class="text-sm font-medium">Al Noor Trading LLC</div>
                    <div class="text-xs text-slate-500">PRY 2026 · 3 locations</div>
                </div>
                <span class="btn btn-primary btn-sm" style="pointer-events: none;">Enter workspace</span>
            </div>
        </div>
        @break

    @case('slot-usage')
        <div class="ent-kpi-card" style="max-width: 14rem;">
            <span class="ent-label">Client slots</span>
            <div class="ent-kpi-value" style="font-size: 1.5rem;">2<span class="ent-kpi-unit"> / 5 used</span></div>
            <div class="text-xs text-slate-500 mt-1">Growth pack · renews 31 Dec 2026</div>
        </div>
        @break

    @case('pack-card')
        <div class="cd-pack-card" style="max-width: 12rem; margin: 0;">
            <div class="cd-pack-name">Growth Pack</div>
            <div class="cd-pack-slots">5 client slots</div>
            <div class="cd-pack-price">AED 12,500</div>
            <span class="btn btn-primary btn-sm w-full mt-2" style="pointer-events: none;">Purchase pack</span>
        </div>
        @break

    @case('directory-profile')
        <div class="card" style="margin: 0; max-width: 20rem;">
            <div class="card-body" style="padding: 1rem;">
                <label class="form-label">Practice headline</label>
                <input type="text" class="form-control mb-2" value="UAE carbon accounting &amp; IFRS reporting" readonly tabindex="-1">
                <label class="form-label">Services</label>
                <input type="text" class="form-control mb-2" value="Scope 1–3, disclosures, audits" readonly tabindex="-1">
                <span class="btn btn-primary btn-sm" style="pointer-events: none;">Submit for review</span>
            </div>
        </div>
        @break

    @case('lead-row')
        <div class="card" style="margin: 0;">
            <div class="card-body flex items-center justify-between" style="padding: 0.875rem 1rem;">
                <div>
                    <div class="text-sm font-medium">Intro request — Demo SME LLC</div>
                    <div class="text-xs text-slate-500">IFRS S2 support · 2 days ago</div>
                </div>
                <span class="badge badge-success">New</span>
            </div>
        </div>
        @break

    @default
        <div class="text-sm text-slate-500 italic">UI preview</div>
@endswitch
