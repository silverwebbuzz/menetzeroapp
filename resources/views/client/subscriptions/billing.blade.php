@extends('layouts.app')

@section('title', 'Billing - MenetZero')
@section('page-title', 'Billing')

@section('content')
<div class="w-full">
    <!-- Header -->
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-900">Billing</h1>
        <p class="mt-2 text-gray-600">Manage your subscription, billing, and payment methods.</p>
    </div>

    <!-- Tabs -->
    <div class="bg-white rounded-lg border border-gray-200 mb-6">
        <div class="border-b border-gray-200">
            <nav class="flex -mb-px">
                <button onclick="showTab('subscription')" id="subscription-tab" class="tab-button active px-6 py-4 text-sm font-medium border-b-2 border-blue-500 text-blue-600">
                    My Subscription
                </button>
                <button onclick="showTab('current-plan')" id="current-plan-tab" class="tab-button px-6 py-4 text-sm font-medium border-b-2 border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300">
                    Current Plan
                </button>
                <button onclick="showTab('transactions')" id="transactions-tab" class="tab-button px-6 py-4 text-sm font-medium border-b-2 border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300">
                    Transactions
                </button>
                <button onclick="showTab('billing-methods')" id="billing-methods-tab" class="tab-button px-6 py-4 text-sm font-medium border-b-2 border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300">
                    Billing Methods
                </button>
            </nav>
        </div>

        <!-- Tab Content -->
        <div class="p-6">
            <!-- My Subscription Tab -->
            <div id="subscription-content" class="tab-content">
                <h2 class="text-xl font-semibold text-gray-900 mb-6">My Subscription</h2>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div>
                        <label class="text-sm font-medium text-gray-500">Plan Name</label>
                        <p class="text-lg font-semibold text-gray-900">{{ $subscription->plan->plan_name ?? 'N/A' }}</p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-500">Billing Cycle</label>
                        <p class="text-lg font-semibold text-gray-900">{{ ucfirst($subscription->billing_cycle) }}</p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-500">Amount</label>
                        <p class="text-lg font-semibold text-gray-900">
                            {{ $subscription->plan->currency ?? 'AED' }} {{ number_format($subscription->plan->price_annual ?? 0, 2) }}
                        </p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-500">Status</label>
                        <p class="text-lg font-semibold {{ $subscription->status === 'active' ? 'text-green-600' : 'text-gray-900' }}">
                            {{ ucfirst($subscription->status) }}
                        </p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-500">Next Billing Date</label>
                        <p class="text-lg font-semibold text-gray-900">{{ $subscription->expires_at->format('F d, Y') }}</p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-500">Auto Renewal</label>
                        <p class="text-lg font-semibold {{ $subscription->auto_renew ? 'text-green-600' : 'text-gray-900' }}">
                            {{ $subscription->auto_renew ? 'Enabled' : 'Disabled' }}
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-4">
                    <a href="{{ route('subscriptions.upgrade') }}" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                        Upgrade Plan
                    </a>
                    @if($subscription->auto_renew)
                        <form action="{{ route('subscriptions.cancel') }}" method="POST" class="inline">
                            @csrf
                            <button type="submit" class="px-6 py-2 bg-white border border-gray-300 rounded-lg hover:bg-gray-50" onclick="return confirm('Are you sure you want to cancel auto-renewal?')">
                                Cancel Auto-Renewal
                            </button>
                        </form>
                    @endif
                </div>
            </div>

            <!-- Current Plan Tab -->
            <div id="current-plan-content" class="tab-content hidden">
                <h2 class="text-xl font-semibold text-gray-900 mb-6">Current Plan</h2>
                
                <div class="bg-gray-50 rounded-lg p-6 mb-6">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h3 class="text-2xl font-bold text-gray-900">{{ $subscription->plan->plan_name ?? 'Free Plan' }}</h3>
                            <p class="text-gray-600 mt-1">{{ $subscription->plan->description ?? 'Basic plan with limited features' }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-3xl font-bold text-gray-900">
                                {{ $subscription->plan->currency ?? 'AED' }} {{ number_format($subscription->plan->price_annual ?? 0, 2) }}
                            </p>
                            <p class="text-sm text-gray-500">per {{ $subscription->billing_cycle === 'annual' ? 'year' : 'month' }}</p>
                        </div>
                    </div>

                    <div class="border-t border-gray-200 pt-4">
                        <h4 class="font-semibold text-gray-900 mb-3">Plan Features</h4>
                        <ul class="space-y-2">
                            @if($subscription->plan)
                                @php
                                    // Features is already cast to array in SubscriptionPlan model
                                    $features = $subscription->plan->features ?? [];
                                    // If it's a string, decode it; if it's already an array, use it directly
                                    if (is_string($features)) {
                                        $features = json_decode($features, true) ?? [];
                                    }
                                @endphp
                                @if(is_array($features) && count($features) > 0)
                                    @foreach($features as $feature)
                                        <li class="flex items-center text-gray-700">
                                            <svg class="w-5 h-5 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                            </svg>
                                            {{ $feature }}
                                        </li>
                                    @endforeach
                                @else
                                    <li class="text-gray-500 text-sm">No features listed</li>
                                @endif
                            @endif
                        </ul>
                    </div>
                </div>

                <a href="{{ route('subscriptions.upgrade') }}" class="inline-block px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                    Upgrade Plan
                </a>
            </div>

            <!-- Billing Methods Tab -->
            <div id="billing-methods-content" class="tab-content hidden">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-xl font-semibold text-gray-900">Billing Methods</h2>
                    <button onclick="openAddBillingMethodModal()" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                        </svg>
                        Add New Card
                    </button>
                </div>

                @if($billingMethods && count($billingMethods) > 0)
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @foreach($billingMethods as $method)
                    <div class="border border-gray-200 rounded-lg p-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="font-semibold text-gray-900">**** **** **** {{ $method->card_last4 ?? '0000' }}</p>
                                <p class="text-sm text-gray-500">{{ $method->card_brand ?? 'Card' }} • Expires {{ $method->card_exp_month ?? 'MM' }}/{{ $method->card_exp_year ?? 'YYYY' }}</p>
                                @if($method->cardholder_name)
                                    <p class="text-xs text-gray-400 mt-1">{{ $method->cardholder_name }}</p>
                                @endif
                            </div>
                            <div class="flex items-center gap-2">
                                @if($method->is_default)
                                    <span class="px-2 py-1 text-xs font-semibold rounded bg-blue-100 text-blue-800">Default</span>
                                @else
                                    <form action="{{ route('subscriptions.billing-methods.set-default', $method->id) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit" class="text-xs text-blue-600 hover:text-blue-800 px-2 py-1 border border-blue-300 rounded hover:bg-blue-50" title="Set as Default">
                                            Set Default
                                        </button>
                                    </form>
                                @endif
                                <button onclick="editBillingMethod({{ $method->id }})" class="text-blue-600 hover:text-blue-800" title="Edit">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                    </svg>
                                </button>
                                <form action="{{ route('subscriptions.billing-methods.destroy', $method->id) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this billing method?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-800" title="Delete">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                        </svg>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
                @else
                <div class="text-center py-12 bg-gray-50 rounded-lg">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                    </svg>
                    <p class="mt-2 text-sm text-gray-500">No billing methods added yet.</p>
                    <button onclick="openAddBillingMethodModal()" class="mt-4 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm">
                        Add Your First Card
                    </button>
                </div>
                @endif
            </div>

            <!-- Transactions Tab -->
            <div id="transactions-content" class="tab-content hidden">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-xl font-semibold text-gray-900">Payment Transactions</h2>
                </div>

                @if($paymentHistory && $paymentHistory->count() > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date & Time</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Payment Method</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Invoice</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($paymentHistory as $transaction)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <div>{{ $transaction->created_at->format('M d, Y') }}</div>
                                    <div class="text-xs text-gray-500">{{ $transaction->created_at->format('H:i') }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">
                                        {{ ucfirst(str_replace('_', ' ', $transaction->transaction_type ?? 'subscription')) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900">
                                    <div>{{ $transaction->description ?? 'Payment Transaction' }}</div>
                                    @if($transaction->invoice_number)
                                        <div class="text-xs text-gray-500 mt-1">Invoice: {{ $transaction->invoice_number }}</div>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    {{ $transaction->currency ?? 'AED' }} {{ number_format($transaction->amount ?? 0, 2) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    @if($transaction->billingMethod)
                                        {{ $transaction->billingMethod->card_brand ?? 'Card' }} •••• {{ $transaction->billingMethod->card_last4 ?? '0000' }}
                                    @else
                                        {{ ucfirst($transaction->payment_method ?? 'N/A') }}
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @php
                                        $statusClass = match($transaction->status) {
                                            'completed' => 'bg-green-100 text-green-800',
                                            'pending' => 'bg-yellow-100 text-yellow-800',
                                            'failed' => 'bg-red-100 text-red-800',
                                            'refunded' => 'bg-blue-100 text-blue-800',
                                            'cancelled' => 'bg-gray-100 text-gray-800',
                                            default => 'bg-gray-100 text-gray-800'
                                        };
                                    @endphp
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $statusClass }}">
                                        {{ ucfirst($transaction->status ?? 'Pending') }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    @if($transaction->invoice_url)
                                        <a href="{{ $transaction->invoice_url }}" target="_blank" class="text-blue-600 hover:text-blue-800 flex items-center gap-1">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                            </svg>
                                            Download
                                        </a>
                                    @else
                                        <span class="text-gray-400">N/A</span>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <div class="text-center py-12 bg-gray-50 rounded-lg">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                    </svg>
                    <p class="mt-2 text-sm text-gray-500">No transactions found.</p>
                    <p class="text-xs text-gray-400 mt-1">All payment transactions will appear here.</p>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Add/Edit Billing Method Modal -->
<div id="billingMethodModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50 hidden">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-medium text-gray-900" id="modalTitle">Add New Card</h3>
                <button onclick="closeBillingMethodModal()" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
            <form id="billingMethodForm" method="POST">
                @csrf
                <div id="formMethod" style="display: none;"></div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Card Number *</label>
                    <input type="text" name="card_number" id="card_number" maxlength="19" 
                           placeholder="1234 5678 9012 3456" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                           required>
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Cardholder Name *</label>
                    <input type="text" name="cardholder_name" id="cardholder_name" 
                           placeholder="John Doe" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                           required>
                </div>
                
                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Expiry Month *</label>
                        <select name="card_exp_month" id="card_exp_month" 
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                                required>
                            <option value="">MM</option>
                            @for($i = 1; $i <= 12; $i++)
                                <option value="{{ str_pad($i, 2, '0', STR_PAD_LEFT) }}">{{ str_pad($i, 2, '0', STR_PAD_LEFT) }}</option>
                            @endfor
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Expiry Year *</label>
                        <select name="card_exp_year" id="card_exp_year" 
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                                required>
                            <option value="">YYYY</option>
                            @for($i = date('Y'); $i <= date('Y') + 15; $i++)
                                <option value="{{ $i }}">{{ $i }}</option>
                            @endfor
                        </select>
                    </div>
                </div>
                
                <div class="mb-4">
                    <label class="flex items-center">
                        <input type="checkbox" name="is_default" id="is_default" 
                               class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                        <span class="ml-2 text-sm text-gray-700">Set as default payment method</span>
                    </label>
                </div>
                
                <div class="flex items-center justify-end gap-3 mt-6">
                    <button type="button" onclick="closeBillingMethodModal()" 
                            class="px-4 py-2 bg-gray-200 text-gray-800 rounded-lg hover:bg-gray-300">
                        Cancel
                    </button>
                    <button type="submit" 
                            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                        Save Card
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function showTab(tabName) {
    // Hide all tab contents
    document.querySelectorAll('.tab-content').forEach(content => {
        content.classList.add('hidden');
    });
    
    // Remove active class from all tabs
    document.querySelectorAll('.tab-button').forEach(button => {
        button.classList.remove('active', 'border-blue-500', 'text-blue-600');
        button.classList.add('border-transparent', 'text-gray-500');
    });
    
    // Show selected tab content
    document.getElementById(tabName + '-content').classList.remove('hidden');
    
    // Add active class to selected tab
    const activeTab = document.getElementById(tabName + '-tab');
    activeTab.classList.add('active', 'border-blue-500', 'text-blue-600');
    activeTab.classList.remove('border-transparent', 'text-gray-500');
}

// Auto-open tabs if redirected
@if(session('active_tab'))
    document.addEventListener('DOMContentLoaded', function() {
        showTab('{{ session('active_tab') }}');
    });
@endif

// Format card number input
document.getElementById('card_number')?.addEventListener('input', function(e) {
    let value = e.target.value.replace(/\s+/g, '');
    let formattedValue = value.match(/.{1,4}/g)?.join(' ') || value;
    e.target.value = formattedValue;
});

function openAddBillingMethodModal() {
    document.getElementById('modalTitle').textContent = 'Add New Card';
    document.getElementById('billingMethodForm').action = '{{ route('subscriptions.billing-methods.store') }}';
    document.getElementById('formMethod').innerHTML = '';
    document.getElementById('billingMethodForm').reset();
    document.getElementById('billingMethodModal').classList.remove('hidden');
}

function editBillingMethod(methodId) {
    // For now, just show a message that edit will be implemented
    // You can fetch the billing method data via AJAX and populate the form
    alert('Edit functionality will be enhanced to load card details. For now, please delete and add a new card.');
}

function closeBillingMethodModal() {
    document.getElementById('billingMethodModal').classList.add('hidden');
    document.getElementById('billingMethodForm').reset();
}

function deleteBillingMethod(methodId) {
    if (confirm('Are you sure you want to delete this billing method?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = `/subscriptions/billing-methods/${methodId}`;
        
        const csrfToken = document.createElement('input');
        csrfToken.type = 'hidden';
        csrfToken.name = '_token';
        csrfToken.value = '{{ csrf_token() }}';
        form.appendChild(csrfToken);
        
        const methodField = document.createElement('input');
        methodField.type = 'hidden';
        methodField.name = '_method';
        methodField.value = 'DELETE';
        form.appendChild(methodField);
        
        document.body.appendChild(form);
        form.submit();
    }
}

// Close modal on outside click
document.getElementById('billingMethodModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        closeBillingMethodModal();
    }
});
</script>
@endsection
