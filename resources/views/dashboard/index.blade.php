@extends('layouts.app')

@section('title', 'Dashboard - MenetZero')
@section('page-title', 'Dashboard')

@section('content')
@if(isset($needsCompanySetup) && $needsCompanySetup)
    <!-- Profile Completion Message -->
    <div class="bg-gradient-to-r from-blue-50 to-indigo-50 border border-blue-200 rounded-xl p-8 text-center">
        <div class="max-w-2xl mx-auto">
            <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
            <h2 class="text-2xl font-semibold text-gray-900 mb-2">Complete Your Business Profile</h2>
            <p class="text-gray-600 mb-6">To get started with carbon tracking, please complete your business profile. This helps us provide accurate emissions data and industry-specific insights.</p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="{{ route('company.setup') }}" class="inline-flex items-center gap-2 px-6 py-3 rounded-lg text-white bg-[color:var(--brand)] hover:bg-[color:var(--brand)]/90 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    Complete Business Profile
                </a>
                <a href="{{ route('company.setup.skip') }}" class="inline-flex items-center gap-2 px-6 py-3 rounded-lg border text-gray-700 border-gray-300 hover:bg-gray-50 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                    Skip for Now
                </a>
            </div>
            <p class="text-sm text-gray-500 mt-4">You can complete this later from your profile settings.</p>
        </div>
    </div>
