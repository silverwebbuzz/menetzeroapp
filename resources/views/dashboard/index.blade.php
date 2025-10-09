@extends('layouts.app')

@section('title', 'Dashboard - MIDDLE EAST NET Zero')
@section('page-title', 'Dashboard')

@section('content')
@if(isset($needsCompanySetup) && $needsCompanySetup)
    <!-- Setup Notification Banner -->
    <div class="bg-gradient-to-r from-amber-50 to-orange-50 border border-amber-200 rounded-lg p-4 mb-6">
        <div class="flex items-center justify-between">
            <div class="flex items-center">
                <div class="w-10 h-10 bg-amber-100 rounded-full flex items-center justify-center mr-4">
                    <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                    </svg>
                </div>
                <div>
                    <h3 class="text-sm font-medium text-gray-900">Complete your business profile</h3>
                    <p class="text-xs text-gray-600">Get started with accurate carbon tracking and industry insights.</p>
                </div>
            </div>
            <button onclick="openCompanySetupModal()" class="bg-amber-600 hover:bg-amber-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                Complete Setup
            </button>
        </div>
    </div>
@endif

<div class="space-y-8">
    <!-- Header with Quick Actions -->
    <div class="mb-8">
        <div class="mb-4">
            <h2 class="text-2xl font-semibold" style="color: #111827;">Dashboard</h2>
            <p class="text-sm" style="color: #6b7280;">Welcome back, {{ auth()->user()->name }}</p>
            @if(isset($needsCompanySetup) && $needsCompanySetup)
                <p class="text-xs text-amber-600 mt-1">Complete your business profile to unlock full dashboard features</p>
            @endif
        </div>
        <div class="flex flex-wrap gap-3">
            <a href="{{ route('locations.index') }}" class="btn btn-primary">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                <span class="hidden sm:inline">Manage Locations</span>
                <span class="sm:hidden">Locations</span>
            </a>
            <a href="{{ route('measurements.index') }}" class="btn btn-outline">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                <span class="hidden sm:inline">Manage Reports</span>
                <span class="sm:hidden">Reports</span>
            </a>
            <button onclick="uploadBill()" class="btn btn-outline">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                <span class="hidden sm:inline">Upload Bill</span>
                <span class="sm:hidden">Upload</span>
            </button>
            <button onclick="generateReport()" class="btn btn-outline">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v8m4-4H8"/></svg>
                <span class="hidden sm:inline">Generate Report</span>
                <span class="sm:hidden">Generate</span>
            </button>
        </div>
    </div>

    @if(isset($needsCompanySetup) && $needsCompanySetup)
        <!-- Setup Reminder -->
        <div class="bg-amber-50 border border-amber-200 rounded-lg p-4 mb-6">
            <div class="flex items-center">
                <svg class="w-5 h-5 text-amber-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                </svg>
                <div class="flex-1">
                    <p class="text-sm text-amber-800">Complete your business profile to access detailed analytics and reporting features.</p>
                </div>
                <button onclick="openCompanySetupModal()" class="text-amber-700 hover:text-amber-900 font-medium text-sm">
                    Complete Now →
                </button>
            </div>
        </div>
    @endif

    <!-- KPI Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6">
        <!-- Total Emissions -->
        <div class="p-6 rounded-2xl text-white shadow-sm" style="background:linear-gradient(90deg, #26A69A 0%, #1f8e86 100%); border:1px solid rgba(38,166,154,.25)">
            <div class="flex items-start justify-between">
                <p class="text-sm/5 opacity-90">Total Emissions</p>
                <span class="text-white/80">🌿</span>
            </div>
            <div class="mt-2 text-3xl font-semibold">{{ number_format($kpis['total_emissions'] ?? 0, 2) }} kg CO₂e</div>
            <p class="mt-1 text-xs/5 opacity-90">
                @if(($kpis['monthly_change'] ?? 0) > 0)
                    <span class="text-red-200">↗ {{ $kpis['monthly_change'] ?? 0 }}%</span> from last month
                @elseif(($kpis['monthly_change'] ?? 0) < 0)
                    <span class="text-green-200">↘ {{ abs($kpis['monthly_change'] ?? 0) }}%</span> from last month
                @else
                    <span class="text-white/70">No change</span> from last month
                @endif
            </p>
        </div>

        <!-- Scope 1 -->
        <div class="p-6 rounded-2xl bg-white shadow-sm border border-gray-100">
            <div class="flex items-start justify-between"><p class="text-sm/5 text-gray-600">Scope 1 Emissions</p><span class="text-rose-500">📈</span></div>
            <div class="mt-2 text-2xl font-semibold text-gray-900">{{ number_format($kpis['scope1_total'] ?? 0, 2) }} kg CO₂e</div>
            <p class="mt-1 text-xs/5 text-rose-600">Direct emissions</p>
        </div>

        <!-- Scope 2 -->
        <div class="p-6 rounded-2xl bg-white shadow-sm border border-gray-100">
            <div class="flex items-start justify-between"><p class="text-sm/5 text-gray-600">Scope 2 Emissions</p><span class="text-amber-500">⚡</span></div>
            <div class="mt-2 text-2xl font-semibold text-gray-900">{{ number_format($kpis['scope2_total'] ?? 0, 2) }} kg CO₂e</div>
            <p class="mt-1 text-xs/5 text-emerald-600">Purchased energy</p>
        </div>

        <!-- Scope 3 -->
        <div class="p-6 rounded-2xl bg-white shadow-sm border border-gray-100">
            <div class="flex items-start justify-between"><p class="text-sm/5 text-gray-600">Scope 3 Emissions</p><span class="text-purple-500">🌐</span></div>
            <div class="mt-2 text-2xl font-semibold text-gray-900">{{ number_format($kpis['scope3_total'] ?? 0, 2) }} kg CO₂e</div>
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
                <div class="text-2xl font-bold text-blue-900">{{ $netZeroProgress['progress'] ?? 0 }}%</div>
                <div class="text-sm text-blue-600">{{ $netZeroProgress['years_remaining'] ?? 25 }} years remaining</div>
            </div>
        </div>
        
        <div class="w-full bg-blue-200 rounded-full h-3 mb-4">
            <div class="bg-gradient-to-r from-blue-500 to-indigo-600 h-3 rounded-full transition-all duration-500" 
                 style="width: {{ $netZeroProgress['progress'] ?? 0 }}%"></div>
        </div>
        
        <div class="grid grid-cols-3 gap-4 text-sm">
            <div class="text-center">
                <div class="font-semibold text-blue-900">{{ $netZeroProgress['current'] ?? 0 }} tCO₂e</div>
                <div class="text-blue-600">Current</div>
            </div>
            <div class="text-center">
                <div class="font-semibold text-blue-900">{{ $netZeroProgress['baseline'] ?? 1000 }} tCO₂e</div>
                <div class="text-blue-600">Baseline</div>
            </div>
            <div class="text-center">
                <div class="font-semibold text-blue-900">{{ $netZeroProgress['target'] ?? 0 }} tCO₂e</div>
                <div class="text-blue-600">Target 2050</div>
            </div>
        </div>
    </div>

    <!-- Charts Section -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 sm:gap-6">
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
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 sm:gap-6">
        <!-- Top Emission Sources -->
        <div class="card p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Top Emission Sources</h3>
            <div class="space-y-3">
                @forelse($topSources as $source)
                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                        <div>
                            <div class="font-medium text-gray-900">{{ $source['location'] }}</div>
                            <div class="text-sm text-gray-500">{{ $source['period'] }} • {{ ucfirst($source['status']) }}</div>
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
                    <!-- Empty state when no data exists -->
                    <div class="text-center py-8">
                        <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                            </svg>
                        </div>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">No emission sources yet</h3>
                        <p class="text-sm text-gray-500 mb-4">Start by adding locations and creating your first measurement.</p>
                        <a href="{{ route('locations.index') }}" class="inline-flex items-center px-4 py-2 bg-emerald-600 text-white text-sm font-medium rounded-lg hover:bg-emerald-700 transition-colors">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                            </svg>
                            Add Your First Location
                        </a>
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
                            <p class="text-sm font-medium text-gray-900">{{ $activity->location->name ?? 'Unknown Location' }}</p>
                            <p class="text-sm text-gray-500">{{ $activity->created_at->format('M d, Y') }} • {{ number_format($activity->total_co2e ?? 0, 2) }} kg CO₂e</p>
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
                    <!-- Empty state when no activity exists -->
                    <div class="text-center py-8">
                        <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">No recent activity</h3>
                        <p class="text-sm text-gray-500 mb-4">Your measurement activities will appear here.</p>
                        <a href="{{ route('measurements.index') }}" class="inline-flex items-center px-4 py-2 bg-emerald-600 text-white text-sm font-medium rounded-lg hover:bg-emerald-700 transition-colors">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            Create First Measurement
                        </a>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Reports Summary -->
    <div class="card p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Reports Summary</h3>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
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

