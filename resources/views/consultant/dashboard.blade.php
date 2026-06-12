@extends('consultant.layouts.app')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('content')
@php
    $hasPortfolioData = ($portfolio['total_emissions_kg'] ?? 0) > 0;
    $industryLabels = array_keys($enterprise['clients_by_industry'] ?? []);
    $industryValues = array_values($enterprise['clients_by_industry'] ?? []);
    $slotsRemaining = (int) ($slotSummary['remaining'] ?? 0);
    $pipeline = $enterprise['pipeline'] ?? ['new_leads' => 0, 'qualified' => 0, 'proposal' => 0, 'won' => 0];
    $revenue = $enterprise['revenue'] ?? ['mrr' => 0, 'arr' => 0, 'renewals_due' => 0, 'outstanding' => 0];
@endphp

@if(!empty($needsRenewal) && $renewalSubscription)
    <div class="cd-notice mb-4" style="border-color:#fde68a;background:#fffbeb;color:#92400e;">
        <span><strong>Renewal due</strong> — {{ $renewalSubscription->plan?->plan_name }} ends {{ $renewalSubscription->expires_at->format('d M Y') }}.</span>
        <a href="{{ route('consultant.renewal.index') }}" class="btn btn-warning btn-sm">Renew for {{ (int) $renewalSubscription->contract_year + 1 }}</a>
    </div>
@endif

@if(!empty($slotSummary['is_trial']) && $activeClients->isEmpty())
    <div class="cd-notice mb-4">
        <span><strong>Free trial ready.</strong> Add one managed client to capture emissions (exports unlock with a paid pack).</span>
        <a href="{{ route('consultant.clients.create') }}" class="btn btn-primary btn-sm">Add first client</a>
    </div>
@endif

