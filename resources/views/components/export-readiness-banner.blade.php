@props([
    'readiness' => null,
    'showInputLink' => true,
])

@php
    $errors = array_merge(
        $readiness['errors'] ?? [],
        session('export_errors', [])
    );
    $warnings = array_merge(
        $readiness['warnings'] ?? [],
        session('export_warnings', [])
    );
    $errors = array_values(array_unique($errors));
    $warnings = array_values(array_unique($warnings));
@endphp

@if(session('error'))
    <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
        {{ session('error') }}
    </div>
@endif

@if(!empty($errors))
    <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-900">
        <p class="font-semibold mb-1">Data gaps — action required</p>
        <ul class="list-disc list-inside space-y-1">
            @foreach($errors as $message)
                <li>{{ $message }}</li>
            @endforeach
        </ul>
        @if($showInputLink)
            <p class="mt-2 text-xs">
                <a href="{{ route('quick-input.index') }}" class="underline font-medium">Go to Input Data</a>
                to add missing electricity or supporting evidence.
            </p>
        @endif
    </div>
@endif

@if(!empty($warnings))
    <div class="mb-4 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-950">
        <p class="font-semibold mb-1">{{ empty($errors) ? 'Data quality notes' : 'Readiness notes' }}</p>
        <ul class="list-disc list-inside space-y-1">
            @foreach($warnings as $message)
                <li>{{ $message }}</li>
            @endforeach
        </ul>
    </div>
@endif
