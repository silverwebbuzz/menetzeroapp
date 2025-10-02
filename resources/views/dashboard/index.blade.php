@extends('layouts.app')

@section('title', 'Dashboard - CarbonTracker')
@section('page-title', 'Dashboard')

@section('content')
<div class="space-y-8">
    <!-- Header / Greeting -->
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-semibold text-gray-900">Dashboard</h2>
            <p class="text-sm text-gray-500">Welcome back, {{ auth()->user()->name }}</p>
        </div>
        <a href="#" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg border border-emerald-200 text-emerald-700 bg-emerald-50 hover:bg-emerald-100 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v8m4-4H8"/></svg>
            Export Report
        </a>
    </div>

    <!-- KPI Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <div class="p-6 rounded-2xl bg-gradient-to-r from-emerald-500 to-emerald-600 text-white shadow-sm">
            <p class="text-sm/5 opacity-90">Total Emissions</p>
            <div class="mt-2 text-3xl font-semibold">4,480 tCOe</div>
            <p class="mt-1 text-xs/5 opacity-90">-5.2% from last month</p>
        </div>
        <div class="p-6 rounded-2xl bg-white shadow-sm border border-gray-100">
            <p class="text-sm/5 text-gray-600">Scope 1 Emissions</p>
            <div class="mt-2 text-2xl font-semibold text-gray-900">1,250 tCOe</div>
            <p class="mt-1 text-xs/5 text-rose-600">+2.1% from last month</p>
        </div>
        <div class="p-6 rounded-2xl bg-white shadow-sm border border-gray-100">
            <p class="text-sm/5 text-gray-600">Scope 2 Emissions</p>
            <div class="mt-2 text-2xl font-semibold text-gray-900">890 tCOe</div>
            <p class="mt-1 text-xs/5 text-emerald-600">-3.4% from last month</p>
        </div>
        <div class="p-6 rounded-2xl bg-white shadow-sm border border-gray-100">
            <p class="text-sm/5 text-gray-600">Scope 3 Emissions</p>
            <div class="mt-2 text-2xl font-semibold text-gray-900">2,340 tCOe</div>
            <p class="mt-1 text-xs/5 text-emerald-600">-8.1% from last month</p>
        </div>
    </div>

    <!-- Charts -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
            <div class="flex items-center gap-2 mb-4">
                <svg class="w-5 h-5 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7h18M3 12h18M3 17h18"/></svg>
                <h3 class="text-lg font-semibold text-gray-900">Monthly Emission Trends</h3>
            </div>
            <div class="h-80">
                <canvas id="trendsChart" class="w-full h-full"></canvas>
            </div>
            <div class="mt-4 flex flex-wrap items-center gap-4 text-sm">
                <span class="inline-flex items-center gap-2"><span class="w-3 h-3 rounded-full bg-emerald-600"></span> Total Emissions</span>
                <span class="inline-flex items-center gap-2"><span class="w-3 h-3 rounded-full bg-rose-500"></span> Scope 1</span>
                <span class="inline-flex items-center gap-2"><span class="w-3 h-3 rounded-full bg-amber-500"></span> Scope 2</span>
                <span class="inline-flex items-center gap-2"><span class="w-3 h-3 rounded-full bg-indigo-500"></span> Scope 3</span>
            </div>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
            <div class="flex items-center gap-2 mb-4">
                <svg class="w-5 h-5 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3v18h18"/></svg>
                <h3 class="text-lg font-semibold text-gray-900">Emissions by Scope</h3>
            </div>
            <div class="h-80">
                <canvas id="scopeChart" class="w-full h-full"></canvas>
            </div>
        </div>
    </div>

    <!-- Recent Activities -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900">Recent Activities</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="table-header">Activity</th>
                        <th class="table-header">Scope</th>
                        <th class="table-header">Emissions</th>
                        <th class="table-header">Date</th>
                        <th class="table-header">Status</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <tr>
                        <td class="table-cell">Fuel Consumption</td>
                        <td class="table-cell">Scope 1</td>
                        <td class="table-cell">245.5 kg CO2e</td>
                        <td class="table-cell">2024-01-15</td>
                        <td class="table-cell">
                            <span class="badge-success">Verified</span>
                        </td>
                    </tr>
                    <tr>
                        <td class="table-cell">Electricity Usage</td>
                        <td class="table-cell">Scope 2</td>
                        <td class="table-cell">189.2 kg CO2e</td>
                        <td class="table-cell">2024-01-14</td>
                        <td class="table-cell">
                            <span class="badge-warning">Pending</span>
                        </td>
                    </tr>
                    <tr>
                        <td class="table-cell">Business Travel</td>
                        <td class="table-cell">Scope 3</td>
                        <td class="table-cell">156.8 kg CO2e</td>
                        <td class="table-cell">2024-01-13</td>
                        <td class="table-cell">
                            <span class="badge-success">Verified</span>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Trends Chart
    const trendsCtx = document.getElementById('trendsChart').getContext('2d');
    new Chart(trendsCtx, {
        type: 'line',
        data: {
            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
            datasets: [
                { label: 'Total Emissions', data: [4200, 4300, 4400, 4550, 4620, 4680], borderColor: '#059669', backgroundColor: 'rgba(5,150,105,.12)', fill: true, tension: .35 },
                { label: 'Scope 1', data: [1100, 1120, 1140, 1150, 1160, 1175], borderColor: '#ef4444', backgroundColor: 'transparent', tension: .35 },
                { label: 'Scope 2', data: [900, 905, 910, 920, 930, 940], borderColor: '#f59e0b', backgroundColor: 'transparent', tension: .35 },
                { label: 'Scope 3', data: [2200, 2275, 2350, 2450, 2530, 2565], borderColor: '#3b82f6', backgroundColor: 'transparent', tension: .35 }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { intersect: false, mode: 'index' },
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: false, grid: { color: '#eef2ff' } },
                x: { grid: { display: false } }
            }
        }
    });

    // Scope Chart (horizontal bar)
    const scopeCtx = document.getElementById('scopeChart').getContext('2d');
    new Chart(scopeCtx, {
        type: 'bar',
        data: {
            labels: ['Scope 1', 'Scope 2', 'Scope 3'],
            datasets: [{
                data: [1250, 890, 2340],
                backgroundColor: ['#ef4444', '#f59e0b', '#3b82f6'],
                borderRadius: 8,
                barThickness: 18
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                x: { grid: { color: '#eef2ff' } },
                y: { grid: { display: false } }
            }
        }
    });
</script>
@endpush