<div class="ent-dashboard">
    <div class="ent-page-header flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="ent-page-title">Dashboard</h1>
            <p class="ent-page-lead">{{ $consultant->company_name }} — portfolio performance, revenue, and client pipeline.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            @if($slotsRemaining > 0)
                <a href="{{ route('consultant.clients.create') }}" class="btn btn-primary btn-sm">+ Add client</a>
            @endif
            <a href="{{ route('consultant.workspace.switcher') }}" class="btn btn-secondary btn-sm">Workspaces</a>
            <a href="{{ route('consultant.intro-requests.index') }}" class="btn btn-secondary btn-sm">Leads</a>
        </div>
    </div>

    {{-- Top KPIs --}}
    <div class="ent-grid-6">
        <div class="ent-kpi-card">
            <span class="ent-label">Managed clients</span>
            <div class="ent-kpi-value">{{ $activeClients->count() }}</div>
            <div class="ent-kpi-card__compare">{{ $portfolio['clients_with_data'] ?? 0 }} with emissions data</div>
        </div>
        <div class="ent-kpi-card">
            <span class="ent-label">Portfolio emissions</span>
            <div class="ent-kpi-value">{{ co2e_t($portfolio['total_emissions_kg'] ?? 0) }}<span class="ent-kpi-unit">tCO₂e</span></div>
            <div class="ent-kpi-card__compare">{{ $portfolio['locations_count'] ?? 0 }} locations</div>
        </div>
        <div class="ent-kpi-card">
            <span class="ent-label">Pending reviews</span>
            <div class="ent-kpi-value">{{ $enterprise['pending_reviews'] ?? 0 }}</div>
            <div class="ent-kpi-card__compare">Draft & submitted entries</div>
        </div>
        <div class="ent-kpi-card">
            <span class="ent-label">New leads</span>
            <div class="ent-kpi-value">{{ $pipeline['new_leads'] }}</div>
            <div class="ent-kpi-card__compare"><a href="{{ route('consultant.intro-requests.index') }}" class="text-brand hover:underline">View inbox →</a></div>
        </div>
        <div class="ent-kpi-card">
            <span class="ent-label">Monthly revenue</span>
            <div class="ent-kpi-value">{{ number_format($enterprise['monthly_revenue'] ?? 0, 0) }}<span class="ent-kpi-unit">AED</span></div>
            <div class="ent-kpi-card__compare">Consultant payout (est.)</div>
        </div>
        <div class="ent-kpi-card">
            <span class="ent-label">Client slots</span>
            <div class="ent-kpi-value">{{ $slotSummary['used'] ?? 0 }}<span class="ent-kpi-unit">/ {{ $slotSummary['limit'] ?? 0 }}</span></div>
            <div class="ent-kpi-card__compare">{{ $slotsRemaining }} remaining</div>
        </div>
    </div>

    {{-- Portfolio overview --}}
    <div>
        <h3 class="ent-card-title mb-3">Portfolio overview</h3>
        <div class="ent-chart-row">
            <div class="card">
                <div class="card-header">
                    <h3 class="ent-card-title">Clients by industry</h3>
                </div>
                <div class="card-body">
                    <div style="height:11rem;">
                        <canvas id="industryChart"></canvas>
                    </div>
                </div>
            </div>
            <div class="card">
                <div class="card-header">
                    <h3 class="ent-card-title">Portfolio emissions</h3>
                </div>
                <div class="card-body">
                    <div style="height:11rem;">
                        <canvas id="portfolioEmissionsChart"></canvas>
                    </div>
                </div>
            </div>
            <div class="card">
                <div class="card-header">
                    <h3 class="ent-card-title">Monthly client growth</h3>
                </div>
                <div class="card-body">
                    <div style="height:11rem;">
                        <canvas id="clientGrowthChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Revenue --}}
    <div>
        <h3 class="ent-card-title mb-3">Revenue</h3>
        <div class="ent-grid-4">
            <div class="ent-kpi-card">
                <span class="ent-label">MRR</span>
                <div class="ent-kpi-value">{{ number_format($revenue['mrr'], 0) }}<span class="ent-kpi-unit">AED</span></div>
            </div>
            <div class="ent-kpi-card">
                <span class="ent-label">ARR</span>
                <div class="ent-kpi-value">{{ number_format($revenue['arr'], 0) }}<span class="ent-kpi-unit">AED</span></div>
            </div>
            <div class="ent-kpi-card">
                <span class="ent-label">Renewals due</span>
                <div class="ent-kpi-value">{{ $revenue['renewals_due'] }}</div>
                @if(!empty($needsRenewal))
                    <div class="ent-kpi-card__compare"><a href="{{ route('consultant.renewal.index') }}" class="text-brand hover:underline">Renew now →</a></div>
                @endif
            </div>
            <div class="ent-kpi-card">
                <span class="ent-label">Outstanding payments</span>
                <div class="ent-kpi-value">{{ number_format($revenue['outstanding'], 0) }}<span class="ent-kpi-unit">AED</span></div>
            </div>
        </div>
    </div>

    <div class="ent-grid-2">
        {{-- Activity feed --}}
        <div class="card">
            <div class="card-header">
                <div>
                    <h3 class="ent-card-title">Client activity</h3>
                    <p class="ent-card-subtitle">Recent submissions, leads, and data entries</p>
                </div>
            </div>
            <div class="card-body">
                @forelse($enterprise['activity'] ?? [] as $item)
                    <div class="ent-activity-item">
                        <div class="flex-1 min-w-0">
                            <div class="font-medium text-slate-900">{{ $item['text'] }}</div>
                            <div class="ent-card-subtitle">{{ $item['meta'] }} · {{ $item['at']?->diffForHumans() }}</div>
                        </div>
                    </div>
                @empty
                    <p class="ent-card-subtitle">Activity from clients and leads will appear here.</p>
                @endforelse
            </div>
        </div>

        {{-- Lead pipeline + directory --}}
        <div class="space-y-4">
            <div class="card">
                <div class="card-header">
                    <div>
                        <h3 class="ent-card-title">Lead pipeline</h3>
                        <p class="ent-card-subtitle">Introduction requests through MenetZero</p>
                    </div>
                    <a href="{{ route('consultant.intro-requests.index') }}" class="btn btn-ghost btn-sm">All leads →</a>
                </div>
                <div class="card-body">
                    <div class="ent-pipeline">
                        <div class="ent-pipeline-step">
                            <strong>{{ $pipeline['new_leads'] }}</strong>
                            <span>New leads</span>
                        </div>
                        <div class="ent-pipeline-step">
                            <strong>{{ $pipeline['qualified'] }}</strong>
                            <span>Qualified</span>
                        </div>
                        <div class="ent-pipeline-step">
                            <strong>{{ $pipeline['proposal'] }}</strong>
                            <span>Proposal sent</span>
                        </div>
                        <div class="ent-pipeline-step">
                            <strong>{{ $pipeline['won'] }}</strong>
                            <span>Won</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <div>
                        <h3 class="ent-card-title">Directory listing</h3>
                        <p class="ent-card-subtitle">{{ $directoryProgress['percent'] }}% complete</p>
                    </div>
                </div>
                <div class="card-body">
                    <div class="ent-progress-track mb-3">
                        <div class="ent-progress-fill" style="width:{{ $directoryProgress['percent'] }}%;"></div>
                    </div>
                    @foreach($directoryProgress['steps'] as $i => $step)
                        <div class="cd-step">
                            <span class="cd-step-dot {{ $step['done'] ? 'done' : 'pending' }}">
                                @if($step['done'])✓@else{{ $i + 1 }}@endif
                            </span>
                            <div class="flex-1 min-w-0">
                                @if($step['url'] && !$step['done'])
                                    <a href="{{ $step['url'] }}" class="text-sm font-medium text-brand hover:underline">{{ $step['label'] }}</a>
                                @else
                                    <span class="text-sm {{ $step['done'] ? 'text-slate-500 line-through' : 'font-medium text-slate-800' }}">{{ $step['label'] }}</span>
                                @endif
                            </div>
                        </div>
                    @endforeach
                    @if($directoryProgress['ready_to_submit'])
                        <form action="{{ route('consultant.profile.submit') }}" method="POST" class="mt-3">
                            @csrf
                            <button type="submit" class="btn btn-primary btn-sm w-full">Submit for review</button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @if($activeClients->isNotEmpty())
        <div class="card">
            <div class="card-header">
                <div>
                    <h3 class="ent-card-title">Active clients</h3>
                    <p class="ent-card-subtitle">Open a workspace to enter data or run reports</p>
                </div>
                <a href="{{ route('consultant.clients.index') }}" class="btn btn-ghost btn-sm">All clients →</a>
            </div>
            <div class="card-body p-0">
                <div class="table-wrap">
                    <table class="table w-full">
                        <thead>
                            <tr>
                                <th>Client</th>
                                <th>Sector</th>
                                <th>PRY</th>
                                <th>Locations</th>
                                <th>Emissions</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($portfolio['clients'] as $client)
                                <tr>
                                    <td>
                                        <div class="font-medium text-slate-900">{{ $client['name'] }}</div>
                                        @if($client['is_trial'])
                                            <span class="text-xs text-blue-600">Trial</span>
                                        @endif
                                    </td>
                                    <td>{{ $client['sector'] ?? '—' }}</td>
                                    <td>{{ $client['pry'] }}</td>
                                    <td>{{ $client['locations'] }}</td>
                                    <td class="font-medium">
                                        @if($client['emissions_kg'] > 0)
                                            {{ co2e_t($client['emissions_kg']) }} tCO₂e
                                        @else
                                            <span class="text-slate-400">No data</span>
                                        @endif
                                    </td>
                                    <td class="text-right">
                                        <form action="{{ route('consultant.workspace.enter', $client['id']) }}" method="POST" class="inline">
                                            @csrf
                                            <button type="submit" class="btn btn-ghost btn-sm">Open →</button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif
