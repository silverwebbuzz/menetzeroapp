{{--
    Setup progress. Two steps, not the four drawn in Internal.dc.html.

    The canvas puts "Boundary" at step 2, before locations. Deliberately not
    followed: EmissionCalculationService contains no reference to base_year or
    consolidation_approach -- they are read only by EmissionsIntensityService,
    IeqtExportService and ReductionTargetProgressService, all reporting-time.
    Requiring a base year before a user has entered a single reading asks a
    question they have no basis to answer, on a screen they must clear to do
    anything. Boundary is offered after setup instead, and nudged once there is
    data to set it against.

    What genuinely blocks data entry, and is therefore all this asks for:
      - country  -> EmissionCalculationService matches factors on region
      - sector / industry -> selects which sources are offered
      - one active location -> an entry belongs to a site

    Mirrors OnboardingService::currentStep(), so it cannot drift from what the
    middleware actually enforces.

    Usage: @include('partials.onboarding-stepper', ['current' => 'business'])
--}}
@php
    $steps = [
        ['key' => 'business', 'label' => 'Business profile'],
        ['key' => 'location', 'label' => 'First location'],
    ];

    $order = array_column($steps, 'key');
    $currentIndex = array_search($current ?? 'business', $order, true);
    $currentIndex = $currentIndex === false ? 0 : $currentIndex;
@endphp

<div class="mb-6">
    <div class="flex items-center gap-3 flex-wrap">
        @foreach ($steps as $i => $step)
            @php
                $state = $i < $currentIndex ? 'done' : ($i === $currentIndex ? 'current' : 'todo');
            @endphp

            <div class="flex items-center gap-2">
                <span class="inline-flex items-center justify-center w-6 h-6 rounded-full text-xs font-semibold border
                    {{ $state === 'done' ? 'bg-green-50 border-green-200 text-green-700' : '' }}
                    {{ $state === 'current' ? 'bg-gray-900 border-gray-900 text-white' : '' }}
                    {{ $state === 'todo' ? 'bg-white border-gray-200 text-gray-400' : '' }}">
                    {{ $state === 'done' ? '✓' : $i + 1 }}
                </span>
                <span class="text-sm {{ $state === 'todo' ? 'text-gray-400' : 'text-gray-900' }} {{ $state === 'current' ? 'font-semibold' : 'font-medium' }}">
                    {{ $step['label'] }}
                </span>
            </div>

            @if (!$loop->last)
                <span class="h-px w-8 bg-gray-200"></span>
            @endif
        @endforeach
    </div>

    <p class="text-xs text-gray-500 mt-2">
        Step {{ $currentIndex + 1 }} of {{ count($steps) }} — this is all we need before you can enter data.
        Reporting settings such as base year come later.
    </p>
</div>
