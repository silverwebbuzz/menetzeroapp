@extends('consultant.layouts.app')

@section('title', 'Switch Client Workspace')

@section('content')
@php
    $hasAnyClients = collect($sections)->contains(fn ($s) => $s['engagements']->isNotEmpty())
        || (isset($orphanEngagements) && $orphanEngagements->isNotEmpty());
@endphp

<h1 class="text-2xl font-bold text-gray-900 mb-1">Switch client workspace</h1>
<p class="text-sm text-gray-600 mb-6">
    Workspaces are grouped by package capacity. Open a managed client to work in their emissions and disclosure UI.
</p>

@if($acting)
    <div class="cd-callout mb-6">
        <span>Currently in: <strong>{{ $acting->name }}</strong></span>
        <form action="{{ route('consultant.workspace.exit') }}" method="POST">
            @csrf
            <button type="submit" class="btn btn-secondary btn-sm">Exit workspace</button>
        </form>
    </div>
@endif

@if(session('error'))
    <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ session('error') }}</div>
@endif

@forelse($sections as $section)
    <section class="mb-8">
        <div class="flex flex-wrap items-end justify-between gap-2 mb-3">
            <div>
                <h2 class="text-lg font-semibold text-gray-900">
                    {{ $section['plan_name'] }}
                    @if(!empty($section['is_trial']))
                        <span class="ml-1 text-xs font-medium text-teal-700 bg-teal-50 border border-teal-100 rounded px-1.5 py-0.5">Free</span>
                    @endif
                    @if(!empty($section['is_demo']))
                        <span class="ml-1 text-xs font-medium text-amber-800 bg-amber-50 border border-amber-100 rounded px-1.5 py-0.5">Demo QA</span>
                    @endif
                </h2>
                <p class="text-xs text-gray-500 mt-0.5">
                    {{ $section['used'] }}/{{ $section['slot_limit'] }} places used
                    · {{ $section['remaining'] }} left
                    @if(!empty($section['expires_at'])) · expires {{ $section['expires_at'] }} @endif
                    @if(!empty($section['client_package_code']))
                        · <span class="font-mono">{{ $section['client_package_code'] }}</span>
                    @endif
                </p>
            </div>
            @if($section['remaining'] > 0)
                <a href="{{ route('consultant.clients.create') }}" class="text-xs font-medium text-brand hover:underline">
                    Add client to this package →
                </a>
            @endif
        </div>

        @if($section['engagements']->isEmpty())
            <div class="bg-white border border-dashed border-gray-200 rounded-xl px-5 py-6 text-sm text-gray-500">
                No clients on this package yet.
                @if($section['remaining'] > 0)
                    <a href="{{ route('consultant.clients.create') }}" class="text-brand hover:underline">Add a client</a>
                    and choose this package.
                @endif
            </div>
        @else
            <div class="grid sm:grid-cols-2 gap-4">
                @foreach($section['engagements'] as $engagement)
                    @php $client = $engagement->managedCompany; @endphp
                    <div class="bg-white border border-gray-200 rounded-xl p-5 flex flex-col gap-3">
                        <div>
                            <div class="font-semibold text-gray-900">{{ $engagement->display_name ?: $client?->name }}</div>
                            @if($engagement->display_name)
                                <div class="text-xs text-gray-500">{{ $client?->name }}</div>
                            @endif
                            <div class="text-sm text-gray-600 mt-1">PRY {{ $engagement->primary_reporting_year }}</div>
                        </div>
                        @if($acting && (int) $acting->id === (int) $client?->id)
                            <span class="text-xs font-medium text-green-700">Active workspace</span>
                        @else
                            <form action="{{ route('consultant.workspace.enter', $engagement) }}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-primary w-full">Open workspace</button>
                            </form>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
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
    <section class="mb-8">
        <div class="mb-3">
            <h2 class="text-lg font-semibold text-gray-900">Other active clients</h2>
            <p class="text-xs text-gray-500 mt-0.5">Capacity row expired or unavailable — still openable until archived.</p>
        </div>
        <div class="grid sm:grid-cols-2 gap-4">
            @foreach($orphanEngagements as $engagement)
                @php $client = $engagement->managedCompany; @endphp
                <div class="bg-white border border-amber-200 rounded-xl p-5 flex flex-col gap-3">
                    <div>
                        <div class="font-semibold text-gray-900">{{ $engagement->display_name ?: $client?->name }}</div>
                        <div class="text-sm text-gray-600 mt-1">PRY {{ $engagement->primary_reporting_year }}</div>
                    </div>
                    @if($acting && (int) $acting->id === (int) $client?->id)
                        <span class="text-xs font-medium text-green-700">Active workspace</span>
                    @else
                        <form action="{{ route('consultant.workspace.enter', $engagement) }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-primary w-full">Open workspace</button>
                        </form>
                    @endif
                </div>
            @endforeach
        </div>
    </section>
@endif

@if(!$hasAnyClients && count($sections) > 0)
    <p class="text-sm text-gray-500 mb-4">
        You have capacity but no clients yet —
        <a href="{{ route('consultant.clients.create') }}" class="text-brand hover:underline">add a managed client</a>.
    </p>
@endif

<div class="mt-6 flex flex-wrap gap-4 text-sm">
    <a href="{{ route('consultant.clients.index') }}" class="text-brand hover:underline">Managed clients</a>
    <a href="{{ route('consultant.packs.index') }}" class="text-brand hover:underline">Request clients</a>
    <a href="{{ route('consultant.dashboard') }}" class="text-brand hover:underline">← Consultant dashboard</a>
</div>
@endsection
