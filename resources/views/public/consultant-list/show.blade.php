@extends('layouts.public')

@section('title', $consultant['company_name'] . ' — Consultant Directory')

@section('content')
<section class="py-12 px-4 mkt-section-bg">
    <div class="max-w-6xl mx-auto">
        <a href="{{ route('consultant-list.index') }}" class="text-sm text-teal-600 hover:underline mb-6 inline-block">← Back to directory</a>
        <div class="grid lg:grid-cols-3 gap-8 items-start">
            {{-- Profile --}}
            <div class="lg:col-span-2">
                <div class="mkt-feature-card">
                    @if($consultant['is_featured'])
                        <span class="mkt-pill mb-4">Featured consultant</span>
                    @endif
                    <h1 class="text-3xl font-bold text-gray-900 mb-2">{{ $consultant['company_name'] }}</h1>
                    <div class="flex flex-wrap gap-3 text-sm text-gray-500 mb-6">
                        @if($consultant['experience_years'])
                            <span>{{ $consultant['experience_years'] }}+ years experience</span>
                        @endif
                        @if($consultant['has_moccae_experience'])
                            <span class="text-teal-700 font-medium">MOCCAE experience</span>
                        @endif
                    </div>

                    @if(!empty($consultant['emirates']))
                        <div class="mb-4">
                            <h2 class="text-xs font-semibold uppercase tracking-wider text-gray-400 mb-2">Emirates covered</h2>
                            <div class="flex flex-wrap gap-2">
                                @foreach($consultant['emirates'] as $em)
                                    <span class="text-sm px-3 py-1 bg-gray-100 text-gray-700 rounded-full">{{ $em }}</span>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @if(!empty($consultant['specialties']))
                        <div class="mb-4">
                            <h2 class="text-xs font-semibold uppercase tracking-wider text-gray-400 mb-2">Specialties</h2>
                            <div class="flex flex-wrap gap-2">
                                @foreach($consultant['specialties'] as $spec)
                                    <span class="text-sm px-3 py-1 bg-teal-50 text-teal-800 rounded-full">{{ $spec }}</span>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @if(!empty($consultant['languages']))
                        <div class="mb-6">
                            <h2 class="text-xs font-semibold uppercase tracking-wider text-gray-400 mb-2">Languages</h2>
                            <p class="text-sm text-gray-600">{{ implode(', ', $consultant['languages']) }}</p>
                        </div>
                    @endif

                    @if($consultant['bio'])
                        <div>
                            <h2 class="text-xs font-semibold uppercase tracking-wider text-gray-400 mb-2">About</h2>
                            <p class="text-gray-600 leading-relaxed whitespace-pre-line">{{ $consultant['bio'] }}</p>
                        </div>
                    @endif
                </div>

                <p class="text-xs text-gray-400 mt-4">
                    Phone and email are not published. MenetZero routes introduction requests to protect consultant privacy.
                </p>
            </div>

            {{-- Lead form --}}
            <div class="mkt-feature-card lg:sticky lg:top-24">
                <h2 class="text-xl font-bold text-gray-900 mb-1">Request an introduction</h2>
                <p class="text-sm text-gray-500 mb-6">
                    Share your details and we will pass this lead to the consultant. They typically respond within 2 business days.
                </p>

                <form action="{{ route('consultant-list.inquire', $consultant['id']) }}" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <label for="requester_name" class="block text-sm font-medium text-gray-700 mb-1">Your name *</label>
                        <input type="text" name="requester_name" id="requester_name" value="{{ old('requester_name') }}" required class="mkt-form-input">
                        @error('requester_name')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="requester_email" class="block text-sm font-medium text-gray-700 mb-1">Email *</label>
                        <input type="email" name="requester_email" id="requester_email" value="{{ old('requester_email') }}" required class="mkt-form-input">
                        @error('requester_email')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="requester_phone" class="block text-sm font-medium text-gray-700 mb-1">Phone / WhatsApp *</label>
                        <input type="tel" name="requester_phone" id="requester_phone" value="{{ old('requester_phone') }}" required class="mkt-form-input" placeholder="+971 …">
                        @error('requester_phone')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="requester_company" class="block text-sm font-medium text-gray-700 mb-1">Company (optional)</label>
                        <input type="text" name="requester_company" id="requester_company" value="{{ old('requester_company') }}" class="mkt-form-input">
                    </div>
                    <div>
                        <label for="message" class="block text-sm font-medium text-gray-700 mb-1">What do you need help with?</label>
                        <textarea name="message" id="message" rows="4" class="mkt-form-input" placeholder="e.g. Scope 1 & 2 inventory for a 50-person SME in Dubai…">{{ old('message') }}</textarea>
                        @error('message')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <button type="submit" class="mkt-btn mkt-btn-primary w-full justify-center">Send introduction request</button>
                </form>

                <p class="text-xs text-gray-400 mt-4 text-center">
                    Already a MenetZero client?
                    <a href="{{ route('login') }}" class="text-teal-600 hover:underline">Sign in</a> for priority consultant access.
                </p>
            </div>
        </div>
    </div>
</section>
@endsection
