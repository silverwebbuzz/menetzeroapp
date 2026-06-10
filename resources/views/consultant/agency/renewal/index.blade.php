@extends('consultant.layouts.app')

@section('title', 'Renew Agency Pack')

@section('content')
<h1 class="text-2xl font-bold text-gray-900 mb-1">Renew for {{ $nextYear }}</h1>
<p class="text-sm text-gray-600 mb-6">
    Your {{ $subscription->plan?->plan_name }} contract ends <strong>{{ $subscription->expires_at->format('d M Y') }}</strong>.
    Choose a pack for {{ $nextYear }} and select which clients continue (max slots per pack).
</p>

<form action="{{ route('consultant.renewal.process') }}" method="POST" id="renewalForm">
    @csrf

    <div class="grid lg:grid-cols-2 gap-6 mb-8">
        <div class="bg-white border border-gray-200 rounded-xl p-6">
            <h2 class="font-semibold text-gray-900 mb-4">Clients ending {{ $subscription->contract_year }}</h2>
            @if($engagements->isEmpty())
                <p class="text-sm text-gray-500">No active clients on this contract — you can renew an empty pack or add clients after payment.</p>
            @else
                <ul class="space-y-3">
                    @foreach($engagements as $engagement)
                        @php
                            $label = $engagement->display_name ?: $engagement->managedCompany?->name;
                            $defaultPry = (int) $engagement->primary_reporting_year + 1;
                        @endphp
                        <li class="border border-gray-100 rounded-lg p-3">
                            <label class="flex items-start gap-3 cursor-pointer">
                                <input type="checkbox"
                                    name="carry[{{ $engagement->id }}][selected]"
                                    value="1"
                                    class="carry-checkbox mt-1 rounded border-gray-300 text-teal-600"
                                    checked
                                    data-engagement-id="{{ $engagement->id }}">
                                <span class="flex-1">
                                    <span class="font-medium text-gray-900">{{ $label }}</span>
                                    <span class="block text-xs text-gray-500">Current PRY {{ $engagement->primary_reporting_year }}</span>
                                </span>
                            </label>
                            <input type="hidden" name="carry[{{ $engagement->id }}][engagement_id]" value="{{ $engagement->id }}">
                            <div class="mt-2 ml-7">
                                <label class="text-xs text-gray-500">PRY for {{ $nextYear }}</label>
                                <select name="carry[{{ $engagement->id }}][primary_reporting_year]" class="text-sm rounded-lg border-gray-300 w-full max-w-[8rem]">
                                    @foreach([$defaultPry - 1, $defaultPry, $defaultPry + 1] as $year)
                                        @if($year >= 2000)
                                            <option value="{{ $year }}" @selected($year === $defaultPry)>{{ $year }}</option>
                                        @endif
                                    @endforeach
                                </select>
                            </div>
                        </li>
                    @endforeach
                </ul>
                <p class="text-xs text-gray-500 mt-3">Unselected clients are archived (read-only). They do not use slots in {{ $nextYear }}.</p>
            @endif
        </div>

        <div class="bg-white border border-gray-200 rounded-xl p-6">
            <h2 class="font-semibold text-gray-900 mb-4">Pack for {{ $nextYear }}</h2>
            <div class="space-y-3 mb-4">
                @foreach($plans as $plan)
                    @php
                        $slots = \App\Data\ConsultantAgencyPlanMatrix::slotCountForPlanCode($plan->plan_code);
                        $quote = $planQuotes[$plan->id] ?? ['charge_amount' => 0, 'pro_rata' => false];
                    @endphp
                    <label class="flex items-center gap-3 border border-gray-200 rounded-lg p-3 cursor-pointer hover:border-teal-300 has-[:checked]:border-teal-500 has-[:checked]:bg-teal-50/40">
                        <input type="radio" name="plan_id" value="{{ $plan->id }}" class="plan-radio text-teal-600" data-slots="{{ $slots }}" @checked($loop->first) required>
                        <span class="flex-1">
                            <span class="font-medium">{{ $plan->plan_name }}</span>
                            <span class="block text-xs text-gray-500">{{ $slots }} slots · AED {{ number_format($quote['charge_amount'], 0) }}</span>
                        </span>
                    </label>
                @endforeach
            </div>
            <div>
                <label class="block text-xs text-gray-500 mb-1">Payment</label>
                <select name="gateway" class="w-full text-sm rounded-lg border-gray-300" required>
                    <option value="cashfree">Cashfree</option>
                    <option value="razorpay">Razorpay (INR)</option>
                </select>
            </div>
        </div>
    </div>

    <p id="slotWarning" class="hidden mb-4 text-sm text-red-700 bg-red-50 border border-red-200 rounded-lg px-4 py-3"></p>

    <div class="flex gap-3">
        <button type="submit" class="px-5 py-2 bg-teal-600 hover:bg-teal-700 text-white text-sm font-medium rounded-lg">
            Continue to payment
        </button>
        <a href="{{ route('consultant.dashboard') }}" class="px-5 py-2 border border-gray-300 rounded-lg text-sm hover:bg-gray-50">Cancel</a>
    </div>
</form>

<script>
    const form = document.getElementById('renewalForm');
    const warning = document.getElementById('slotWarning');

    function selectedCount() {
        return document.querySelectorAll('.carry-checkbox:checked').length;
    }

    function activeSlotLimit() {
        const plan = document.querySelector('.plan-radio:checked');
        return plan ? parseInt(plan.dataset.slots, 10) : 0;
    }

    function validateSlots() {
        const count = selectedCount();
        const limit = activeSlotLimit();
        if (count > limit) {
            warning.textContent = `You selected ${count} clients but this pack only has ${limit} slots. Uncheck clients or choose a larger pack.`;
            warning.classList.remove('hidden');
            return false;
        }
        warning.classList.add('hidden');
        return true;
    }

    form?.addEventListener('submit', (e) => {
        if (!validateSlots()) e.preventDefault();
    });

    document.querySelectorAll('.carry-checkbox, .plan-radio').forEach((el) => {
        el.addEventListener('change', validateSlots);
    });
</script>
@endsection
