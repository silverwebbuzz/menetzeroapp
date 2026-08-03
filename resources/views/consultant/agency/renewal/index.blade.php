@extends('consultant.layouts.app')

@section('title', 'Renew capacity')
@section('page-title', 'Renew capacity')

@section('content')
<div class="w-full max-w-4xl">
    <h1 class="text-2xl font-bold text-gray-900 mb-1">Renew for {{ $nextYear }}</h1>
    <p class="text-sm text-gray-600 mb-6">
        Your {{ $subscription->plan?->plan_name }} capacity ends
        <strong>{{ $subscription->expires_at->format('d M Y') }}</strong>.
        Request capacity for {{ $nextYear }} offline — MENetZero confirms pricing and activates after payment.
    </p>

    <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-950 mb-6">
        <strong>Offline renewal.</strong>
        There is no self-serve checkout. Use <em>Request clients</em> to choose package depth and how many managed clients you need for {{ $nextYear }}, then note that this is a renewal.
    </div>

    <div class="grid lg:grid-cols-2 gap-6 mb-8">
        <div class="bg-white border border-gray-200 rounded-xl p-6">
            <h2 class="font-semibold text-gray-900 mb-4">Current managed clients ({{ $subscription->contract_year }})</h2>
            @if($engagements->isEmpty())
                <p class="text-sm text-gray-500">No active clients on this contract. You can still request capacity for {{ $nextYear }}.</p>
            @else
                <ul class="space-y-2 text-sm">
                    @foreach($engagements as $engagement)
                        @php $label = $engagement->display_name ?: $engagement->managedCompany?->name; @endphp
                        <li class="flex justify-between gap-3 border border-gray-100 rounded-lg px-3 py-2">
                            <span class="font-medium text-gray-900">{{ $label }}</span>
                            <span class="text-xs text-gray-500">PRY {{ $engagement->primary_reporting_year }}</span>
                        </li>
                    @endforeach
                </ul>
                <p class="text-xs text-gray-500 mt-3">
                    Mention which clients continue in your request notes. After activation for {{ $nextYear }}, keep working in those workspaces under your new capacity.
                </p>
            @endif
        </div>

        <div class="bg-white border border-gray-200 rounded-xl p-6 flex flex-col">
            <h2 class="font-semibold text-gray-900 mb-2">Next step</h2>
            <p class="text-sm text-gray-600 mb-4 flex-1">
                Suggested starting point: request <strong>{{ max(1, $engagements->count()) }}</strong>
                managed client{{ $engagements->count() === 1 ? '' : 's' }} for {{ $nextYear }}
                (adjust during Request clients).
            </p>
            <a href="{{ route('consultant.packs.index') }}"
               class="inline-flex justify-center px-4 py-2.5 bg-orange-600 text-white text-sm font-semibold rounded-lg hover:bg-orange-700 text-center">
                Request clients for {{ $nextYear }}
            </a>
            <a href="{{ route('consultant.dashboard') }}" class="mt-3 text-center text-sm text-brand hover:underline">
                Back to dashboard
            </a>
        </div>
    </div>
</div>
@endsection
