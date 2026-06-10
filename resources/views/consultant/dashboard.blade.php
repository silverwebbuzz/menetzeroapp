@extends('consultant.layouts.app')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@push('styles')
<style>
    .cd-hero {
        background: linear-gradient(135deg, #1d4ed8 0%, #2563eb 45%, #3b82f6 100%);
        border-radius: var(--radius-xl);
        color: #fff;
        padding: 1.75rem 2rem;
        position: relative;
        overflow: hidden;
    }
    .cd-hero::after {
        content: '';
        position: absolute;
        right: -2rem;
        top: -3rem;
        width: 14rem;
        height: 14rem;
        border-radius: 50%;
        background: rgba(255,255,255,0.08);
        pointer-events: none;
    }
    .cd-hero-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.375rem;
        padding: 0.25rem 0.625rem;
        border-radius: 9999px;
        font-size: 0.75rem;
        font-weight: 600;
        background: rgba(255,255,255,0.18);
        border: 1px solid rgba(255,255,255,0.25);
    }
    .cd-ring {
        width: 5.5rem;
        height: 5.5rem;
        border-radius: 50%;
        background: conic-gradient(var(--brand) calc(var(--pct) * 1%), var(--canvas-deep) 0);
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }
    .cd-ring-inner {
        width: 4.25rem;
        height: 4.25rem;
        border-radius: 50%;
        background: var(--surface);
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        font-size: 0.6875rem;
        font-weight: 600;
        color: var(--ink-muted);
        line-height: 1.2;
    }
    .cd-ring-inner strong {
        font-size: 1.125rem;
        color: var(--brand-darker);
    }
    .cd-action-tile {
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        gap: 0.5rem;
        padding: 1rem 1.125rem;
        border-radius: var(--radius-lg);
        border: 1px solid var(--line);
        background: var(--surface);
        text-decoration: none;
        color: var(--ink);
        transition: border-color 0.15s, box-shadow 0.15s, transform 0.15s;
        min-height: 6.5rem;
    }
    .cd-action-tile:hover {
        border-color: var(--brand);
        box-shadow: var(--shadow-sm);
        transform: translateY(-1px);
        text-decoration: none;
        color: var(--ink);
    }
    .cd-action-tile.primary {
        background: linear-gradient(135deg, var(--brand-soft) 0%, var(--surface) 100%);
        border-color: #bfdbfe;
    }
    .cd-action-icon {
        width: 2.25rem;
        height: 2.25rem;
        border-radius: var(--radius);
        background: var(--brand-soft);
        color: var(--brand);
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .cd-action-tile.primary .cd-action-icon {
        background: var(--brand);
        color: #fff;
    }
    .cd-progress-track {
        height: 0.5rem;
        border-radius: 9999px;
        background: var(--canvas-deep);
        overflow: hidden;
    }
    .cd-progress-fill {
        height: 100%;
        border-radius: 9999px;
        background: linear-gradient(90deg, var(--brand) 0%, #60a5fa 100%);
        transition: width 0.4s ease;
    }
    .cd-step {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.625rem 0;
    }
    .cd-step-dot {
        width: 1.5rem;
        height: 1.5rem;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        font-size: 0.6875rem;
        font-weight: 700;
    }
    .cd-step-dot.done { background: var(--brand-soft); color: var(--brand-darker); }
    .cd-step-dot.pending { background: var(--canvas-deep); color: var(--ink-subtle); border: 1px solid var(--line); }
    .cd-empty-illus {
        width: 4rem;
        height: 4rem;
        border-radius: 50%;
        background: var(--brand-soft);
        color: var(--brand);
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 1rem;
    }
</style>
@endpush

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
@endphp

@if(!empty($needsRenewal) && $renewalSubscription)
    <div class="mb-5 rounded-xl border border-amber-200 bg-amber-50 px-5 py-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 text-sm text-amber-900">
        <span>
            <strong>Renewal due</strong> — {{ $renewalSubscription->plan?->plan_name }} ends
            {{ $renewalSubscription->expires_at->format('d M Y') }}.
        </span>
        <a href="{{ route('consultant.renewal.index') }}" class="btn btn-warning btn-sm whitespace-nowrap">
            Renew for {{ (int) $renewalSubscription->contract_year + 1 }}
        </a>
    </div>
@endif

{{-- Hero --}}
<div class="cd-hero mb-5">
    <div class="relative z-10 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
        <div>
            <div class="cd-hero-badge mb-3">
                @if(!empty($slotSummary['is_trial']))
                    Free trial · 1 client slot
                @elseif($subscription)
                    {{ $subscription->plan?->plan_name ?? 'Agency pack' }} · {{ $subscription->contract_year }}
                @else
                    Consultant account
                @endif
            </div>
            <h2 class="text-2xl font-bold tracking-tight mb-1">Welcome back, {{ $consultant->name }}</h2>
            <p class="text-blue-100 text-sm max-w-xl">
                {{ $consultant->company_name }} — manage client carbon workspaces and your directory listing from one hub.
            </p>
        </div>
        <div class="flex flex-wrap gap-2 lg:justify-end">
            @if(!empty($slotSummary['remaining']))
                <a href="{{ route('consultant.clients.create') }}" class="btn btn-sm" style="background:#fff;color:#1d4ed8;font-weight:600;border:none;">
                    + Add client
                </a>
            @endif
            <a href="{{ route('consultant.workspace.switcher') }}" class="btn btn-sm" style="background:rgba(255,255,255,0.15);color:#fff;border:1px solid rgba(255,255,255,0.3);">
                Open workspace
            </a>
        </div>
    </div>
</div>

@if(!empty($slotSummary['is_trial']) && $activeClients->isEmpty())
    <div class="mb-5 rounded-xl border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-900">
        <strong>Your free trial is ready.</strong> Add one managed client to start capturing emissions data (no exports until you upgrade).
        <a href="{{ route('consultant.packs.index') }}" class="font-semibold underline ml-1">View agency packs</a>
    </div>
@endif

{{-- KPI row --}}
<div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-3 mb-5">
    <div class="stat-card" style="background:linear-gradient(135deg,var(--brand) 0%,var(--brand-dark) 100%);border-color:var(--brand-dark);color:#fff;">
        <div class="stat-card-label" style="color:rgba(255,255,255,0.85);">Portfolio emissions</div>
        <div class="stat-card-value" style="color:#fff;">
            {{ co2e_t($portfolio['total_emissions_kg'] ?? 0) }}
            <span style="font-size:0.75rem;font-weight:500;opacity:0.85;">tCO₂e</span>
        </div>
        <div class="stat-card-delta" style="color:rgba(255,255,255,0.9);">
            {{ $portfolio['clients_with_data'] ?? 0 }} of {{ $activeClients->count() }} clients with data
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-card-label">Active clients</div>
        <div class="stat-card-value">{{ $activeClients->count() }}</div>
        <div class="stat-card-delta">{{ $portfolio['locations_count'] ?? 0 }} locations across portfolio</div>
    </div>
    <div class="stat-card">
        <div class="stat-card-label">Client slots</div>
        <div class="stat-card-value">
            {{ $slotSummary['used'] ?? 0 }}<span class="text-slate-400 text-base font-medium"> / {{ $slotSummary['limit'] ?? 0 }}</span>
        </div>
        <div class="stat-card-delta">{{ $slotSummary['remaining'] ?? 0 }} remaining</div>
    </div>
    <div class="stat-card">
        <div class="stat-card-label">New leads</div>
        <div class="stat-card-value">{{ $introCount }}</div>
        <div class="stat-card-delta">
            <span class="{{ $statusColors[$consultant->status] ?? 'badge badge-neutral' }}">{{ $consultant->statusLabel() }}</span>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-5">
    {{-- Portfolio chart or empty state --}}
    <div class="card lg:col-span-2">
        <div class="card-header">
            <div>
                <h3 class="card-title">Client portfolio</h3>
                <p class="card-subtitle">Emissions tracked across your managed workspaces</p>
            </div>
            <a href="{{ route('consultant.clients.index') }}" class="btn btn-ghost btn-sm">
                All clients
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </a>
        </div>
        <div class="card-body">
            @if($hasPortfolioData)
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 items-center">
                    <div style="height:12rem;max-width:14rem;margin:0 auto;">
                        <canvas id="portfolioScopeChart"></canvas>
                    </div>
                    <div class="space-y-2">
                        @foreach($portfolio['scope_breakdown'] as $scope => $kg)
                            @if($kg > 0)
                                <div class="flex items-center justify-between text-sm py-1.5 border-b border-slate-100 last:border-0">
                                    <span class="text-slate-600">{{ $scope }}</span>
                                    <span class="font-semibold text-slate-900">{{ co2e_t($kg) }} tCO₂e</span>
                                </div>
                            @endif
                        @endforeach
                        @if(!empty($portfolio['sectors']))
                            <p class="text-xs text-slate-500 pt-2">
                                Sectors: {{ implode(', ', array_slice($portfolio['sectors'], 0, 4)) }}
                            </p>
                        @endif
                    </div>
                </div>
            @else
                <div class="text-center py-6">
                    <div class="cd-empty-illus">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-5.13a4 4 0 11-8 0 4 4 0 018 0zm6 3a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                    </div>
                    <h4 class="font-semibold text-slate-900 mb-1">No client data yet</h4>
                    <p class="text-sm text-slate-500 mb-4 max-w-sm mx-auto">
                        Add your first managed client and enter emissions to see portfolio insights here.
                    </p>
                    @if(!empty($slotSummary['remaining']))
                        <a href="{{ route('consultant.clients.create') }}" class="btn btn-primary btn-sm">Add your first client</a>
                    @endif
                </div>
            @endif
        </div>
    </div>

    {{-- Slots + directory progress --}}
    <div class="space-y-4">
        <div class="card">
            <div class="card-header">
                <div>
                    <h3 class="card-title">Slot usage</h3>
                    <p class="card-subtitle">
                        @if(!empty($slotSummary['is_trial']))
                            Free trial workspace
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
                    <p class="font-medium text-slate-900">{{ $slotSummary['remaining'] ?? 0 }} slots available</p>
                    @if(!empty($slotSummary['is_trial']))
                        <p class="text-slate-500 text-xs">Trial clients: data entry only. Upgrade for Growth exports.</p>
                        <a href="{{ route('consultant.packs.index') }}" class="text-brand text-xs font-semibold hover:underline">Upgrade pack →</a>
                    @elseif(($slotSummary['remaining'] ?? 0) === 0)
                        <a href="{{ route('consultant.packs.index') }}" class="text-brand text-xs font-semibold hover:underline">Add extra slots →</a>
                    @endif
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <div>
                    <h3 class="card-title">Directory setup</h3>
                    <p class="card-subtitle">{{ $directoryProgress['percent'] }}% complete</p>
                </div>
            </div>
            <div class="card-body">
                <div class="cd-progress-track mb-4">
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
                    <p class="text-xs text-amber-700 mt-3 pt-3 border-t border-slate-100">
                        Missing: {{ implode(', ', array_map(fn($t) => \App\Data\ConsultantOptions::labelFor('document', $t), $missingDocs)) }}
                    </p>
                @endif
                @if($directoryProgress['ready_to_submit'])
                    <form action="{{ route('consultant.profile.submit') }}" method="POST" class="mt-4">
                        @csrf
                        <button type="submit" class="btn btn-primary btn-sm w-full">Submit for review</button>
                    </form>
                @elseif($consultant->status === 'approved')
                    <p class="text-xs text-green-700 mt-3 pt-3 border-t border-slate-100">
                        ✓ Listed in the MenetZero consultant directory
                    </p>
                @elseif($consultant->status === 'pending_review')
                    <p class="text-xs text-amber-700 mt-3 pt-3 border-t border-slate-100">
                        Application under review — you can still add clients meanwhile.
                    </p>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- Quick actions --}}
<div class="mb-5">
    <h3 class="text-sm font-semibold text-slate-500 uppercase tracking-wider mb-3">Quick actions</h3>
    <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-5 gap-3">
        @foreach($quickActions as $action)
            <a href="{{ $action['route'] }}" class="cd-action-tile {{ $action['primary'] ? 'primary' : '' }}">
                <span class="cd-action-icon">
                    @if($action['icon'] === 'plus')
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    @elseif($action['icon'] === 'switch')
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                    @elseif($action['icon'] === 'card')
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                    @elseif($action['icon'] === 'refresh')
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                    @else
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/></svg>
                    @endif
                </span>
                <span class="text-sm font-semibold">{{ $action['label'] }}</span>
            </a>
        @endforeach
    </div>
</div>

{{-- Client roster --}}
@if($activeClients->isNotEmpty())
    <div class="card">
        <div class="card-header">
            <div>
                <h3 class="card-title">Active clients</h3>
                <p class="card-subtitle">Jump into a workspace or review progress</p>
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
                                        <span class="text-xs text-blue-600">Trial workspace</span>
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
            plugins: {
                legend: { display: false },
            },
        },
    });
});
</script>
@endpush
@endif
