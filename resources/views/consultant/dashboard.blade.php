@extends('consultant.layouts.app')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('content')
@php
    $statusColors = [
        'draft' => 'badge badge-neutral',
        'pending_review' => 'badge badge-warning',
        'approved' => 'badge badge-success',
        'rejected' => 'badge badge-danger',
        'suspended' => 'badge badge-danger',
    ];
    $hasPortfolioData = ($portfolio['total_emissions_kg'] ?? 0) > 0;
    $scopeChartData = array_values($portfolio['scope_breakdown'] ?? []);
    $scopeChartLabels = array_keys($portfolio['scope_breakdown'] ?? []);
    $slotsRemaining = (int) ($slotSummary['remaining'] ?? 0);
@endphp

@if(!empty($needsRenewal) && $renewalSubscription)
    <div class="cd-notice mb-4" style="border-color:#fde68a;background:#fffbeb;color:#92400e;">
        <span><strong>Renewal due</strong> — {{ $renewalSubscription->plan?->plan_name }} ends {{ $renewalSubscription->expires_at->format('d M Y') }}.</span>
        <a href="{{ route('consultant.renewal.index') }}" class="btn btn-warning btn-sm">Renew for {{ (int) $renewalSubscription->contract_year + 1 }}</a>
    </div>
@endif

<div class="cd-page-head">
    <div>
        <div class="cd-eyebrow">
            @if(!empty($slotSummary['is_trial']))
                Free trial · 1 client slot
            @elseif($subscription)
                {{ $subscription->plan?->plan_name ?? 'Agency pack' }} · {{ $subscription->contract_year }}
            @else
                Consultant account
            @endif
        </div>
        <h2>Welcome back, {{ $consultant->name }}</h2>
        <p class="cd-subtitle">{{ $consultant->company_name }}</p>
    </div>
    <div class="cd-page-actions">
        @if($slotsRemaining > 0)
            <a href="{{ route('consultant.clients.create') }}" class="btn btn-primary btn-sm">+ Add client</a>
        @endif
        <a href="{{ route('consultant.workspace.switcher') }}" class="btn btn-secondary btn-sm">Workspaces</a>
        <a href="{{ route('consultant.packs.index') }}" class="btn btn-ghost btn-sm">Agency packs</a>
    </div>
</div>

@if(!empty($slotSummary['is_trial']) && $activeClients->isEmpty())
    <div class="cd-notice">
        <span><strong>Free trial ready.</strong> Add one managed client to capture emissions (exports unlock with a paid pack).</span>
        <a href="{{ route('consultant.clients.create') }}" class="btn btn-primary btn-sm">Add first client</a>
    </div>
@endif

<div class="cd-stats">
    <div class="cd-stat">
        <div class="cd-stat-label">Portfolio emissions</div>
        <div class="cd-stat-value">{{ co2e_t($portfolio['total_emissions_kg'] ?? 0) }} <span>tCO₂e</span></div>
        <div class="cd-stat-meta">{{ $portfolio['clients_with_data'] ?? 0 }} of {{ $activeClients->count() }} clients with data</div>
    </div>
    <div class="cd-stat">
        <div class="cd-stat-label">Active clients</div>
        <div class="cd-stat-value">{{ $activeClients->count() }}</div>
        <div class="cd-stat-meta">{{ $portfolio['locations_count'] ?? 0 }} locations</div>
    </div>
    <div class="cd-stat">
        <div class="cd-stat-label">Client slots</div>
        <div class="cd-stat-value">{{ $slotSummary['used'] ?? 0 }}<span> / {{ $slotSummary['limit'] ?? 0 }}</span></div>
        <div class="cd-stat-meta">{{ $slotsRemaining }} {{ $slotsRemaining === 1 ? 'slot' : 'slots' }} left</div>
    </div>
    <div class="cd-stat">
        <div class="cd-stat-label">New leads</div>
        <div class="cd-stat-value">{{ $introCount }}</div>
        <div class="cd-stat-meta">
            <span class="{{ $statusColors[$consultant->status] ?? 'badge badge-neutral' }}">{{ $consultant->statusLabel() }}</span>
        </div>
    </div>
</div>

