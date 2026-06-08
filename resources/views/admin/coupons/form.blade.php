@extends('admin.layouts.app')

@section('title', ($coupon->exists ? 'Edit' : 'Create') . ' Coupon | MENetZero')
@section('page-title', ($coupon->exists ? 'Edit' : 'Create') . ' Coupon')

@section('content')
<div class="max-w-2xl bg-white shadow rounded-lg p-6">
    <form action="{{ $coupon->exists ? route('admin.coupons.update', $coupon) : route('admin.coupons.store') }}" method="POST" class="space-y-4">
        @csrf
        @if($coupon->exists) @method('PUT') @endif

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Code</label>
                <input type="text" name="code" value="{{ old('code', $coupon->code) }}" required class="w-full border rounded-lg px-3 py-2 font-mono uppercase">
                @error('code')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Internal name</label>
                <input type="text" name="name" value="{{ old('name', $coupon->name) }}" required placeholder="Launch 2026 campaign" class="w-full border rounded-lg px-3 py-2">
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Type</label>
            <select name="type" id="coupon-type" class="w-full border rounded-lg px-3 py-2">
                @foreach(['percent' => 'Percentage discount', 'fixed' => 'Fixed amount off', 'free' => 'Free plan (100% off)'] as $val => $label)
                    <option value="{{ $val }}" @selected(old('type', $coupon->type) === $val)>{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div id="field-percent" class="{{ old('type', $coupon->type) === 'percent' ? '' : 'hidden' }}">
            <label class="block text-sm font-medium text-gray-700 mb-1">Discount %</label>
            <input type="number" name="discount_percent" step="0.01" min="0" max="100" value="{{ old('discount_percent', $coupon->discount_percent) }}" class="w-full border rounded-lg px-3 py-2">
        </div>

        <div id="field-fixed" class="grid grid-cols-2 gap-4 {{ old('type', $coupon->type) === 'fixed' ? '' : 'hidden' }}">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Discount AED</label>
                <input type="number" name="discount_amount_aed" step="0.01" min="0" value="{{ old('discount_amount_aed', $coupon->discount_amount_aed) }}" class="w-full border rounded-lg px-3 py-2">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Discount INR</label>
                <input type="number" name="discount_amount_inr" step="0.01" min="0" value="{{ old('discount_amount_inr', $coupon->discount_amount_inr) }}" class="w-full border rounded-lg px-3 py-2">
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Restrict to plan (optional)</label>
            <select name="subscription_plan_id" class="w-full border rounded-lg px-3 py-2">
                <option value="">Any paid plan</option>
                @foreach($plans as $plan)
                    <option value="{{ $plan->id }}" @selected((int) old('subscription_plan_id', $coupon->subscription_plan_id) === (int) $plan->id)>{{ $plan->plan_name }}</option>
                @endforeach
            </select>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Max uses (blank = unlimited)</label>
                <input type="number" name="max_uses" min="1" value="{{ old('max_uses', $coupon->max_uses) }}" class="w-full border rounded-lg px-3 py-2">
            </div>
            <div class="flex items-end">
                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $coupon->is_active)) class="rounded">
                    Active
                </label>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Valid from</label>
                <input type="datetime-local" name="starts_at" value="{{ old('starts_at', optional($coupon->starts_at)->format('Y-m-d\TH:i')) }}" class="w-full border rounded-lg px-3 py-2">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Valid until</label>
                <input type="datetime-local" name="expires_at" value="{{ old('expires_at', optional($coupon->expires_at)->format('Y-m-d\TH:i')) }}" class="w-full border rounded-lg px-3 py-2">
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Notes (internal)</label>
            <textarea name="notes" rows="2" class="w-full border rounded-lg px-3 py-2">{{ old('notes', $coupon->notes) }}</textarea>
        </div>

        <div class="flex gap-3 pt-2">
            <button type="submit" class="bg-purple-600 text-white px-5 py-2 rounded-lg hover:bg-purple-700">Save</button>
            <a href="{{ route('admin.coupons.index') }}" class="px-5 py-2 border rounded-lg hover:bg-gray-50">Cancel</a>
        </div>
    </form>
</div>

<script>
document.getElementById('coupon-type').addEventListener('change', function () {
    document.getElementById('field-percent').classList.toggle('hidden', this.value !== 'percent');
    document.getElementById('field-fixed').classList.toggle('hidden', this.value !== 'fixed');
});
</script>
@endsection
