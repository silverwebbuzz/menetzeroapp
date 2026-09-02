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
            <div class="stat-card-label">Consultants</div>
            <div class="stat-card-value">{{ $companies->where('company_type', 'consultant')->count() }}</div>
        </div>
        <div class="stat-card">
            <div class="stat-card-label">Active this page</div>
            <div class="stat-card-value">{{ $companies->where('is_active', true)->count() }}</div>
        </div>
    </div>

    {{-- Table card --}}
    {{-- Three-way split. company_type alone does not separate a direct client
         from a consultant-managed one -- both are 'client'. See
         SuperAdminController::companies(). --}}
    <div class="flex flex-wrap gap-2 mb-4">
        @foreach ([
            'direct' => 'Direct companies',
            'consultant' => 'Consultants',
        ] as $key => $label)
            <a href="{{ route('admin.companies.index', ['tab' => $key]) }}"
               class="px-4 py-2 rounded-lg text-sm font-medium {{ $tab === $key ? 'bg-purple-600 text-white' : 'bg-white text-slate-600 border border-slate-200 hover:bg-slate-50' }}">
                {{ $label }}
                <span class="ml-1 opacity-70">{{ $counts[$key] }}</span>
            </a>
        @endforeach
    </div>

    <div class="card">
        <div class="card-header">
            <div>
                <h2 class="card-title">{{ $tab === 'consultant' ? 'Consultants' : 'Direct companies' }}</h2>
                <p class="card-subtitle">
                    {{ $tab === 'consultant'
                        ? 'Consultant agencies. Their client companies are listed on each agency page.'
                        : 'Client companies that signed up directly, with no consultant behind them.' }}
                </p>
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
                {{-- Carry the tab through search, or searching would drop
                     the user back to the default list. --}}
                <input type="hidden" name="tab" value="{{ $tab }}">

                <label class="flex items-center gap-1.5 text-sm text-slate-600">
                    <input type="checkbox" name="dormant" value="1" {{ request()->boolean('dormant') ? 'checked' : '' }}>
                    Dormant only
                </label>
                <input type="number" name="dormant_days" value="{{ $dormantDays }}" min="7" max="365"
                       class="form-control" style="width: 5.5rem;" title="Days with no activity">
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
                        <th>Plan</th>
                        <th>Last login</th>
                        <th>{{ $tab === 'consultant' ? 'Clients' : 'Data' }}</th>
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
                                @if($type === 'consultant')
                                    <span class="badge badge-info badge-dot">Consultant</span>
                                @else
                                    <span class="badge badge-brand badge-dot">Client</span>
                                @endif
                            </td>

                            {{-- One accessor for both kinds: a consultant agency's plan
                                 lives in consultant_subscriptions, a client's in
                                 client_subscriptions. Null means nothing active, which
                                 is the free tier. --}}
                            <td>
                                @php $planName = $company->currentPlanName(); @endphp
                                @if($planName)
                                    <span class="badge badge-brand">{{ $planName }}</span>
                                @else
                                    <span class="text-xs text-slate-400">Free</span>
                                @endif
                            </td>

                            {{-- users_max_last_login_at comes from withMax(): the most
                                 recent sign-in by anyone at this company. Null means
                                 nobody has ever logged in since tracking was added. --}}
                            @php
                                $lastLogin = $company->users_max_last_login_at
                                    ? \Illuminate\Support\Carbon::parse($company->users_max_last_login_at)
                                    : null;
                                $idleDays = $lastLogin ? (int) $lastLogin->diffInDays(now()) : null;
                                $hasData = ($company->carbon_emissions_count ?? 0) > 0
                                    || ($company->locations_count ?? 0) > 0;
                            @endphp
                            <td>
                                @if($lastLogin)
                                    <div class="cell-strong">{{ $lastLogin->format('d M Y') }}</div>
                                    <div class="text-xs {{ $idleDays > 30 ? 'text-amber-600' : 'text-slate-500' }}">
                                        {{ $idleDays === 0 ? 'today' : $idleDays . 'd ago' }}
                                    </div>
                                @else
                                    <span class="text-xs text-slate-400">never</span>
                                @endif
                            </td>
                            <td>
                                @if($tab === 'consultant')
                                    {{-- managed_clients_count uses consultant_id alone, the same
                                         test OrganisationDeletionService::blockerFor() applies, so
                                         this number always agrees with the delete blocker. --}}
                                    <div class="text-xs text-slate-600">
                                        {{ $company->managed_clients_count ?? 0 }}
                                        {{ ($company->managed_clients_count ?? 0) === 1 ? 'client' : 'clients' }}
                                    </div>
                                @elseif($hasData)
                                    <div class="text-xs text-slate-600">
                                        {{ $company->carbon_emissions_count ?? 0 }} entries ·
                                        {{ $company->locations_count ?? 0 }} sites
                                    </div>
                                @else
                                    <span class="badge badge-warning">no data</span>
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
                                    {{-- Carry the originating tab so the detail page can send the admin back
                                         to the list they came from, not the default one. --}}
                                    <a href="{{ route('admin.companies.show', ['id' => $company->id, 'from' => $tab]) }}" class="btn btn-ghost btn-xs">
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
                            <td colspan="9" class="text-center py-10 cell-muted">
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
