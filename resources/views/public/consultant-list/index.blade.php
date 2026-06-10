@extends('layouts.public')

@section('title', 'Verified Carbon Consultants — MENetZero')

@section('content')
<section class="py-16 px-4 mkt-section-bg">
    <div class="max-w-6xl mx-auto text-center">
        <div class="mkt-tagline">MENetZero verified directory</div>
        <h1 class="text-4xl md:text-5xl font-bold text-gray-900 mb-4">
            Find a carbon consultant
            @if($consultantCount > 0)
                <span class="block text-teal-600 text-3xl md:text-4xl mt-2">{{ $consultantCount }} verified on our platform</span>
            @endif
        </h1>
        <p class="text-lg text-gray-500 max-w-2xl mx-auto">
            Browse admin-approved sustainability consultants in the UAE. Contact details are never shown publicly —
            request an introduction and we route qualified leads through MenetZero.
        </p>
    </div>
</section>

<section class="py-12 px-4">
    <div class="max-w-6xl mx-auto">
        @if(!empty($emirateFilters) || !empty($specialtyFilters))
            <div class="mb-8 flex flex-wrap gap-2 items-center">
                <span class="text-sm font-medium text-gray-500 mr-2">Filter:</span>
                <a href="{{ route('consultant-list.index') }}"
                   class="mkt-filter-pill {{ !$activeEmirate && !$activeSpecialty ? 'active' : '' }}">All</a>
                @foreach($emirateFilters as $emirate)
                    <a href="{{ route('consultant-list.index', ['emirate' => $emirate, 'specialty' => $activeSpecialty]) }}"
                       class="mkt-filter-pill {{ $activeEmirate === $emirate ? 'active' : '' }}">{{ $emirate }}</a>
                @endforeach
                @foreach($specialtyFilters as $spec)
                    <a href="{{ route('consultant-list.index', ['specialty' => $spec, 'emirate' => $activeEmirate]) }}"
                       class="mkt-filter-pill {{ $activeSpecialty === $spec ? 'active' : '' }}">{{ $spec }}</a>
                @endforeach
            </div>
        @endif

        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
            @forelse($consultants as $c)
                <article class="mkt-consultant-card {{ ($c['is_featured'] ?? false) ? 'featured' : '' }}">
                    @if($c['is_featured'] ?? false)
                        <span class="mkt-pill mb-3">Featured</span>
                    @endif
                    <h2 class="text-lg font-bold text-gray-900 mb-1">{{ $c['company_name'] }}</h2>
                    @if($c['experience_years'])
                        <p class="text-xs text-gray-500 mb-3">{{ $c['experience_years'] }}+ years experience</p>
                    @endif
                    @if(!empty($c['emirates']))
                        <div class="flex flex-wrap gap-1 mb-3">
                            @foreach(array_slice($c['emirates'], 0, 3) as $em)
                                <span class="text-xs px-2 py-0.5 bg-gray-100 text-gray-600 rounded-full">{{ $em }}</span>
                            @endforeach
                        </div>
                    @endif
                    @if(!empty($c['specialties']))
                        <div class="flex flex-wrap gap-1 mb-3">
                            @foreach(array_slice($c['specialties'], 0, 3) as $spec)
                                <span class="text-xs px-2 py-0.5 bg-teal-50 text-teal-700 rounded-full">{{ $spec }}</span>
                            @endforeach
                        </div>
                    @endif
                    @if($c['has_moccae_experience'])
                        <p class="text-xs text-teal-700 font-medium mb-2">MOCCAE / UAE reporting experience</p>
                    @endif
                    @if($c['bio'])
                        <p class="text-sm text-gray-600 flex-1">{{ \Illuminate\Support\Str::limit($c['bio'], 140) }}</p>
                    @endif
                    <div class="mt-4 pt-4 border-t border-gray-100">
                        <a href="{{ route('consultant-list.show', $c['id']) }}" class="text-sm font-semibold text-teal-600 hover:underline">
                            View profile &amp; request intro →
                        </a>
                    </div>
                </article>
            @empty
                <div class="col-span-full mkt-feature-card text-center py-12">
                    <h3 class="text-xl font-bold text-gray-900 mb-2">Directory launching soon</h3>
                    <p class="text-gray-500 mb-6">Our first consultants are being verified. Check back shortly or register your practice.</p>
                    <a href="{{ route('consultant.landing') }}" class="mkt-btn mkt-btn-primary">Join as a consultant</a>
                </div>
            @endforelse
        </div>

        @if($consultants->hasPages())
            <div class="mb-8">{{ $consultants->links() }}</div>
        @endif

        <div class="mkt-feature-card text-center max-w-2xl mx-auto">
            <h3 class="text-lg font-bold text-gray-900 mb-2">Are you a sustainability consultant?</h3>
            <p class="text-sm text-gray-500 mb-4">Get listed for free at launch — verified profiles receive leads from MenetZero clients and public visitors.</p>
            <a href="{{ route('consultant.register') }}" class="mkt-btn mkt-btn-primary">Apply for listing</a>
        </div>
    </div>
</section>
@endsection
