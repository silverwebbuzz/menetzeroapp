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
                @include('disclosures.targets._form', ['target' => null, 'prefix' => 'new'])
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

                    {{-- Progress against actual inventory — same figures as the ESG dashboard. --}}
                    @if($p = ($progress[$target->id] ?? null))
                        @php
                            $badge = match($p['status']['key']) {
                                'achieved', 'on_track' => 'bg-green-100 text-green-800',
                                'off_track', 'missed' => 'bg-red-100 text-red-800',
                                'no_data', 'incomplete' => 'bg-gray-100 text-gray-600',
                                default => 'bg-blue-100 text-blue-800',
                            };
                            $barColor = match($p['status']['key']) {
                                'achieved', 'on_track' => 'bg-green-500',
                                'off_track', 'missed' => 'bg-red-500',
                                default => 'bg-brand-500',
                            };
                        @endphp
                        <div class="mt-3 pt-3 border-t border-gray-100">
                            <div class="flex flex-wrap items-center gap-3 text-sm mb-2">
                                <span class="px-2 py-1 text-xs font-medium rounded-full {{ $badge }}">{{ $p['status']['label'] }}</span>
                                <span class="text-gray-500">
                                    Current {{ $p['current_year'] }}:
                                    <strong class="text-gray-900">{{ $p['current_tco2e'] !== null ? number_format($p['current_tco2e'], 2) . ' tCO₂e' : '—' }}</strong>
                                </span>
                                <span class="text-gray-500">
                                    Target {{ $p['target_year'] }}:
                                    <strong class="text-brand-600">{{ $p['target_tco2e'] !== null ? number_format($p['target_tco2e'], 2) . ' tCO₂e' : '—' }}</strong>
                                </span>
                                @if($p['remaining_tco2e'] !== null)
                                    <span class="text-gray-500">Still to reduce: <strong class="text-gray-900">{{ number_format($p['remaining_tco2e'], 2) }} tCO₂e</strong></span>
                                @endif
                            </div>
                            @if($p['achieved_percent'] !== null)
                                <div class="w-full bg-gray-100 rounded-full h-2">
                                    <div class="{{ $barColor }} h-2 rounded-full" style="width: {{ $p['achieved_percent'] }}%"></div>
                                </div>
                                <div class="text-xs text-gray-500 mt-1">{{ $p['achieved_percent'] }}% of required reduction achieved</div>
                            @endif
                        </div>
                    @endif
                    <form method="POST" action="{{ route('disclosures.s2.targets.update', ['reductionTarget' => $target, 'fiscal_year' => $fiscalYear]) }}" class="mt-4 space-y-4">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="fiscal_year" value="{{ $fiscalYear }}">
                        @include('disclosures.targets._form', ['target' => $target, 'prefix' => 'edit-' . $target->id])
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
