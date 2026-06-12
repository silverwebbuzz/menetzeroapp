@extends('layouts.app')

@section('title', $config['title'] . ' - IFRS S2')
@section('page-title', $config['title'])

@section('content')
<div class="w-full">
    @include('disclosures.partials.header', ['framework' => $framework ?? 'ifrs_s2'])

    @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg mb-6">{{ session('success') }}</div>
    @endif

    <div class="card">
        <div class="card-header">
            <div>
                <h3 class="card-title">{{ $config['title'] }}</h3>
                <p class="card-subtitle">{{ $config['reference'] }} — {{ $config['description'] }}</p>
            </div>
            @if($record->status === 'complete')
                <span class="text-xs font-medium px-2 py-1 rounded-full bg-green-100 text-green-800">Complete</span>
            @else
                <span class="text-xs font-medium px-2 py-1 rounded-full bg-amber-100 text-amber-800">Draft</span>
            @endif
        </div>
        <div class="card-body">
            @php
                $updateRoute = match ($framework ?? 'ifrs_s2') {
                    'ifrs_s1' => 'disclosures.s1.sections.update',
                    'gri' => 'disclosures.gri.sections.update',
                    default => 'disclosures.s2.sections.update',
                };
            @endphp
            <form method="POST" action="{{ route($updateRoute, ['section' => $section, 'fiscal_year' => $fiscalYear]) }}" class="space-y-5">
                @csrf
                <input type="hidden" name="fiscal_year" value="{{ $fiscalYear }}">

                @foreach($config['fields'] as $key => $field)
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            {{ $field['label'] }}
                            @if(!empty($field['required']))
                                <span class="text-red-500">*</span>
                            @endif
                        </label>

                        @if(($field['type'] ?? 'text') === 'textarea')
                            <textarea name="content[{{ $key }}]" rows="4"
                                      class="w-full border border-gray-300 rounded-lg px-3 py-2"
                                      @if(!empty($field['required'])) required @endif>{{ old("content.{$key}", $content[$key] ?? '') }}</textarea>
                        @elseif(($field['type'] ?? '') === 'number')
                            <input type="number" step="any" name="content[{{ $key }}]" value="{{ old("content.{$key}", $content[$key] ?? '') }}"
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2"
                                   @if(!empty($field['required'])) required @endif>
                        @elseif(($field['type'] ?? '') === 'select')
                            <select name="content[{{ $key }}]" class="w-full border border-gray-300 rounded-lg px-3 py-2"
                                    @if(!empty($field['required'])) required @endif>
                                <option value="">— Select —</option>
                                @foreach($field['options'] ?? [] as $opt)
                                    <option value="{{ $opt }}" @selected(old("content.{$key}", $content[$key] ?? '') === $opt)>{{ $opt }}</option>
                                @endforeach
                            </select>
                        @else
                            <input type="text" name="content[{{ $key }}]" value="{{ old("content.{$key}", $content[$key] ?? '') }}"
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2"
                                   @if(!empty($field['required'])) required @endif>
                        @endif
                    </div>
                @endforeach

                <div class="pt-2">
                    <button type="submit" class="btn btn-primary">Save section</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
