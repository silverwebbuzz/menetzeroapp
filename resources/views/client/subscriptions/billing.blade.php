@extends('layouts.app')

@section('title', 'Plan & Billing - MenetZero')
@section('page-title', 'Plan & Billing')

@section('content')
<div class="w-full max-w-5xl mx-auto">
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-900">Plan &amp; billing</h1>
        <p class="mt-2 text-gray-600">Your subscription, usage, entitlements, and payment history.</p>
    </div>

    @if(session('success'))
        <div class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('success') }}</div>
    @endif
    @if(session('info'))
        <div class="mb-4 rounded-lg border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-800">{{ session('info') }}</div>
    @endif
    @if(session('error'))
        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ session('error') }}</div>
    @endif

    {{-- Plan header --}}
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 mb-6">
        <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-6">
            <div class="flex-1">
                <div class="flex flex-wrap items-center gap-2 mb-2">
                    <h2 class="text-2xl font-bold text-gray-900">{{ $subscription->plan->plan_name ?? 'Free' }}</h2>
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $subscription->status === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-700' }}">
                        {{ ucfirst($subscription->status) }}
                    </span>
                    @if(!empty($cancellationScheduled))
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-red-100 text-red-800">
                            Cancels {{ $subscription->expires_at->format('M d, Y') }}
                        </span>
                    @elseif($subscription->auto_renew)
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-blue-100 text-blue-800">Renewal reminder on</span>
                    @endif
                </div>
                <p class="text-gray-600 text-sm mb-3">{{ $subscription->plan->description ?? '' }}</p>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-sm">
                    <div>
                        <div class="text-gray-500">Renews / expires</div>
                        <div class="font-semibold text-gray-900">{{ $subscription->expires_at->format('M d, Y') }}</div>
                    </div>
                    <div>
                        <div class="text-gray-500">Days left</div>
                        <div class="font-semibold {{ $daysRemaining < 30 ? 'text-red-600' : 'text-gray-900' }}">{{ $daysRemaining }} days</div>
                    </div>
                    <div>
                        <div class="text-gray-500">Billing</div>
                        <div class="font-semibold text-gray-900">
                            @if(!empty($isComplimentary))
                                Complimentary
                            @else
                                {{ ucfirst($subscription->billing_cycle) }} · {{ $subscription->plan->currency ?? 'AED' }} {{ number_format($subscription->plan->price_annual ?? 0, 0) }}
                            @endif
                        </div>
                    </div>
                    <div>
                        <div class="text-gray-500">Term started</div>
                        <div class="font-semibold text-gray-900">{{ $subscription->started_at->format('M d, Y') }}</div>
                    </div>
                </div>
            </div>
            <div class="flex flex-wrap gap-2 lg:flex-col lg:items-stretch min-w-[200px]">
                @php
                    $planCode = $subscription->plan->plan_code ?? '';
                    $showGrowthCta = in_array($planCode, ['client_free', 'client_starter'], true);
                @endphp
                @if($showGrowthCta)
                    <a href="{{ route('subscriptions.upgrade') }}" class="px-4 py-2.5 bg-orange-600 text-white text-sm font-semibold rounded-lg hover:bg-orange-700 text-center">
                        {{ $planCode === 'client_free' ? 'Upgrade to Starter' : 'Upgrade to Growth' }}
                    </a>
                @else
                    <a href="{{ route('subscriptions.upgrade') }}" class="px-4 py-2.5 bg-orange-600 text-white text-sm font-semibold rounded-lg hover:bg-orange-700 text-center">
                        Change plan
                    </a>
                @endif
                <span class="px-4 py-2.5 bg-gray-100 text-gray-500 text-sm font-medium rounded-lg text-center cursor-not-allowed" title="Consultant packs — coming in Phase B">
                    Add consultant pack
                </span>
                @if(!empty($cancellationScheduled))
                    <form action="{{ route('subscriptions.resume') }}" method="POST">
                        @csrf
                        <button type="submit" class="w-full px-4 py-2.5 border border-green-300 text-green-700 text-sm font-medium rounded-lg hover:bg-green-50">
                            Keep my plan
                        </button>
                    </form>
                @elseif(!empty($isPaidPlan))
                    <form action="{{ route('subscriptions.cancel') }}" method="POST" onsubmit="return confirm('Your plan stays active until {{ $subscription->expires_at->format('F d, Y') }} and will not renew. Continue?')">
                        @csrf
                        <button type="submit" class="w-full px-4 py-2.5 border border-gray-300 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50">
                            Cancel at renewal
                        </button>
                    </form>
                @endif
            </div>
        </div>

        @if(!empty($provisionLabel))
            <div class="mt-4 rounded-lg border border-purple-200 bg-purple-50 px-4 py-3 text-sm text-purple-900">{{ $provisionLabel }}</div>
        @endif

        @if(!empty($scheduledPlan))
            <div class="mt-4 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                <strong>Scheduled at renewal:</strong> switches to {{ $scheduledPlan->plan_name }} on {{ $subscription->expires_at->format('F d, Y') }}.
                @foreach($scheduledDowngradeWarnings ?? [] as $warning)
                    <p class="mt-1 text-red-800">⚠ {{ $warning }}</p>
                @endforeach
            </div>
        @endif
    </div>

    {{-- Usage --}}
    <div class="bg-white rounded-xl border border-gray-200 p-6 mb-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Usage</h3>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            @foreach($usageMeters as $key => $meter)
                @php
                    $limit = $meter['limit'];
                    $used = $meter['used'];
                    $pct = ($limit && $limit > 0) ? min(100, round(($used / $limit) * 100)) : null;
                    $limitLabel = $limit === null ? '∞' : ($limit === 0 ? 'Locked' : $limit);
                @endphp
                <div class="rounded-lg border border-gray-100 bg-gray-50 p-4">
                    <div class="flex justify-between text-sm mb-2">
                        <span class="font-medium text-gray-700">{{ $meter['label'] }}</span>
                        <span class="text-gray-900 font-semibold">{{ $used }} / {{ $limitLabel }}</span>
                    </div>
                    @if($pct !== null && $limit > 0)
                        <div class="h-2 bg-gray-200 rounded-full overflow-hidden">
                            <div class="h-full rounded-full {{ $pct >= 90 ? 'bg-red-500' : 'bg-emerald-500' }}" style="width: {{ $pct }}%"></div>
                        </div>
                    @elseif($limit === 0)
                        <p class="text-xs text-purple-700">Available on Starter+</p>
                    @else
                        <p class="text-xs text-gray-500">Unlimited on your plan</p>
                    @endif
                </div>
            @endforeach
        </div>
        <div class="mt-4 flex flex-wrap gap-2 text-xs">
            <span class="text-gray-500 mr-1">Exports:</span>
            @foreach($downloadEntitlements as $item)
                <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full {{ $item['allowed'] ? 'bg-emerald-50 text-emerald-800' : 'bg-gray-100 text-gray-500' }}">
                    @if($item['allowed'])
                        <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                    @endif
                    {{ $item['label'] }}
                    @if(!$item['allowed'] && $item['hint'])
                        <span class="text-gray-400">· {{ $item['hint'] }}</span>
                    @endif
                </span>
            @endforeach
        </div>
    </div>

    {{-- Entitlements --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-3">Data &amp; operations</h3>
            <ul class="space-y-2 text-sm">
                @foreach($dataEntitlements as $item)
                    <li class="flex items-center justify-between">
                        <span class="text-gray-700">{{ $item['label'] }}</span>
                        <span class="flex items-center gap-2">
                            @if($item['hint'])
                                <span class="text-xs text-gray-400">{{ $item['hint'] }}</span>
                            @endif
                            @if($item['allowed'])
                                <svg class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                            @else
                                <span class="text-gray-300">—</span>
                            @endif
                        </span>
                    </li>
                @endforeach
            </ul>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-3">Report downloads</h3>
            <ul class="space-y-2 text-sm">
                @foreach($downloadEntitlements as $item)
                    <li class="flex items-center justify-between">
                        <span class="text-gray-700">{{ $item['label'] }}</span>
                        <span class="flex items-center gap-2">
                            @if(!$item['allowed'] && $item['hint'])
                                <a href="{{ route('subscriptions.upgrade') }}" class="text-xs text-orange-600 hover:underline">{{ $item['hint'] }}</a>
                            @endif
                            @if($item['allowed'])
                                <svg class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                            @else
                                <span class="text-gray-300">—</span>
                            @endif
                        </span>
                    </li>
                @endforeach
            </ul>
            <p class="mt-4 text-xs text-gray-500">Disclosure PDF export requires Growth. GHG / IEQT exports require Starter.</p>
        </div>
    </div>

    {{-- Consultant marketplace --}}
    <div class="bg-gradient-to-r from-slate-50 to-gray-100 border border-gray-200 rounded-xl p-6 mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h3 class="text-lg font-semibold text-gray-900">Consultant marketplace</h3>
            <p class="text-sm text-gray-600 mt-1">
                Your plan: <strong>{{ $consultantDirectoryLabel }}</strong>.
                Verified UAE partners for review and sign-off on your reports.
            </p>
        </div>
        <a href="{{ route('client.consultants.index') }}"
           class="inline-flex px-4 py-2 bg-teal-600 hover:bg-teal-700 text-white text-sm font-medium rounded-lg">
            Browse partners
        </a>
    </div>

    {{-- Payment history & billing methods (tabs) --}}
    <div class="bg-white rounded-lg border border-gray-200">
        <div class="border-b border-gray-200">
            <nav class="flex -mb-px">
                <button type="button" onclick="showTab('transactions')" id="transactions-tab" class="tab-button active px-6 py-4 text-sm font-medium border-b-2 border-blue-500 text-blue-600">
                    Payment history
                </button>
                <button type="button" onclick="showTab('billing-methods')" id="billing-methods-tab" class="tab-button px-6 py-4 text-sm font-medium border-b-2 border-transparent text-gray-500 hover:text-gray-700">
                    Billing methods
                </button>
            </nav>
        </div>
        <div class="p-6">
            <div id="transactions-content" class="tab-content">
                @if($paymentHistory && $paymentHistory->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Description</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Invoice</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach($paymentHistory as $transaction)
                                    <tr>
                                        <td class="px-4 py-3 whitespace-nowrap">{{ $transaction->created_at->format('M d, Y') }}</td>
                                        <td class="px-4 py-3">{{ $transaction->description ?? 'Subscription payment' }}</td>
                                        <td class="px-4 py-3 font-medium">{{ $transaction->currency ?? 'AED' }} {{ number_format($transaction->amount ?? 0, 2) }}</td>
                                        <td class="px-4 py-3">
                                            <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-gray-100">{{ ucfirst($transaction->status ?? 'pending') }}</span>
                                        </td>
                                        <td class="px-4 py-3">
                                            @if($transaction->invoice_url)
                                                <a href="{{ $transaction->invoice_url }}" target="_blank" class="text-blue-600 hover:underline">Download</a>
                                            @else
                                                <span class="text-gray-400">—</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="text-sm text-gray-500 py-8 text-center">
                        @if(!empty($isComplimentary))
                            No payment history — your plan is complimentary.
                        @else
                            Payment transactions will appear here after your first purchase.
                        @endif
                    </p>
                @endif
            </div>

            <div id="billing-methods-content" class="tab-content hidden">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-semibold text-gray-900">Saved cards</h3>
                    <button type="button" onclick="openAddBillingMethodModal()" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm">Add card</button>
                </div>
                @if($billingMethods && $billingMethods->count() > 0)
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        @foreach($billingMethods as $method)
                            <div class="border border-gray-200 rounded-lg p-4 flex justify-between items-center">
                                <div>
                                    <p class="font-semibold">•••• {{ $method->card_last4 ?? '0000' }}</p>
                                    <p class="text-sm text-gray-500">{{ $method->card_brand ?? 'Card' }} · {{ $method->card_exp_month }}/{{ $method->card_exp_year }}</p>
                                </div>
                                @if($method->is_default)
                                    <span class="text-xs bg-blue-100 text-blue-800 px-2 py-1 rounded">Default</span>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-sm text-gray-500 py-6 text-center">No saved payment methods.</p>
                @endif
            </div>
        </div>
    </div>
</div>

@include('client.subscriptions.partials.billing-method-modal', ['billingMethods' => $billingMethods])

<script>
function showTab(tabName) {
    document.querySelectorAll('.tab-content').forEach(el => el.classList.add('hidden'));
    document.querySelectorAll('.tab-button').forEach(btn => {
        btn.classList.remove('active', 'border-blue-500', 'text-blue-600');
        btn.classList.add('border-transparent', 'text-gray-500');
    });
    document.getElementById(tabName + '-content').classList.remove('hidden');
    const tab = document.getElementById(tabName + '-tab');
    tab.classList.add('active', 'border-blue-500', 'text-blue-600');
    tab.classList.remove('border-transparent', 'text-gray-500');
}
@if(session('active_tab'))
document.addEventListener('DOMContentLoaded', () => showTab('{{ session('active_tab') }}'));
@endif
</script>
@endsection
