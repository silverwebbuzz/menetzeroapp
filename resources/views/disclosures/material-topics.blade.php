@extends('layouts.app')

@section('title', 'Material Topics - IFRS S1')
@section('page-title', 'Material Sustainability Topics')

@section('content')
<div class="max-w-4xl mx-auto">
    @include('disclosures.partials.header', ['framework' => $framework ?? 'ifrs_s1'])

    @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg mb-6">{{ session('success') }}</div>
    @endif

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Material topic assessment</h3>
            <p class="card-subtitle">IFRS S1 / GRI 3 — identify topics material for {{ $fiscalYear }}.</p>
        </div>
        <div class="card-body">
            @php
                $updateRoute = ($framework ?? 'ifrs_s1') === 'gri'
                    ? 'disclosures.gri.material-topics.update'
                    : 'disclosures.s1.material-topics.update';
            @endphp
            <form method="POST" action="{{ route($updateRoute, ['fiscal_year' => $fiscalYear]) }}" class="space-y-4">
                @csrf
                <input type="hidden" name="fiscal_year" value="{{ $fiscalYear }}">

                @foreach($topics as $key => $topic)
                    <div class="p-4 border border-gray-200 rounded-lg">
                        <div class="flex items-start gap-3">
                            <input type="checkbox" name="topics[{{ $key }}][is_material]" value="1" id="topic-{{ $key }}"
                                   @checked($topic['is_material']) class="mt-1">
                            <div class="flex-1">
                                <label for="topic-{{ $key }}" class="font-medium text-gray-900">{{ $topic['label'] }}</label>
                                @if($topic['gri'])
                                    <span class="text-xs text-gray-500 ml-2">{{ $topic['gri'] }}</span>
                                @endif
                                <textarea name="topics[{{ $key }}][rationale]" rows="2" class="mt-2 w-full border border-gray-300 rounded-lg px-3 py-2 text-sm"
                                          placeholder="{{ $topic['is_material'] ? 'Why this topic is material…' : 'Reason if not material…' }}">{{ $topic['rationale'] }}</textarea>
                            </div>
                        </div>
                    </div>
                @endforeach

                <button type="submit" class="btn btn-primary">Save material topics</button>
            </form>
        </div>
    </div>
</div>
@endsection
