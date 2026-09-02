@extends('admin.layouts.app')

@section('title', 'Company Details | MENetZero')
@section('page-title', 'Company Details')

@section('content')
    @isset($company)
        {{-- Returns to whichever list the admin arrived from -- see
             SuperAdminController::resolveBackUrl(). --}}
        <a href="{{ $back['url'] }}" class="text-sm text-purple-600 hover:underline">&larr; {{ $back['label'] }}</a>

        <div class="space-y-6 mt-3">
            <div class="bg-white shadow rounded-lg p-4">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Company Information</h2>
                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                    <div>
                        <dt class="font-medium text-gray-500">Name</dt>
                        <dd class="text-gray-900">{{ $company->name }}</dd>
                    </div>
                    <div>
                        <dt class="font-medium text-gray-500">Email</dt>
                        <dd class="text-gray-900">{{ $company->email }}</dd>
                    </div>
                    <div>
                        <dt class="font-medium text-gray-500">Type</dt>
                        <dd class="text-gray-900">{{ $company->company_type ?? 'client' }}</dd>
                    </div>
                    <div>
                        <dt class="font-medium text-gray-500">Created At</dt>
                        <dd class="text-gray-900">{{ optional($company->created_at)->format('Y-m-d') }}</dd>
                    </div>
                </dl>
            </div>

            <div class="bg-white shadow rounded-lg p-4">
                <h2 class="text-md font-semibold text-gray-900 mb-3">Users</h2>
                @if($company->users->isEmpty())
                    <p class="text-sm text-gray-500">No users linked to this company.</p>
                @else
                    <ul class="divide-y divide-gray-200 text-sm">
                        @foreach ($company->users as $user)
                            <li class="py-2 flex items-center justify-between">
                                <div>
                                    <div class="text-gray-900">{{ $user->name }}</div>
                                    <div class="text-gray-500 text-xs">{{ $user->email }}</div>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>

            @if(session('success'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">{{ session('error') }}</div>
            @endif

            <div class="bg-white shadow rounded-lg p-4">
                <h2 class="text-md font-semibold text-gray-900 mb-3">Subscriptions</h2>
                @if($company->clientSubscriptions->isEmpty())
                    <p class="text-sm text-gray-500">No subscriptions found.</p>
                @else
                    <ul class="divide-y divide-gray-200 text-sm">
                        @foreach ($company->clientSubscriptions as $sub)
                            @php
                                $provision = app(\App\Services\SubscriptionService::class)->getProvisionLabel($sub);
                            @endphp
                            <li class="py-2">
                                <div class="text-gray-900 font-medium">{{ optional($sub->plan)->plan_name ?? 'Unknown plan' }}</div>
                                <div class="text-gray-500 text-xs">
                                    Status: {{ $sub->status }} · Expires: {{ optional($sub->expires_at)->format('Y-m-d') }}
                                    @if($provision) · <span class="text-purple-700">{{ $provision }}</span> @endif
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>

            {{-- MENetZero 2.0 (Phase 6) — per-company theme opt-in.

                 Deliberately the ONLY theme UI in the product. Requirement:
                 normal users must never see a "Switch Theme" option, so this
                 lives in super-admin company administration, not in any
                 company- or consultant-facing screen. --}}
            @php
                $mnzCurrentTheme = $company->themePreference();
                $mnzDefaultLabel = config('themes.themes.' . config('themes.default') . '.label', config('themes.default'));
            @endphp
            <div class="bg-white shadow rounded-lg p-4 border-l-4 border-emerald-500">
                <h2 class="text-md font-semibold text-gray-900 mb-1">Portal theme</h2>
                <p class="text-xs text-gray-500 mb-4">
                    Moves this company to the MENetZero 2.0 look. Appearance only — routes,
                    permissions and plan gating are unchanged. Reversible at any time.
                </p>

                @if($mnzCurrentTheme === null)
                    <p class="text-xs text-gray-600 mb-3">
                        Currently following the system default (<strong>{{ $mnzDefaultLabel }}</strong>).
                    </p>
                @else
                    <p class="text-xs text-gray-600 mb-3">
                        Pinned to <strong>{{ config('themes.themes.' . $mnzCurrentTheme . '.label', $mnzCurrentTheme) }}</strong>.
                    </p>
                @endif

                <form action="{{ route('admin.companies.theme', $company->id) }}" method="POST" class="flex flex-wrap items-end gap-3 text-sm">
                    @csrf
                    <div>
                        <label class="block font-medium text-gray-700 mb-1">Theme</label>
                        <select name="theme" class="border rounded-lg px-3 py-2">
                            <option value="default" {{ $mnzCurrentTheme === null ? 'selected' : '' }}>
                                Follow default ({{ $mnzDefaultLabel }})
                            </option>
                            @foreach(config('themes.themes', []) as $mnzKey => $mnzTheme)
                                <option value="{{ $mnzKey }}" {{ $mnzCurrentTheme === $mnzKey ? 'selected' : '' }}>
                                    {{ $mnzTheme['label'] ?? $mnzKey }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg px-4 py-2">
                        Save theme
                    </button>
                </form>
            </div>

            @if($company->isConsultantOrg())
            <div class="bg-white shadow rounded-lg p-4 border-l-4 border-indigo-500">
                <h2 class="text-md font-semibold text-gray-900 mb-1">Assign consultant agency pack</h2>
                <p class="text-xs text-gray-500 mb-4">Admin-approved grant — no payment required. Replaces the consultant org&rsquo;s active pack for the selected contract year.</p>
                @if($consultantPacks->isEmpty())
                    <p class="text-sm text-gray-500">No active consultant packs found. Run the consultant agency plan migrations first.</p>
                @else
                <form action="{{ route('admin.companies.assign-package', $company->id) }}" method="POST" class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                    @csrf
                    <div>
                        <label class="block font-medium text-gray-700 mb-1">Pack</label>
                        <select name="plan_id" required class="w-full border rounded-lg px-3 py-2">
                            @foreach($consultantPacks as $plan)
                                <option value="{{ $plan->id }}">{{ $plan->plan_name }} (AED {{ number_format($plan->price_annual, 0) }}/yr)</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block font-medium text-gray-700 mb-1">Contract year</label>
                        <input type="number" name="contract_year" value="{{ now()->year }}" min="2024" max="2100" required class="w-full border rounded-lg px-3 py-2">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block font-medium text-gray-700 mb-1">Reason / approval note</label>
                        <input type="text" name="note" required placeholder="e.g. Launch partner, agency pilot" class="w-full border rounded-lg px-3 py-2">
                    </div>
                    <div class="sm:col-span-2">
                        <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700" onclick="return confirm('Assign this consultant pack at no charge?')">
                            Assign agency pack
                        </button>
                    </div>
                </form>
                @endif
            </div>
            @else
            <div class="bg-white shadow rounded-lg p-4 border-l-4 border-purple-500">
                <h2 class="text-md font-semibold text-gray-900 mb-1">Assign complimentary plan</h2>
                <p class="text-xs text-gray-500 mb-4">Special cases only. Client sees full plan features with a &ldquo;Complimentary&rdquo; label — no payment or billing history.</p>
                <form action="{{ route('admin.companies.assign-package', $company->id) }}" method="POST" class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                    @csrf
                    <div>
                        <label class="block font-medium text-gray-700 mb-1">Plan</label>
                        <select name="plan_id" required class="w-full border rounded-lg px-3 py-2">
                            @foreach($grantPlans as $plan)
                                <option value="{{ $plan->id }}">{{ $plan->plan_name }} (AED {{ number_format($plan->price_annual, 0) }}/yr)</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block font-medium text-gray-700 mb-1">Duration (months)</label>
                        <input type="number" name="duration_months" value="12" min="1" max="60" required class="w-full border rounded-lg px-3 py-2">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block font-medium text-gray-700 mb-1">Reason (shown to client)</label>
                        <input type="text" name="note" required placeholder="e.g. Pilot consultant, NGO programme, launch promo" class="w-full border rounded-lg px-3 py-2">
                    </div>
                    <div class="sm:col-span-2">
                        <button type="submit" class="bg-purple-600 text-white px-4 py-2 rounded-lg hover:bg-purple-700" onclick="return confirm('Assign this plan at no charge?')">
                            Assign complimentary access
                        </button>
                    </div>
                </form>
            </div>
            @endif

            @if(isset($packageAssignments) && $packageAssignments->isNotEmpty())
            <div class="bg-white shadow rounded-lg p-4">
                <h2 class="text-md font-semibold text-gray-900 mb-3">Admin package assignment history</h2>
                <ul class="divide-y divide-gray-200 text-sm">
                    @foreach($packageAssignments as $assignment)
                        <li class="py-2">
                            <div class="text-gray-900 font-medium">
                                {{ optional($assignment->plan)->plan_name ?? 'Unknown plan' }}
                                <span class="text-xs font-normal text-gray-500">({{ $assignment->target_type }})</span>
                            </div>
                            <div class="text-gray-500 text-xs">
                                {{ $assignment->status }} ·
                                {{ $assignment->contract_year ? 'Year '.$assignment->contract_year : $assignment->duration_months.' months' }} ·
                                by {{ optional($assignment->admin)->name ?? 'admin #'.$assignment->admin_id }} ·
                                {{ $assignment->created_at->format('Y-m-d H:i') }}
                            </div>
                            @if($assignment->note)<div class="text-gray-600 text-xs mt-0.5">&ldquo;{{ $assignment->note }}&rdquo;</div>@endif
                        </li>
                    @endforeach
                </ul>
            </div>
            @endif

            <div class="bg-white shadow rounded-lg p-4">
                <h2 class="text-md font-semibold text-gray-900 mb-3">Locations</h2>
                @if($company->locations->isEmpty())
                    <p class="text-sm text-gray-500">No locations defined for this company.</p>
                @else
                    <ul class="divide-y divide-gray-200 text-sm">
                        @foreach ($company->locations as $location)
                            <li class="py-2 flex items-center justify-between">
                                <div>
                                    <div class="text-gray-900">{{ $location->name }}</div>
                                    <div class="text-gray-500 text-xs">
                                        {{ $location->city }}{{ $location->country ? ', '.$location->country : '' }}
                                    </div>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>

    {{-- MANAGED CLIENTS. Uses consultant_id alone -- the same test
         OrganisationDeletionService::blockerFor() applies -- so what the delete
         blocker counts is exactly what is listed here. An earlier version
         filtered on is_direct_client too and produced a company that blocked
         the delete while appearing nowhere. --}}
    @php
        $managedClients = \App\Models\Company::where('consultant_id', $company->id)
            ->withCount(['carbonEmissions', 'locations'])
            ->orderBy('name')
            ->get();
    @endphp

    @if($company->company_type === 'consultant' || $managedClients->isNotEmpty())
        <div class="bg-white shadow rounded-lg mt-8">
            <div class="px-5 py-4 border-b border-gray-200">
                <h2 class="text-lg font-medium text-gray-900">
                    Client companies ({{ $managedClients->count() }})
                </h2>
                <p class="text-xs text-gray-500 mt-1">Companies this agency manages.</p>
            </div>

            @if($managedClients->isEmpty())
                <div class="p-5 text-sm text-gray-500">No client companies.</div>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="text-left text-xs text-gray-500 border-b border-gray-200">
                                <th class="px-5 py-2">Company</th>
                                <th class="px-5 py-2">Type</th>
                                <th class="px-5 py-2">Data</th>
                                <th class="px-5 py-2">Registered</th>
                                <th class="px-5 py-2 text-right">Open</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($managedClients as $mc)
                                <tr class="border-b border-gray-100">
                                    <td class="px-5 py-2">
                                        <div class="font-medium text-gray-900">{{ $mc->name }}</div>
                                        <div class="text-xs text-gray-500">{{ $mc->email ?: '—' }}</div>
                                    </td>
                                    <td class="px-5 py-2">
                                        {{-- is_direct_client separates a referred customer who pays
                                             us directly from a workspace the agency runs. Both point
                                             at this agency, so both block its deletion. --}}
                                        @if($mc->is_direct_client)
                                            <span class="text-xs px-2 py-0.5 rounded-full bg-blue-50 text-blue-700">Direct (referred)</span>
                                        @else
                                            <span class="text-xs px-2 py-0.5 rounded-full bg-gray-100 text-gray-600">Managed</span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-2 text-xs text-gray-600">
                                        {{ $mc->carbon_emissions_count }} entries · {{ $mc->locations_count }} sites
                                    </td>
                                    <td class="px-5 py-2 text-gray-600">{{ $mc->created_at?->format('d M Y') ?? '—' }}</td>
                                    <td class="px-5 py-2 text-right">
                                        <a href="{{ route('admin.companies.show', ['id' => $mc->id, 'from' => 'agency:' . $company->id]) }}" class="text-purple-600 hover:underline">View</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    @endif

    {{-- ACTIVITY. last_login_* are written by RecordSuccessfulLogin on every
         successful sign-in (all three guards). Null means the account has not
         signed in since login tracking was added, NOT that it never has --
         the columns start empty for everyone. --}}
    @php
        $lastLogin = $company->users->max('last_login_at');
        $lastLogin = $lastLogin ? \Illuminate\Support\Carbon::parse($lastLogin) : null;
        $idleDays = $lastLogin ? (int) $lastLogin->diffInDays(now()) : null;
        $lastEntry = $company->carbonEmissions()->max('created_at');
        $lastEntry = $lastEntry ? \Illuminate\Support\Carbon::parse($lastEntry) : null;
    @endphp

    <div class="bg-white shadow rounded-lg mt-8">
        <div class="px-5 py-4 border-b border-gray-200">
            <h2 class="text-lg font-medium text-gray-900">Activity</h2>
        </div>
        <div class="p-5">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-6 text-sm mb-6">
                <div>
                    <div class="text-xs text-gray-500">Last login</div>
                    <div class="font-semibold {{ $idleDays !== null && $idleDays > 30 ? 'text-amber-600' : 'text-gray-900' }}">
                        {{ $lastLogin ? $lastLogin->format('d M Y') : 'Never' }}
                    </div>
                    @if($idleDays !== null)
                        <div class="text-xs text-gray-500">{{ $idleDays === 0 ? 'today' : $idleDays . ' days ago' }}</div>
                    @endif
                </div>
                <div>
                    <div class="text-xs text-gray-500">Registered</div>
                    <div class="font-semibold text-gray-900">{{ $company->created_at?->format('d M Y') ?? '—' }}</div>
                </div>
                <div>
                    <div class="text-xs text-gray-500">Emission entries</div>
                    <div class="font-semibold text-gray-900">{{ $company->carbonEmissions()->count() }}</div>
                </div>
                <div>
                    <div class="text-xs text-gray-500">Last data entered</div>
                    <div class="font-semibold text-gray-900">{{ $lastEntry ? $lastEntry->format('d M Y') : 'Never' }}</div>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs text-gray-500 border-b border-gray-200">
                            <th class="py-2">User</th>
                            <th class="py-2">Last login</th>
                            <th class="py-2">From</th>
                            <th class="py-2 text-right">Logins</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($company->users as $u)
                            <tr class="border-b border-gray-100">
                                <td class="py-2">
                                    <div class="font-medium text-gray-900">{{ $u->name }}</div>
                                    <div class="text-xs text-gray-500">{{ $u->email }}</div>
                                </td>
                                <td class="py-2 text-gray-600">
                                    {{ $u->last_login_at ? \Illuminate\Support\Carbon::parse($u->last_login_at)->format('d M Y H:i') : 'Never' }}
                                </td>
                                <td class="py-2 text-gray-500 font-mono text-xs">{{ $u->last_login_ip ?: '—' }}</td>
                                <td class="py-2 text-right text-gray-600">{{ $u->login_count ?? 0 }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- DANGER ZONE. Typed-name confirmation rather than a JS confirm():
         this erases an organisation and every record cascading from it, and a
         stray Enter key must not be able to do that. The button stays disabled
         until the typed value matches exactly. --}}
    @php
        $deletionBlocker = app(\App\Services\OrganisationDeletionService::class)->blockerFor($company);
    @endphp

    <div class="bg-white shadow rounded-lg border border-red-200 mt-8">
        <div class="px-5 py-4 border-b border-red-100">
            <h2 class="text-lg font-medium text-red-700">Danger zone</h2>
        </div>
        <div class="p-5">
            @if($deletionBlocker)
                <p class="text-sm text-gray-700">{{ $deletionBlocker }}</p>
            @else
                <p class="text-sm text-gray-700 mb-1">
                    Permanently delete <strong>{{ $company->name }}</strong> and everything belonging to it —
                    emissions data, reports, disclosures, locations, users, subscriptions and invoices.
                </p>
                <p class="text-xs text-red-600 mb-4">This cannot be undone. There is no recovery short of a database backup.</p>

                <form method="POST" action="{{ route('admin.companies.destroy', $company->id) }}"
                      class="flex flex-wrap items-end gap-3" id="delete-company-form">
                    @csrf
                    @method('DELETE')
                    {{-- Keeps the origin across the redirect, so deleting from an
                         agency's client list returns to that agency. --}}
                    <input type="hidden" name="from" value="{{ request()->query('from') }}">
                    <div>
                        <label for="confirm_name" class="block text-xs text-gray-600 mb-1">
                            Type <span class="font-mono font-semibold">{{ $company->name }}</span> to confirm
                        </label>
                        <input type="text" name="confirm_name" id="confirm_name" autocomplete="off"
                               data-expected="{{ $company->name }}"
                               class="w-80 px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    </div>
                    <button type="submit" id="delete-company-btn" disabled
                            class="px-4 py-2 bg-red-600 text-white text-sm font-semibold rounded-lg disabled:opacity-40 disabled:cursor-not-allowed">
                        Delete permanently
                    </button>
                </form>

                <script>
                    (function () {
                        var input = document.getElementById('confirm_name');
                        var btn = document.getElementById('delete-company-btn');
                        if (!input || !btn) { return; }
                        input.addEventListener('input', function () {
                            btn.disabled = input.value.trim() !== input.dataset.expected.trim();
                        });
                    })();
                </script>
            @endif
        </div>
    </div>

    @else
        <p class="text-gray-500 text-sm">Company data not available.</p>
    @endisset
@endsection


