@extends('admin.layouts.app')

@section('title', 'Payment Gateways | MENetZero')
@section('page-title', 'Payment Gateways')

@section('content')
    @if(session('success'))
        <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">{{ session('error') }}</div>
    @endif

    <p class="text-sm text-gray-500 mb-6">
        Configure the keys for the payment gateways clients use to purchase paid plans.
        Secrets are encrypted at rest. Leave a secret field blank to keep the saved value.
    </p>

    <div class="space-y-6">
        @foreach($gateways as $gateway)
            <div class="bg-white shadow rounded-lg">
                <div class="px-5 py-4 border-b border-gray-200 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <h2 class="text-lg font-medium text-gray-900">{{ $gateway->label }}</h2>
                        <span class="px-2 py-0.5 text-xs rounded-full {{ $gateway->is_enabled ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-600' }}">
                            {{ $gateway->is_enabled ? 'Enabled' : 'Disabled' }}
                        </span>
                        <span class="px-2 py-0.5 text-xs rounded-full {{ $gateway->isLive() ? 'bg-blue-100 text-blue-800' : 'bg-amber-100 text-amber-800' }}">
                            {{ $gateway->isLive() ? 'Live' : 'Test' }}
                        </span>
                    </div>
                </div>

                <form method="POST" action="{{ route('admin.payment-gateways.update', $gateway->id) }}" class="p-5">
                    @csrf
                    @method('PUT')

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Mode</label>
                            <select name="mode" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-purple-500 focus:border-purple-500">
                                <option value="test" {{ $gateway->mode === 'test' ? 'selected' : '' }}>Test / Sandbox</option>
                                <option value="live" {{ $gateway->mode === 'live' ? 'selected' : '' }}>Live / Production</option>
                            </select>
                        </div>

                        <div class="flex items-center mt-7">
                            <input type="checkbox" name="is_enabled" id="enabled_{{ $gateway->id }}" value="1" {{ $gateway->is_enabled ? 'checked' : '' }}
                                   class="h-4 w-4 text-purple-600 focus:ring-purple-500 border-gray-300 rounded">
                            <label for="enabled_{{ $gateway->id }}" class="ml-2 block text-sm text-gray-700">Enable for clients</label>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                {{ $gateway->gateway === 'cashfree' ? 'App ID (x-client-id)' : ($gateway->gateway === 'stripe' ? 'Publishable Key' : 'Key ID') }}
                            </label>
                            <input type="text" name="key_id" value="{{ old('key_id', $gateway->key_id) }}"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-purple-500 focus:border-purple-500 font-mono text-sm">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                {{ $gateway->gateway === 'cashfree' ? 'Secret Key (x-client-secret)' : ($gateway->gateway === 'stripe' ? 'Secret Key (sk_...)' : 'Key Secret') }}
                            </label>
                            <input type="password" name="key_secret" autocomplete="new-password"
                                   placeholder="{{ $gateway->key_secret ? '•••••••• (saved — leave blank to keep)' : 'Enter secret key' }}"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-purple-500 focus:border-purple-500 font-mono text-sm">
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Webhook Secret (optional)</label>
                            <input type="password" name="webhook_secret" autocomplete="new-password"
                                   placeholder="{{ $gateway->webhook_secret ? '•••••••• (saved — leave blank to keep)' : 'Optional' }}"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-purple-500 focus:border-purple-500 font-mono text-sm">
                            @php
                                $webhookUrl = match ($gateway->gateway) {
                                    'cashfree' => route('webhooks.payments.cashfree'),
                                    'stripe' => route('webhooks.payments.stripe'),
                                    default => route('webhooks.payments.razorpay'),
                                };
                            @endphp
                            <p class="mt-2 text-xs text-gray-500">
                                Webhook URL (paste into the {{ $gateway->label }} dashboard):
                                <code class="px-1.5 py-0.5 bg-gray-100 rounded select-all">{{ $webhookUrl }}</code>
                            </p>
                        </div>
                    </div>

                    <div class="mt-5 flex justify-end">
                        <button type="submit" class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700">Save {{ $gateway->label }}</button>
                    </div>
                </form>
            </div>
        @endforeach
    </div>
@endsection
