@extends('layouts.app')

@section('title', 'Dashboard - CarbonTracker')
@section('page-title', 'Dashboard')

@section('content')
<div class="space-y-6">
    <!-- Welcome Section -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <h2 class="text-2xl font-bold text-gray-900 mb-2">Welcome back, {{ auth()->user()->name }}!</h2>
        <p class="text-gray-600">Here's an overview of your carbon emissions tracking.</p>
    </div>

    <!-- Metrics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <div class="metric-card">
            <div class="metric-value">1,234</div>
            <div class="metric-label">Total Emissions (kg CO2e)</div>
        </div>
        <div class="metric-card">
            <div class="metric-value">-12%</div>
            <div class="metric-label">vs Last Month</div>
        </div>
        <div class="metric-card">
            <div class="metric-value">45</div>
            <div class="metric-label">Activities Tracked</div>
        </div>
        <div class="metric-card">
            <div class="metric-value">3</div>
            <div class="metric-label">Scopes Covered</div>
        </div>
    </div>

    <!-- Charts Section -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="chart-container">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Emissions by Scope</h3>
            <canvas id="scopeChart" width="400" height="200"></canvas>
        </div>
        <div class="chart-container">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Monthly Trends</h3>
            <canvas id="trendsChart" width="400" height="200"></canvas>
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
    // Scope Chart
    const scopeCtx = document.getElementById('scopeChart').getContext('2d');
    new Chart(scopeCtx, {
        type: 'doughnut',
        data: {
            labels: ['Scope 1', 'Scope 2', 'Scope 3'],
            datasets: [{
                data: [450, 320, 464],
                backgroundColor: ['#28a745', '#4ade80', '#16a34a'],
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

    // Trends Chart
    const trendsCtx = document.getElementById('trendsChart').getContext('2d');
    new Chart(trendsCtx, {
        type: 'line',
        data: {
            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
            datasets: [{
                label: 'Total Emissions',
                data: [1200, 1100, 1350, 1250, 1180, 1234],
                borderColor: '#28a745',
                backgroundColor: 'rgba(40, 167, 69, 0.1)',
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
</script>
@endpush
