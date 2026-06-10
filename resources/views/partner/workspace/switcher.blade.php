@extends('consultant.layouts.app')

@section('title', 'Switch Client Workspace')

@section('content')
<h1 class="text-2xl font-bold text-gray-900 mb-1">Switch client workspace</h1>
<p class="text-sm text-gray-600 mb-6">Open a managed client to work in their emissions and disclosure UI.</p>

@if($acting)
    <div class="bg-indigo-50 border border-indigo-200 rounded-xl p-4 mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 text-sm">
        <span>Currently in: <strong>{{ $acting->name }}</strong></span>
        <form action="{{ route('consultant.workspace.exit') }}" method="POST">
            @csrf
            <button type="submit" class="px-3 py-1.5 bg-white border border-indigo-300 rounded-lg text-indigo-700 hover:bg-indigo-100">Exit workspace</button>
        </form>
    </div>
@endif

<div class="grid sm:grid-cols-2 gap-4">
    @forelse($engagements as $engagement)
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
                    <button type="submit" class="w-full px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg">Open workspace</button>
                </form>
            @endif
        </div>
    @empty
        <div class="sm:col-span-2 bg-white border border-gray-200 rounded-xl p-8 text-center text-gray-500 text-sm">
            No active clients. <a href="{{ route('consultant.clients.create') }}" class="text-indigo-600 hover:underline">Add a client</a> first.
        </div>
    @endforelse
</div>

<div class="mt-6">
    <a href="{{ route('consultant.dashboard') }}" class="text-sm text-teal-600 hover:underline">← Partner dashboard</a>
</div>
@endsection
