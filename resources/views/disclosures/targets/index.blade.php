@extends('layouts.app')

@section('title', 'Reduction Targets - IFRS S2')
@section('page-title', 'Targets & Transition Roadmap')

@section('content')
<div class="w-full" x-data="{ actionRows: 1 }">
    @include('disclosures.partials.header')

    @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg mb-6">{{ session('success') }}</div>
    @endif

    <div class="card mb-6">
        <div class="card-header">
            <h3 class="card-title">Add reduction target</h3>
            <p class="card-subtitle">IFRS S2 §33–36 — targets and transition actions.</p>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('disclosures.s2.targets.store', ['fiscal_year' => $fiscalYear]) }}" class="space-y-4">
                @csrf
                <input type="hidden" name="fiscal_year" value="{{ $fiscalYear }}">
                @include('disclosures.s2.targets._form', ['target' => null, 'prefix' => 'new'])
                <button type="submit" class="btn btn-primary">Save target</button>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Active targets ({{ $targets->count() }})</h3>
        </div>
        <div class="card-body space-y-6">
            @forelse($targets as $target)
                <details class="border border-gray-200 rounded-lg p-4" @if($loop->first) open @endif>
                    <summary class="font-medium text-gray-900 cursor-pointer flex justify-between">
                        <span>{{ $target->name }}</span>
                        <span class="text-xs text-gray-500">{{ $target->target_year }} · {{ \App\Models\ReductionTarget::SCOPE_COVERAGE[$target->scope_coverage] ?? $target->scope_coverage }}</span>
                    </summary>
                    <form method="POST" action="{{ route('disclosures.s2.targets.update', ['reductionTarget' => $target, 'fiscal_year' => $fiscalYear]) }}" class="mt-4 space-y-4">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="fiscal_year" value="{{ $fiscalYear }}">
                        @include('disclosures.s2.targets._form', ['target' => $target, 'prefix' => 'edit-' . $target->id])
                        <button type="submit" class="btn btn-secondary btn-sm">Update target</button>
                    </form>
                    <form method="POST" action="{{ route('disclosures.s2.targets.destroy', ['reductionTarget' => $target, 'fiscal_year' => $fiscalYear]) }}" class="mt-2" onsubmit="return confirm('Remove this target and its actions?')">
                        @csrf
                        @method('DELETE')
                        <input type="hidden" name="fiscal_year" value="{{ $fiscalYear }}">
                        <button type="submit" class="text-sm text-red-600 hover:underline">Delete</button>
                    </form>
                </details>
            @empty
                <p class="text-gray-500 text-sm">No reduction targets defined yet.</p>
            @endforelse
        </div>
    </div>
</div>
@endsection
