@extends('consultant.layouts.app')

@section('title', 'Switch Client Workspace')

@section('content')
<h1 class="text-2xl font-bold text-gray-900 mb-1">Switch client workspace</h1>
<p class="text-sm text-gray-600 mb-8">
    Each section is a package. Filled boxes are clients; empty <span class="font-medium text-gray-800">+</span> boxes add a new client on that package (tied to the next open purchase term).
</p>

@if($acting)
    <div class="cd-callout mb-8">
        <span>Currently in: <strong>{{ $acting->name }}</strong></span>
        <form action="{{ route('consultant.workspace.exit') }}" method="POST">
            @csrf
            <button type="submit" class="btn btn-secondary btn-sm">Exit workspace</button>
        </form>
    </div>
@endif

@if(session('error'))
    <div class="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ session('error') }}</div>
@endif

@forelse($sections as $section)
    @php
        $emptyTargets = $section['empty_seat_targets'] ?? [];
        $purchaseRows = $section['purchase_rows'] ?? [];
        $subtitleParts = [];
        $subtitleParts[] = $section['used'].'/'.$section['slot_limit'].' places used';
        if (!empty($section['expires_label'])) {
            $subtitleParts[] = $section['expires_label'];
        }
        if (!empty($section['client_package_code'])) {
            $subtitleParts[] = $section['client_package_code'];
        }
        if (!empty($section['is_trial'])) {
            $subtitleParts[] = 'Free trial depth';
        } elseif (!empty($section['is_demo'])) {
            $subtitleParts[] = 'Demo / QA only';
        } else {
            $subtitleParts[] = 'Paid capacity package';
        }
    @endphp

    <section class="mb-10">
        <div class="mb-4">
            <h2 class="text-lg font-semibold text-gray-900">
                {{ $section['plan_name'] }}
                @if(!empty($section['is_trial']))
                    <span class="ml-1 align-middle text-[10px] font-semibold uppercase tracking-wide text-teal-700 bg-teal-50 border border-teal-100 rounded px-1.5 py-0.5">Free</span>
                @endif
                @if(!empty($section['is_demo']))
                    <span class="ml-1 align-middle text-[10px] font-semibold uppercase tracking-wide text-amber-800 bg-amber-50 border border-amber-100 rounded px-1.5 py-0.5">Demo</span>
                @endif
            </h2>
            <p class="text-sm text-gray-500 mt-0.5">{{ implode(' · ', $subtitleParts) }}</p>
            @if(count($purchaseRows) > 1)
                <ul class="mt-2 flex flex-wrap gap-2">
                    @foreach($purchaseRows as $row)
                        <li class="text-[11px] text-gray-600 bg-gray-50 border border-gray-200 rounded-md px-2 py-1">
                            {{ $row['used'] }}/{{ $row['slot_limit'] }} seats
                            @if(!empty($row['expires_at'])) · exp {{ $row['expires_at'] }} @endif
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-3">
            @foreach($section['engagements'] as $engagement)
                @php $client = $engagement->managedCompany; @endphp
                <div class="bg-white border border-gray-200 rounded-xl p-4 flex flex-col min-h-[9.5rem] shadow-sm">
                    <div class="flex-1">
                        <div class="w-9 h-9 rounded-lg bg-teal-50 text-teal-700 flex items-center justify-center mb-3" aria-hidden="true">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                        </div>
                        <div class="font-semibold text-gray-900 text-sm leading-snug line-clamp-2">
                            {{ $engagement->display_name ?: $client?->name }}
                        </div>
                        @if($engagement->display_name && $client?->name)
                            <div class="text-xs text-gray-500 mt-0.5 line-clamp-1">{{ $client->name }}</div>
                        @endif
                        <div class="text-xs text-gray-500 mt-1">PRY {{ $engagement->primary_reporting_year }}</div>
                    </div>
                    <div class="mt-3">
                        @if($acting && (int) $acting->id === (int) $client?->id)
                            <span class="block text-center text-xs font-medium text-green-700 py-2">Active workspace</span>
                        @else
                            <form action="{{ route('consultant.workspace.enter', $engagement) }}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-primary w-full text-xs py-2">Open</button>
                            </form>
                        @endif
                    </div>
                </div>
            @endforeach

            @foreach($emptyTargets as $subscriptionId)
                <a
                    href="{{ route('consultant.clients.create', ['subscription' => $subscriptionId]) }}"
                    class="group bg-white border-2 border-dashed border-gray-200 hover:border-teal-400 hover:bg-teal-50/40 rounded-xl p-4 flex flex-col items-center justify-center min-h-[9.5rem] text-center transition-colors"
                >
                    <span class="w-10 h-10 rounded-full bg-gray-50 group-hover:bg-teal-100 text-gray-400 group-hover:text-teal-700 flex items-center justify-center text-2xl font-light leading-none mb-2 transition-colors" aria-hidden="true">+</span>
                    <span class="text-sm font-medium text-gray-600 group-hover:text-teal-800">Add client</span>
                    <span class="text-xs text-gray-400 mt-0.5">Open seat</span>
                </a>
            @endforeach
        </div>
    </section>
@empty
    <div class="bg-white border border-gray-200 rounded-xl p-8 text-center text-gray-500 text-sm mb-6">
        No package capacity yet.
        <a href="{{ route('consultant.packs.index') }}" class="text-brand hover:underline">Request clients</a>
        or
        <a href="{{ route('consultant.clients.create') }}" class="text-brand hover:underline">add a Free client</a>.
    </div>
@endforelse

@if(isset($orphanEngagements) && $orphanEngagements->isNotEmpty())
    <section class="mb-10">
        <div class="mb-4">
            <h2 class="text-lg font-semibold text-gray-900">Other active clients</h2>
            <p class="text-sm text-gray-500 mt-0.5">Capacity row expired or unavailable — still openable until archived.</p>
        </div>
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-3">
            @foreach($orphanEngagements as $engagement)
                @php $client = $engagement->managedCompany; @endphp
                <div class="bg-white border border-amber-200 rounded-xl p-4 flex flex-col min-h-[9.5rem] shadow-sm">
                    <div class="flex-1">
                        <div class="font-semibold text-gray-900 text-sm leading-snug">
                            {{ $engagement->display_name ?: $client?->name }}
                        </div>
                        <div class="text-xs text-gray-500 mt-1">PRY {{ $engagement->primary_reporting_year }}</div>
                    </div>
                    <div class="mt-3">
                        @if($acting && (int) $acting->id === (int) $client?->id)
                            <span class="block text-center text-xs font-medium text-green-700 py-2">Active workspace</span>
                        @else
                            <form action="{{ route('consultant.workspace.enter', $engagement) }}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-primary w-full text-xs py-2">Open</button>
                            </form>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </section>
@endif

<div class="mt-2 flex flex-wrap gap-4 text-sm">
    <a href="{{ route('consultant.clients.index') }}" class="text-brand hover:underline">Managed clients</a>
    <a href="{{ route('consultant.packs.index') }}" class="text-brand hover:underline">Request clients</a>
    <a href="{{ route('consultant.dashboard') }}" class="text-brand hover:underline">← Consultant dashboard</a>
</div>
@endsection
