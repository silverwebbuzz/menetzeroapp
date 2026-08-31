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
                <p class="card-subtitle">{{ $config['reference'] ?? '' }}@if(!empty($config['description'])) — {{ $config['description'] }}@endif</p>
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
                    'esg_report' => 'disclosures.uae-esg.sections.update',
                    default => 'disclosures.s2.sections.update',
                };
                $formFramework = $framework ?? 'ifrs_s2';
            @endphp
            {{-- Field widths follow the field TYPE from config/disclosure.php
                 (and config/esg_report.php), not a per-field class.

                 A number or a four-option select is never as wide as the row,
                 so a form of them ran as one tall column of near-empty boxes
                 -- GRI 306 Waste is seven of them. Half-width pairs them two
                 to a row; textarea keeps the full row because a paragraph is
                 not a peer of a number.

                 Driving this off 'type' means a field added to the config
                 later gets the right width with no extra markup.

                 DOM order is unchanged, so tab order stays left-to-right then
                 down, and screen readers read the same sequence as before. --}}
            <form method="POST" action="{{ route($updateRoute, ['section' => $section, 'fiscal_year' => $fiscalYear]) }}">
                @csrf
                <input type="hidden" name="fiscal_year" value="{{ $fiscalYear }}">

                <x-field-help :framework="$formFramework" :section="$section" class="mb-5" />

                <div class="grid grid-cols-1 md:grid-cols-2 gap-x-5 gap-y-5">
                @foreach($config['fields'] as $key => $field)
                    @php
                        // Only a textarea needs the whole row.
                        $isWide = ($field['type'] ?? 'text') === 'textarea';
                    @endphp
                    {{-- flex-col + mt-auto on the control keeps inputs in a row
                         aligned even when one field has two lines of help text
                         and its neighbour has none. --}}
                    <div class="flex flex-col {{ $isWide ? 'md:col-span-2' : '' }}">
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            {{ $field['label'] }}
                            @if(!empty($field['required']))
                                <span class="text-red-500">*</span>
                            @endif
                        </label>

                        <x-field-help :framework="$formFramework" :section="$section" :field="$key" class="mb-2 -mt-1" />

                        @if(($field['type'] ?? 'text') === 'textarea')
                            <textarea name="content[{{ $key }}]" rows="4"
                                      class="mt-auto w-full border border-gray-300 rounded-lg px-3 py-2"
                                      @if(!empty($field['required'])) required @endif>{{ old("content.{$key}", $content[$key] ?? '') }}</textarea>
                        @elseif(($field['type'] ?? '') === 'number')
                            <input type="number" step="any" name="content[{{ $key }}]" value="{{ old("content.{$key}", $content[$key] ?? '') }}"
                                   class="mt-auto w-full border border-gray-300 rounded-lg px-3 py-2"
                                   @if(!empty($field['required'])) required @endif>
                        @elseif(($field['type'] ?? '') === 'select')
                            <select name="content[{{ $key }}]" class="mt-auto w-full border border-gray-300 rounded-lg px-3 py-2"
                                    @if(!empty($field['required'])) required @endif>
                                <option value="">— Select —</option>
                                @foreach($field['options'] ?? [] as $opt)
                                    <option value="{{ $opt }}" @selected(old("content.{$key}", $content[$key] ?? '') === $opt)>{{ $opt }}</option>
                                @endforeach
                            </select>
                        @else
                            <input type="text" name="content[{{ $key }}]" value="{{ old("content.{$key}", $content[$key] ?? '') }}"
                                   class="mt-auto w-full border border-gray-300 rounded-lg px-3 py-2"
                                   @if(!empty($field['required'])) required @endif>
                        @endif
                    </div>
                @endforeach
                </div>

                <div class="pt-5">
                    <button type="submit" class="btn btn-primary">Save section</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