@else
<style>
    :root { --brand:#004D40; --accent:#26A69A; --bg:#F9FAFB; }
    .card { border:1px solid #e5e7eb; border-radius:1rem; background:#fff; box-shadow:0 10px 20px -10px rgba(0,0,0,.08); transition: box-shadow .25s ease, transform .25s ease; }
    .card:hover { box-shadow:0 16px 28px -12px rgba(0,0,0,.12); transform: translateY(-1px); }
    .chip { display:inline-flex; align-items:center; gap:.5rem; padding:.25rem .5rem; border-radius:9999px; font-size:.75rem; border:1px solid #e5e7eb; }
</style>

<div class="space-y-8">
    <!-- Header with Quick Actions -->
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-semibold text-gray-900">Dashboard</h2>
            <p class="text-sm text-gray-500">Welcome back, {{ auth()->user()->name }}</p>
        </div>
        <div class="flex gap-3">
            <a href="{{ route('emission-form.index') }}" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg border text-white bg-[color:var(--brand)] hover:bg-[color:var(--brand)]/90 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                New Emission Report
            </a>
            <a href="{{ route('emissions.management') }}" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg border text-[color:var(--brand)] border-[color:var(--accent)]/30 bg-[color:var(--accent)]/10 hover:bg-[color:var(--accent)]/20 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                Manage Reports
            </a>
            <button onclick="uploadBill()" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg border text-[color:var(--brand)] border-[color:var(--accent)]/30 bg-[color:var(--accent)]/10 hover:bg-[color:var(--accent)]/20 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                Upload Bill
            </button>
            <button onclick="generateReport()" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg border text-[color:var(--brand)] border-[color:var(--accent)]/30 bg-[color:var(--accent)]/10 hover:bg-[color:var(--accent)]/20 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v8m4-4H8"/></svg>
                Generate Report
            </button>
        </div>
    </div>

    <!-- KPI Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- Total Emissions -->
        <div class="p-6 rounded-2xl text-white shadow-sm" style="background:linear-gradient(90deg, #26A69A 0%, #1f8e86 100%); border:1px solid rgba(38,166,154,.25)">
            <div class="flex items-start justify-between">
                <p class="text-sm/5 opacity-90">Total Emissions</p>
                <span class="text-white/80">🌿</span>
            </div>
            <div class="mt-2 text-3xl font-semibold">{{ number_format($kpis['total_emissions'], 2) }} kg CO₂e</div>
            <p class="mt-1 text-xs/5 opacity-90">
                @if($kpis['monthly_change'] > 0)
                    <span class="text-red-200">↗ {{ $kpis['monthly_change'] }}%</span> from last month
                @elseif($kpis['monthly_change'] < 0)
                    <span class="text-green-200">↘ {{ abs($kpis['monthly_change']) }}%</span> from last month
                @else
                    <span class="text-white/70">No change</span> from last month
                @endif
            </p>
        </div>

        <!-- Scope 1 -->
        <div class="p-6 rounded-2xl bg-white shadow-sm border border-gray-100">
            <div class="flex items-start justify-between"><p class="text-sm/5 text-gray-600">Scope 1 Emissions</p><span class="text-rose-500">📈</span></div>
            <div class="mt-2 text-2xl font-semibold text-gray-900">{{ number_format($kpis['scope1_total'], 2) }} kg CO₂e</div>
            <p class="mt-1 text-xs/5 text-rose-600">Direct emissions</p>
        </div>

        <!-- Scope 2 -->
        <div class="p-6 rounded-2xl bg-white shadow-sm border border-gray-100">
            <div class="flex items-start justify-between"><p class="text-sm/5 text-gray-600">Scope 2 Emissions</p><span class="text-amber-500">⚡</span></div>
            <div class="mt-2 text-2xl font-semibold text-gray-900">{{ number_format($kpis['scope2_total'], 2) }} kg CO₂e</div>
            <p class="mt-1 text-xs/5 text-emerald-600">Purchased energy</p>
        </div>

        <!-- Scope 3 -->
        <div class="p-6 rounded-2xl bg-white shadow-sm border border-gray-100">
            <div class="flex items-start justify-between"><p class="text-sm/5 text-gray-600">Scope 3 Emissions</p><span class="text-purple-500">🌐</span></div>
            <div class="mt-2 text-2xl font-semibold text-gray-900">{{ number_format($kpis['scope3_total'], 2) }} kg CO₂e</div>
            <p class="mt-1 text-xs/5 text-purple-600">Other indirect</p>
        </div>
    </div>

    <!-- UAE Net Zero Progress -->
    <div class="bg-gradient-to-r from-blue-50 to-indigo-50 border border-blue-200 rounded-xl p-6">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h3 class="text-lg font-semibold text-blue-900">UAE Net Zero 2050 Progress</h3>
                <p class="text-sm text-blue-700">Track your progress towards carbon neutrality</p>
            </div>
            <div class="text-right">
                <div class="text-2xl font-bold text-blue-900">{{ $netZeroProgress['progress'] }}%</div>
                <div class="text-sm text-blue-600">{{ $netZeroProgress['years_remaining'] }} years remaining</div>
            </div>
        </div>
        
        <div class="w-full bg-blue-200 rounded-full h-3 mb-4">
            <div class="bg-gradient-to-r from-blue-500 to-indigo-600 h-3 rounded-full transition-all duration-500" 
                 style="width: {{ $netZeroProgress['progress'] }}%"></div>
        </div>
        
        <div class="grid grid-cols-3 gap-4 text-sm">
            <div class="text-center">
                <div class="font-semibold text-blue-900">{{ $netZeroProgress['current'] }} tCO₂e</div>
                <div class="text-blue-600">Current</div>
            </div>
            <div class="text-center">
                <div class="font-semibold text-blue-900">{{ $netZeroProgress['baseline'] }} tCO₂e</div>
                <div class="text-blue-600">Baseline</div>
            </div>
            <div class="text-center">
                <div class="font-semibold text-blue-900">{{ $netZeroProgress['target'] }} tCO₂e</div>
                <div class="text-blue-600">Target 2050</div>
            </div>
        </div>
    </div>

    <!-- Charts Section -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Monthly Emissions Trend -->
        <div class="card p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Monthly Emissions Trend</h3>
            <div class="h-64">
                <canvas id="monthlyTrendChart"></canvas>
            </div>
        </div>

        <!-- Emissions by Scope -->
        <div class="card p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Emissions by Scope</h3>
            <div class="h-64">
                <canvas id="scopeBreakdownChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Top Emission Sources & Recent Activity -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Top Emission Sources -->
        <div class="card p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Top Emission Sources</h3>
            <div class="space-y-3">
                @forelse($topSources as $source)
                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                        <div>
                            <div class="font-medium text-gray-900">{{ $source['company'] }}</div>
                            <div class="text-sm text-gray-500">{{ $source['year'] }} • {{ ucfirst($source['status']) }}</div>
                        </div>
                        <div class="text-right">
                            <div class="font-semibold text-gray-900">{{ number_format($source['emissions'], 2) }} kg CO₂e</div>
                            <div class="text-xs text-gray-500">
                                S1: {{ number_format($source['scope1'], 0) }} | 
                                S2: {{ number_format($source['scope2'], 0) }} | 
                                S3: {{ number_format($source['scope3'], 0) }}
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-8 text-gray-500">
                        <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        <p>No emission reports yet</p>
                        <p class="text-sm">Create your first report to see data here</p>
                    </div>
                @endforelse
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="card p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Recent Activity</h3>
            <div class="space-y-3">
                @forelse($recentActivity as $activity)
                    <div class="flex items-center space-x-3">
                        <div class="flex-shrink-0">
                            @switch($activity->status)
                                @case('draft')
                                    <div class="w-8 h-8 bg-amber-100 rounded-full flex items-center justify-center">
                                        <svg class="w-4 h-4 text-amber-600" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"></path>
                                        </svg>
                                    </div>
                                    @break
                                @case('submitted')
                                    <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center">
                                        <svg class="w-4 h-4 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                        </svg>
                                    </div>
                                    @break
                                @case('reviewed')
                                    <div class="w-8 h-8 bg-purple-100 rounded-full flex items-center justify-center">
                                        <svg class="w-4 h-4 text-purple-600" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                        </svg>
                                    </div>
                                    @break
                            @endswitch
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-900">{{ $activity->company_name }}</p>
                            <p class="text-sm text-gray-500">{{ $activity->created_at->format('M d, Y') }} • {{ number_format($activity->grand_total ?? 0, 2) }} kg CO₂e</p>
                        </div>
                        <div class="flex-shrink-0">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                @if($activity->status === 'draft') bg-amber-100 text-amber-800
                                @elseif($activity->status === 'submitted') bg-green-100 text-green-800
                                @else bg-purple-100 text-purple-800
                                @endif">
                                {{ ucfirst($activity->status) }}
                            </span>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-8 text-gray-500">
                        <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <p>No recent activity</p>
                        <p class="text-sm">Start creating emission reports</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Reports Summary -->
    <div class="card p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Reports Summary</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="text-center p-4 bg-blue-50 rounded-lg">
                <div class="text-2xl font-bold text-blue-900">{{ $kpis['reports_count'] }}</div>
                <div class="text-sm text-blue-600">Total Reports</div>
            </div>
            <div class="text-center p-4 bg-amber-50 rounded-lg">
                <div class="text-2xl font-bold text-amber-900">{{ $kpis['draft_reports'] }}</div>
                <div class="text-sm text-amber-600">Draft Reports</div>
            </div>
            <div class="text-center p-4 bg-green-50 rounded-lg">
                <div class="text-2xl font-bold text-green-900">{{ $kpis['submitted_reports'] }}</div>
                <div class="text-sm text-green-600">Submitted Reports</div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Monthly Trend Chart
const monthlyTrendCtx = document.getElementById('monthlyTrendChart').getContext('2d');
const monthlyTrendData = @json($chartData['monthly_trend']);

new Chart(monthlyTrendCtx, {
    type: 'line',
    data: {
        labels: Object.keys(monthlyTrendData),
        datasets: [{
            label: 'Emissions (kg CO₂e)',
            data: Object.values(monthlyTrendData),
            borderColor: '#26A69A',
            backgroundColor: 'rgba(38, 166, 154, 0.1)',
            borderWidth: 3,
            fill: true,
            tension: 0.4
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                grid: {
                    color: 'rgba(0,0,0,0.1)'
                }
            },
            x: {
                grid: {
                    display: false
                }
            }
        }
    }
});

// Scope Breakdown Chart
const scopeBreakdownCtx = document.getElementById('scopeBreakdownChart').getContext('2d');
const scopeBreakdownData = @json($chartData['scope_breakdown']);

new Chart(scopeBreakdownCtx, {
    type: 'doughnut',
    data: {
        labels: Object.keys(scopeBreakdownData),
        datasets: [{
            data: Object.values(scopeBreakdownData),
            backgroundColor: [
                '#EF4444', // Scope 1 - Red
                '#F59E0B', // Scope 2 - Amber
                '#8B5CF6'  // Scope 3 - Purple
            ],
            borderWidth: 0
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom'
            }
        }
    }
});

// Quick Actions
function uploadBill() {
    // Redirect to emission form with file upload focus
    window.location.href = '{{ route("emission-form.step", "evidence") }}';
}

function generateReport() {
    // Generate and download report
    alert('Report generation feature coming soon!');
}
</script>
@endpush
@endif
@endsection