</div>
@endsection

@push('head')
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const blues = ['#2563eb', '#3b82f6', '#60a5fa', '#93c5fd', '#bfdbfe'];
    const chartDefaults = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
    };

    const industryCtx = document.getElementById('industryChart');
    if (industryCtx) {
        new Chart(industryCtx, {
            type: 'bar',
            data: {
                labels: @json($industryLabels ?: ['No clients']),
                datasets: [{
                    data: @json($industryValues ?: [0]),
                    backgroundColor: blues[0],
                    borderRadius: 6,
                }],
            },
            options: {
                ...chartDefaults,
                scales: {
                    y: { beginAtZero: true, ticks: { stepSize: 1 } },
                    x: { grid: { display: false } },
                },
            },
        });
    }

    const emissionsCtx = document.getElementById('portfolioEmissionsChart');
    if (emissionsCtx) {
        new Chart(emissionsCtx, {
            type: 'doughnut',
            data: {
                labels: @json(array_keys($portfolio['scope_breakdown'] ?? [])),
                datasets: [{
                    data: @json(array_values($portfolio['scope_breakdown'] ?? [])),
                    backgroundColor: blues.slice(0, 3),
                    borderWidth: 0,
                }],
            },
            options: {
                ...chartDefaults,
                cutout: '65%',
                plugins: {
                    legend: {
                        display: true,
                        position: 'bottom',
                        labels: { boxWidth: 10, font: { size: 11 } },
                    },
                },
            },
        });
    }

    const growthCtx = document.getElementById('clientGrowthChart');
    if (growthCtx) {
        new Chart(growthCtx, {
            type: 'line',
            data: {
                labels: @json($enterprise['client_growth']['labels'] ?? []),
                datasets: [{
                    data: @json($enterprise['client_growth']['values'] ?? []),
                    borderColor: blues[0],
                    backgroundColor: 'rgba(37, 99, 235, 0.08)',
                    fill: true,
                    tension: 0.35,
                    pointRadius: 2,
                }],
            },
            options: {
                ...chartDefaults,
                scales: {
                    y: { beginAtZero: true, ticks: { stepSize: 1 } },
                    x: { grid: { display: false } },
                },
            },
        });
    }
});
</script>
@endpush