// If no data, show sample data
const sampleMonthlyData = {
    'Jan 2024': 1200,
    'Feb 2024': 1350,
    'Mar 2024': 1100,
    'Apr 2024': 1450,
    'May 2024': 1300,
    'Jun 2024': 1600,
    'Jul 2024': 1400,
    'Aug 2024': 1250,
    'Sep 2024': 1500,
    'Oct 2024': 1700,
    'Nov 2024': 1550,
    'Dec 2024': 1800
};

const chartData = Object.keys(monthlyTrendData).length > 0 ? monthlyTrendData : sampleMonthlyData;

new Chart(monthlyTrendCtx, {
    type: 'line',
    data: {
        labels: Object.keys(chartData),
        datasets: [{
            label: 'Emissions (kg CO₂e)',
            data: Object.values(chartData),
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

// If no data, show sample data
const sampleScopeData = {
    'Scope 1': 450,
    'Scope 2': 320,
    'Scope 3': 280
};

const scopeData = Object.keys(scopeBreakdownData).length > 0 ? scopeBreakdownData : sampleScopeData;

new Chart(scopeBreakdownCtx, {
    type: 'doughnut',
    data: {
        labels: Object.keys(scopeData),
        datasets: [{
            data: Object.values(scopeData),
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
    window.location.href = '{{ route("locations.index") }}';
}

function generateReport() {
    // Generate and download report
    alert('Report generation feature coming soon!');
}
</script>
@endpush
@endsection

@if(isset($needsCompanySetup) && $needsCompanySetup)
    @include('components.company-setup-modal')
@endif