@extends('admin.layouts.app')

@section('title', 'Super Admin Dashboard | MENetZero')
@section('page-title', 'Super Admin Dashboard')

@section('content')
    <div class="page-header">
        <div>
            <h1>Super Admin Dashboard</h1>
            <p>Overview of companies, users, and subscriptions across the MENetZero platform.</p>
        </div>
    </div>

    @isset($stats)
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 mb-5">
            <div class="stat-card">
                <div class="stat-card-label">Total Companies</div>
                <div class="stat-card-value">{{ number_format($stats['total_companies'] ?? 0) }}</div>
            </div>
            <div class="stat-card">
                <div class="stat-card-label">Client Companies</div>
                <div class="stat-card-value">{{ number_format($stats['total_clients'] ?? 0) }}</div>
            </div>
            <div class="stat-card">
                <div class="stat-card-label">Total Users</div>
                <div class="stat-card-value">{{ number_format($stats['total_users'] ?? 0) }}</div>
            </div>
            <div class="stat-card">
                <div class="stat-card-label">Active Subscriptions</div>
                <div class="stat-card-value">{{ number_format($stats['active_client_subscriptions'] ?? 0) }}</div>
            </div>
        </div>
    @endisset

    @isset($recentCompanies)
        <div class="card">
            <div class="card-header">
                <div>
                    <h2 class="card-title">Recent Companies</h2>
                    <p class="card-subtitle">Latest organisations to join the platform.</p>
                </div>
                <a href="{{ route('admin.companies.index') }}" class="btn btn-ghost btn-sm">
                    View all
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>
            </div>
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Type</th>
                            <th>Created</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentCompanies as $company)
                            <tr>
                                <td class="cell-strong">{{ $company->name }}</td>
                                <td class="cell-muted">{{ $company->email }}</td>
                                <td>
                                    @if(($company->company_type ?? 'client') === 'partner')
                                        <span class="badge badge-info badge-dot">Partner</span>
                                    @else
                                        <span class="badge badge-brand badge-dot">Client</span>
                                    @endif
                                </td>
                                <td class="cell-muted">{{ optional($company->created_at)->format('d M Y') ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center py-6 cell-muted">No companies found yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endisset
@endsection