<div class="cd-actions">
    @foreach($quickActions as $action)
        <a href="{{ $action['route'] }}" class="cd-action {{ $action['primary'] ? 'primary' : '' }}">
            @if($action['icon'] === 'plus')
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            @elseif($action['icon'] === 'switch')
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
            @elseif($action['icon'] === 'card')
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
            @elseif($action['icon'] === 'refresh')
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
            @else
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/></svg>
            @endif
            {{ $action['label'] }}
        </a>
    @endforeach
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
    <div class="card lg:col-span-2">
        <div class="card-header">
            <div>
                <h3 class="card-title">Client portfolio</h3>
                <p class="card-subtitle">Emissions across managed workspaces</p>
            </div>
            <a href="{{ route('consultant.clients.index') }}" class="btn btn-ghost btn-sm">All clients →</a>
        </div>
        <div class="card-body">
            @if($hasPortfolioData)
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 items-center">
                    <div style="height:11rem;max-width:12rem;margin:0 auto;">
                        <canvas id="portfolioScopeChart"></canvas>
                    </div>
                    <div class="space-y-1">
                        @foreach($portfolio['scope_breakdown'] as $scope => $kg)
                            @if($kg > 0)
                                <div class="flex items-center justify-between text-sm py-1.5 border-b border-slate-100 last:border-0">
                                    <span class="text-slate-600">{{ $scope }}</span>
                                    <span class="font-semibold text-slate-900">{{ co2e_t($kg) }} tCO₂e</span>
                                </div>
                            @endif
                        @endforeach
                    </div>
                </div>
            @else
                <div class="cd-empty">
                    <div class="cd-empty-icon">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-5.13a4 4 0 11-8 0 4 4 0 018 0zm6 3a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                    </div>
                    <h4 class="font-semibold text-slate-900 mb-1">No client data yet</h4>
                    <p class="text-sm text-slate-500 mb-0 max-w-xs mx-auto">
                        Add a managed client and enter emissions to see portfolio charts here.
                    </p>
                </div>
            @endif
        </div>
    </div>

    <div class="space-y-4">
        <div class="card">
            <div class="card-header">
                <div>
                    <h3 class="card-title">Slot usage</h3>
                    <p class="card-subtitle">
                        @if(!empty($slotSummary['is_trial']))
                            Free trial
                        @else
                            {{ $subscription?->plan?->plan_name ?? 'No pack' }}
                        @endif
                    </p>
                </div>
            </div>
            <div class="card-body flex items-center gap-4">
                <div class="cd-ring" style="--pct: {{ $slotUsedPercent }};">
                    <div class="cd-ring-inner">
                        <strong>{{ $slotSummary['used'] ?? 0 }}/{{ $slotSummary['limit'] ?? 0 }}</strong>
                        slots
                    </div>
                </div>
                <div class="text-sm space-y-1 min-w-0">
                    <p class="font-medium text-slate-900">
                        {{ $slotsRemaining }} {{ $slotsRemaining === 1 ? 'slot' : 'slots' }} available
                    </p>
                    @if(!empty($slotSummary['is_trial']))
                        <p class="text-slate-500 text-xs">Trial: data entry only</p>
                    @endif
                    <a href="{{ route('consultant.packs.index') }}" class="text-brand text-xs font-semibold hover:underline">View packs →</a>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <div>
                    <h3 class="card-title">Directory</h3>
                    <p class="card-subtitle">{{ $directoryProgress['percent'] }}% complete</p>
                </div>
            </div>
            <div class="card-body">
                <div class="cd-progress-track mb-3">
                    <div class="cd-progress-fill" style="width:{{ $directoryProgress['percent'] }}%;"></div>
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
                @if(!empty($missingDocs))
                    <p class="text-xs text-amber-700 mt-2 pt-2 border-t border-slate-100">
                        Missing: {{ implode(', ', array_map(fn($t) => \App\Data\ConsultantOptions::labelFor('document', $t), $missingDocs)) }}
                    </p>
                @endif
                @if($directoryProgress['ready_to_submit'])
                    <form action="{{ route('consultant.profile.submit') }}" method="POST" class="mt-3">
                        @csrf
                        <button type="submit" class="btn btn-primary btn-sm w-full">Submit for review</button>
                    </form>
                @elseif($consultant->status === 'approved')
                    <p class="text-xs text-green-700 mt-2 pt-2 border-t border-slate-100">✓ Listed in consultant directory</p>
                @elseif($consultant->status === 'pending_review')
                    <p class="text-xs text-amber-700 mt-2 pt-2 border-t border-slate-100">Application under review</p>
                @endif
            </div>
        </div>
    </div>
</div>

@if($activeClients->isNotEmpty())
    <div class="card mt-4">
        <div class="card-header">
            <div>
                <h3 class="card-title">Active clients</h3>
                <p class="card-subtitle">Open a workspace to enter data or run reports</p>
            </div>
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
                                <td class="text-slate-600 text-sm">{{ $client['sector'] ?? '—' }}</td>
                                <td class="text-sm">{{ $client['pry'] }}</td>
                                <td class="text-sm">{{ $client['locations'] }}</td>
                                <td class="text-sm font-medium">
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
@endsection

@if($hasPortfolioData)
@push('head')
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
@endpush
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const ctx = document.getElementById('portfolioScopeChart');
    if (!ctx) return;

    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: @json($scopeChartLabels),
            datasets: [{
                data: @json($scopeChartData),
                backgroundColor: ['#2563eb', '#60a5fa', '#93c5fd'],
                borderWidth: 0,
                hoverOffset: 4,
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '68%',
            plugins: { legend: { display: false } },
        },
    });
});
</script>
@endpush
@endif
