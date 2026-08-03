@extends('consultant.layouts.app')

@section('title', 'Request entities')
@section('page-title', 'Request entities')

@section('content')
@php
    $isTrial = $subscription?->isFreeTrial() ?? false;
    $currentUsed = (int) ($slotSummary['used'] ?? 0);
    $currentLimit = $slotSummary['limit'] ?? '—';
    $profiles = [
        'standard' => [
            'name' => 'Standard',
            'summary' => 'Default paid profile per managed client',
            'features' => [
                'Scope 1 & 2 data + bulk import',
                'Up to 5 sites per entity',
                'Clean GHG / MOCCAE / Excel / IEQT',
                'Not full ESG suite by default',
            ],
        ],
        'enterprise' => [
            'name' => 'Enterprise',
            'summary' => 'Custom / white-label for complex deployments',
            'features' => [
                'Everything negotiable (sites, seats, branding)',
                'White-label report covers & custom workflows',
                'MENetZero invoices the consultant practice',
                'Quoted offline — no public list price',
            ],
        ],
    ];
    $selectedProfile = old('plan_profile', old('wants_enterprise') ? 'enterprise' : 'standard');
@endphp

<div class="w-full max-w-5xl">
    <div class="mb-6">
        <a href="{{ route('consultant.dashboard') }}" class="text-sm text-brand hover:underline">&larr; Dashboard</a>
        <h1 class="text-3xl font-bold text-gray-900 mt-2">Request managed-client entities</h1>
        <p class="mt-2 text-gray-600">
            Same pattern as company <em>Request a package</em>: choose a profile by capability, then how many managed clients you need.
            Pricing is confirmed offline — nothing checkoutable here.
        </p>
        @if($subscription)
            <p class="mt-2 text-sm text-gray-500">
                Current: {{ $subscription->plan?->plan_name }} · {{ $currentUsed }}/{{ $currentLimit }} managed clients · contract year {{ $contractYear }}
            </p>
        @endif
        @if($isTrial)
            <p class="mt-1 text-xs text-gray-500">You’re on Free (1 client, watermarked trials). This request is for paid capacity.</p>
        @endif
    </div>

    @if(session('success'))
        <div class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('success') }}</div>
    @endif
    @if(session('info'))
        <div class="mb-4 rounded-lg border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-800">{{ session('info') }}</div>
    @endif
    @if($errors->any())
        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
            <ul class="list-disc pl-4">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('consultant.packs.request-entities') }}" method="POST" class="space-y-6">
        @csrf

        <div class="grid sm:grid-cols-2 gap-4">
            @foreach($profiles as $code => $pkg)
                <label class="relative flex flex-col h-full rounded-xl border border-gray-200 bg-white p-5 cursor-pointer hover:border-teal-400 has-[:checked]:border-teal-600 has-[:checked]:ring-2 has-[:checked]:ring-teal-500/30">
                    <input
                        type="radio"
                        name="plan_profile"
                        value="{{ $code }}"
                        class="sr-only"
                        @checked($selectedProfile === $code)
                        required
                    >
                    <span class="text-lg font-bold text-gray-900">{{ $pkg['name'] }}</span>
                    <span class="text-xs text-gray-500 mt-1 mb-3">{{ $pkg['summary'] }}</span>
                    <ul class="space-y-1.5 text-sm text-gray-600 flex-1">
                        @foreach($pkg['features'] as $feat)
                            <li class="flex gap-2"><span class="text-teal-600">✓</span> {{ $feat }}</li>
                        @endforeach
                    </ul>
                </label>
            @endforeach
        </div>

        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <label for="entity_count" class="block text-sm font-semibold text-gray-900 mb-2">
                How many managed clients (entities) do you need?
            </label>
            <p class="text-xs text-gray-500 mb-3">
                One entity = one client workspace under this profile.
            </p>
            <input
                type="number"
                id="entity_count"
                name="entity_count"
                min="1"
                max="500"
                required
                value="{{ old('entity_count', max(1, $currentUsed)) }}"
                class="w-full max-w-xs rounded-lg border-gray-300 text-sm focus:border-teal-500 focus:ring-teal-500"
            >
        </div>

        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <h2 class="text-sm font-semibold text-gray-900 mb-3">Optional extras</h2>
            <p class="text-xs text-gray-500 mb-3">Tick only if needed — confirmed when quoting.</p>
            <div class="grid sm:grid-cols-2 gap-2">
                <label class="flex items-start gap-2 text-sm text-gray-700">
                    <input
                        type="checkbox"
                        name="needs_sites_over_5"
                        value="1"
                        class="mt-1 rounded border-gray-300 text-teal-600 focus:ring-teal-500"
                        @checked(old('needs_sites_over_5'))
                    >
                    <span>Some clients need <strong>more than 5 sites</strong> per entity</span>
                </label>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <label for="message" class="block text-sm font-semibold text-gray-900 mb-2">Notes for MENetZero</label>
            <textarea
                id="message"
                name="message"
                rows="4"
                class="w-full rounded-lg border-gray-300 text-sm focus:border-teal-500 focus:ring-teal-500"
                placeholder="Industries, reporting years, urgency, or clients that need &gt;5 sites…"
            >{{ old('message') }}</textarea>
        </div>

        <div class="flex flex-wrap gap-3">
            <button type="submit" class="px-5 py-2.5 bg-orange-600 text-white text-sm font-semibold rounded-lg hover:bg-orange-700">
                Submit request
            </button>
            <a href="{{ route('consultant.clients.index') }}" class="px-5 py-2.5 border border-gray-300 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50">
                Cancel
            </a>
        </div>
    </form>

    @if(isset($recentRequests) && $recentRequests->isNotEmpty())
        <div class="mt-10">
            <h2 class="text-sm font-semibold text-gray-900 mb-3">Recent requests</h2>
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden text-sm">
                <table class="min-w-full divide-y divide-gray-100">
                    <thead class="bg-gray-50 text-left text-xs text-gray-500">
                        <tr>
                            <th class="px-4 py-2">Date</th>
                            <th class="px-4 py-2">Managed clients</th>
                            <th class="px-4 py-2">Profile</th>
                            <th class="px-4 py-2">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($recentRequests as $req)
                            <tr>
                                <td class="px-4 py-2 text-gray-600">{{ $req->created_at->format('d M Y') }}</td>
                                <td class="px-4 py-2">{{ $req->entity_count }}</td>
                                <td class="px-4 py-2 text-xs text-gray-600">
                                    {{ $req->wants_enterprise ? 'Enterprise' : 'Standard' }}
                                    @if($req->needs_sites_over_5) · &gt;5 sites @endif
                                </td>
                                <td class="px-4 py-2">{{ ucfirst($req->status) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
@endsection
