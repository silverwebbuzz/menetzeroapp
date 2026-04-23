@extends('admin.layouts.app')

@section('title', 'Companies | MENetZero')
@section('page-title', 'Companies')

@section('content')
    {{-- Page header --}}
    <div class="page-header">
        <div>
            <h1>Companies</h1>
            <p>All organisations using the MenetZero platform. Search, filter and drill into any company to see their emissions profile.</p>
        </div>
        <div class="page-header-actions">
            <button type="button" class="btn btn-secondary" onclick="window.print()">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                </svg>
                Export
            </button>
        </div>
    </div>

    {{-- Stat cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 mb-5">
        <div class="stat-card">
            <div class="stat-card-label">Total Companies</div>
            <div class="stat-card-value">{{ method_exists($companies, 'total') ? $companies->total() : $companies->count() }}</div>
        </div>
        <div class="stat-card">
            <div class="stat-card-label">Clients</div>
            <div class="stat-card-value">{{ $companies->where('company_type', 'client')->count() + $companies->whereNull('company_type')->count() }}</div>
        </div>
        <div class="stat-card">
            <div class="stat-card-label">Partners</div>
            <div class="stat-card-value">{{ $companies->where('company_type', 'partner')->count() }}</div>
        </div>
        <div class="stat-card">
            <div class="stat-card-label">Active this page</div>
            <div class="stat-card-value">{{ $companies->where('is_active', true)->count() }}</div>
        </div>
    </div>

    {{-- Table card --}}
    <div class="card">
        <div class="card-header">
            <div>
                <h2 class="card-title">All Companies</h2>
                <p class="card-subtitle">Filter by type or search by name and email.</p>
            </div>

            <form method="GET" class="flex flex-wrap items-center gap-2">
                <div class="relative">
                    <svg class="absolute left-2.5 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M10.5 18a7.5 7.5 0 110-15 7.5 7.5 0 010 15z"/>
                    </svg>
                    <input
                        type="text"
                        name="search"
                        value="{{ request('search') }}"
                        placeholder="Search by name or email"
                        class="form-control"
                        style="padding-left: 2.125rem; min-width: 16rem;"
                    >
                </div>
                <select name="type" class="form-select" style="min-width: 9rem;">
                    <option value="">All types</option>
                    <option value="client" {{ request('type') === 'client' ? 'selected' : '' }}>Client</option>
                    <option value="partner" {{ request('type') === 'partner' ? 'selected' : '' }}>Partner</option>
                </select>
                <button type="submit" class="btn btn-primary">Apply</button>
            </form>
        </div>

        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Company</th>
                        <th>Email</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($companies as $company)
                        @php
                            $type = $company->company_type ?? 'client';
                            $initial = strtoupper(substr($company->name ?? '?', 0, 1));
                        @endphp
                        <tr>
                            <td>
                                <div class="flex items-center gap-3">
                                    <span class="avatar">{{ $initial }}</span>
                                    <div class="min-w-0">
                                        <div class="cell-strong truncate">{{ $company->name }}</div>
                                        @if($company->industry)
                                            <div class="text-xs text-slate-500 truncate">{{ $company->industry }}</div>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="cell-muted">{{ $company->email ?? '—' }}</td>
                            <td>
                                @if($type === 'partner')
                                    <span class="badge badge-info badge-dot">Partner</span>
                                @else
                                    <span class="badge badge-brand badge-dot">Client</span>
                                @endif
                            </td>
                            <td>
                                @if($company->is_active)
                                    <span class="badge badge-success badge-dot">Active</span>
                                @else
                                    <span class="badge badge-neutral badge-dot">Inactive</span>
                                @endif
                            </td>
                            <td class="cell-muted">{{ optional($company->created_at)->format('d M Y') ?? '—' }}</td>
                            <td>
                                <div class="row-actions">
                                    <a href="{{ route('admin.companies.show', $company->id) }}" class="btn btn-ghost btn-xs">
                                        View
                                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                        </svg>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-10 cell-muted">
                                <div class="flex flex-col items-center gap-2">
                                    <svg class="w-8 h-8 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                    </svg>
                                    <div class="font-medium text-slate-700">No companies found</div>
                                    <div class="text-xs">Try adjusting your search or filters.</div>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if(method_exists($companies, 'hasPages') && $companies->hasPages())
            <div class="card-footer">
                {{ $companies->withQueryString()->links() }}
            </div>
        @endif
    </div>
@endsection
