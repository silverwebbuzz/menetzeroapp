@extends('layouts.app')

@section('title', $consultant['display_name'])

@section('content')
<div class="max-w-3xl mx-auto">
    <a href="{{ route('client.consultants.index') }}" class="text-sm text-teal-600 hover:underline">&larr; All consultants</a>

    <div class="bg-white border border-gray-200 rounded-xl p-6 mt-4">
        <h1 class="text-2xl font-bold text-gray-900">{{ $consultant['display_name'] }}</h1>

        @if(!empty($consultant['specialties']))
            <div class="flex flex-wrap gap-2 mt-3">
                @foreach($consultant['specialties'] as $spec)
                    <span class="text-xs px-2 py-1 bg-teal-50 text-teal-800 rounded">{{ $spec }}</span>
                @endforeach
            </div>
        @endif

        @if($consultant['has_moccae_experience'])
            <span class="inline-block mt-3 text-xs px-2 py-1 bg-blue-50 text-blue-800 rounded">MOCCAE experience</span>
        @endif

        @if($consultant['bio'])
            <p class="text-gray-700 mt-4 whitespace-pre-line">{{ $consultant['bio'] }}</p>
        @endif

        <dl class="grid sm:grid-cols-2 gap-4 mt-6 text-sm">
            @if(!empty($consultant['emirates']))
                <div>
                    <dt class="text-gray-500">Emirates</dt>
                    <dd class="font-medium">{{ implode(', ', $consultant['emirates']) }}</dd>
                </div>
            @endif
            @if($consultant['experience_years'])
                <div>
                    <dt class="text-gray-500">Experience</dt>
                    <dd class="font-medium">{{ $consultant['experience_years'] }} years</dd>
                </div>
            @endif
            @if($consultant['email'])
                <div>
                    <dt class="text-gray-500">Email</dt>
                    <dd><a href="mailto:{{ $consultant['email'] }}" class="text-teal-600">{{ $consultant['email'] }}</a></dd>
                </div>
            @endif
            @if($consultant['phone'])
                <div>
                    <dt class="text-gray-500">Phone</dt>
                    <dd>{{ $consultant['phone'] }}</dd>
                </div>
            @endif
        </dl>
    </div>

    @if($canBookPack ?? false)
        <div class="bg-teal-50 border border-teal-200 rounded-xl p-6 mt-6">
            <h2 class="font-semibold text-gray-900 mb-2">Book a review pack</h2>
            <p class="text-sm text-gray-600 mb-4">Pay through MenetZero — funds held in escrow until the consultant delivers your review.</p>
            <div class="grid sm:grid-cols-2 gap-3">
                @foreach($consultantAddOns as $addon)
                    <a href="{{ route('client.consultants.checkout', ['consultant' => $consultant['id'], 'pack' => $addon['pack_type']]) }}"
                       class="block bg-white border border-teal-200 rounded-lg p-4 hover:border-teal-400 transition">
                        <div class="font-medium text-gray-900">{{ $addon['name'] }}</div>
                        <div class="text-sm text-gray-600 mt-1">{{ $addon['description'] }}</div>
                        <div class="text-teal-700 font-semibold mt-2">{{ $addon['price'] }}</div>
                    </a>
                @endforeach
            </div>
        </div>
    @endif

    @if($canRequestIntro)
        <div class="bg-white border border-gray-200 rounded-xl p-6 mt-6">
            <h2 class="font-semibold text-gray-900 mb-4">Request introduction (free)</h2>
            <form method="POST" action="{{ route('client.consultants.intro', $consultant['id']) }}" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Interested pack (optional)</label>
                    <select name="pack_type" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                        <option value="">General introduction</option>
                        @foreach($packTypes as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Message</label>
                    <textarea name="message" rows="3" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" placeholder="Briefly describe your reporting year, scope, and what you need reviewed."></textarea>
                </div>
                <button type="submit" class="px-4 py-2 bg-teal-600 hover:bg-teal-700 text-white text-sm font-medium rounded-lg">Send request</button>
            </form>
            <p class="text-xs text-gray-400 mt-3">MenetZero will facilitate the introduction. Consultant review ≠ MOCCAE legal verification unless contracted.</p>
        </div>
    @elseif($level === 'teaser')
        <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 mt-6 text-sm">
            <a href="{{ route('subscriptions.upgrade') }}" class="text-teal-700 font-medium hover:underline">Upgrade to Starter</a> to request introductions.
        </div>
    @endif
</div>
@endsection
