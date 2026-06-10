@extends('partner.layouts.app')

@section('title', 'Partner Dashboard')

@section('content')
<h1 class="text-2xl font-bold text-gray-900 mb-1">{{ $partner->name }}</h1>
<p class="text-sm text-gray-600 mb-6">Agency partner hub — manage client workspaces</p>

<div class="grid sm:grid-cols-4 gap-4 mb-8">
    <div class="bg-white border border-gray-200 rounded-xl p-5">
        <div class="text-xs text-gray-500 uppercase tracking-wide">Slots used</div>
        <div class="mt-1 text-2xl font-bold text-gray-900">{{ $slotSummary['used'] }} / {{ $slotSummary['limit'] }}</div>
    </div>
    <div class="bg-white border border-gray-200 rounded-xl p-5">
        <div class="text-xs text-gray-500 uppercase tracking-wide">Slots remaining</div>
        <div class="mt-1 text-2xl font-bold text-indigo-700">{{ $slotSummary['remaining'] }}</div>
    </div>
    <div class="bg-white border border-gray-200 rounded-xl p-5">
        <div class="text-xs text-gray-500 uppercase tracking-wide">Contract year</div>
        <div class="mt-1 text-2xl font-bold text-gray-900">{{ $slotSummary['contract_year'] ?? '—' }}</div>
    </div>
    <div class="bg-white border border-gray-200 rounded-xl p-5">
        <div class="text-xs text-gray-500 uppercase tracking-wide">Pack expires</div>
        <div class="mt-1 text-lg font-semibold text-gray-900">
            @if($slotSummary['expires_at'])
                {{ \Carbon\Carbon::parse($slotSummary['expires_at'])->format('d M Y') }}
            @else
                —
            @endif
        </div>
    </div>
</div>

@if(!$subscription)
    <div class="bg-amber-50 border border-amber-200 rounded-xl p-6 mb-6 text-sm text-amber-900">
        <strong>No active agency pack.</strong> Purchase a Partner 5/10/25/50 pack to add managed clients.
        <span class="text-amber-700">(Online checkout — P19)</span>
    </div>
@else
    <div class="bg-indigo-50 border border-indigo-200 rounded-xl p-4 mb-6 text-sm text-indigo-900">
        Active pack: <strong>{{ $subscription->plan?->plan_name ?? 'Partner pack' }}</strong>
        · Contract {{ $subscription->contract_year }}
    </div>
@endif

<div class="flex items-center justify-between mb-4">
    <h2 class="text-lg font-semibold text-gray-900">Active clients</h2>
    @if($slotSummary['remaining'] > 0 && $subscription)
        <a href="{{ route('partner.clients.create') }}" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg">Add client</a>
    @endif
</div>

@if($activeClients->isEmpty())
    <div class="bg-white border border-gray-200 rounded-xl p-8 text-center text-gray-500 text-sm">
        No active managed clients yet.
        @if($subscription && $slotSummary['remaining'] > 0)
            <a href="{{ route('partner.clients.create') }}" class="text-indigo-600 hover:underline">Add your first client</a>
        @endif
    </div>
@else
    <div class="bg-white border border-gray-200 rounded-xl overflow-hidden">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50 text-left text-gray-500">
                <tr>
                    <th class="px-4 py-3 font-medium">Client</th>
                    <th class="px-4 py-3 font-medium">PRY</th>
                    <th class="px-4 py-3 font-medium">Status</th>
                    <th class="px-4 py-3 font-medium"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($activeClients as $engagement)
                    <tr>
                        <td class="px-4 py-3">
                            <div class="font-medium text-gray-900">{{ $engagement->display_name ?: $engagement->managedCompany?->name }}</div>
                            @if($engagement->display_name)
                                <div class="text-xs text-gray-500">{{ $engagement->managedCompany?->name }}</div>
                            @endif
                        </td>
                        <td class="px-4 py-3">{{ $engagement->primary_reporting_year }}</td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-0.5 rounded-full text-xs bg-green-100 text-green-800">Active</span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('partner.clients.show', $engagement) }}" class="text-indigo-600 hover:underline">View</a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif

<div class="mt-6">
    <a href="{{ route('partner.clients.index') }}" class="text-sm text-indigo-600 hover:underline">View all clients (including archived)</a>
</div>
@endsection
