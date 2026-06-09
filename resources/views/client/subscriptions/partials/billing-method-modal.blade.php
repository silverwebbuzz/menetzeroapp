<div id="billingMethodModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50 hidden">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-medium text-gray-900">Add payment card</h3>
            <button type="button" onclick="closeBillingMethodModal()" class="text-gray-400 hover:text-gray-600">&times;</button>
        </div>
        <form id="billingMethodForm" method="POST" action="{{ route('subscriptions.billing-methods.store') }}">
            @csrf
            <div class="mb-3">
                <label class="block text-sm font-medium text-gray-700 mb-1">Card number</label>
                <input type="text" name="card_number" maxlength="19" required class="w-full px-3 py-2 border border-gray-300 rounded-lg">
            </div>
            <div class="mb-3">
                <label class="block text-sm font-medium text-gray-700 mb-1">Cardholder name</label>
                <input type="text" name="cardholder_name" required class="w-full px-3 py-2 border border-gray-300 rounded-lg">
            </div>
            <div class="grid grid-cols-2 gap-3 mb-3">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Expiry month</label>
                    <select name="card_exp_month" required class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                        <option value="">MM</option>
                        @for($i = 1; $i <= 12; $i++)
                            <option value="{{ str_pad($i, 2, '0', STR_PAD_LEFT) }}">{{ str_pad($i, 2, '0', STR_PAD_LEFT) }}</option>
                        @endfor
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Expiry year</label>
                    <select name="card_exp_year" required class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                        <option value="">YYYY</option>
                        @for($i = date('Y'); $i <= date('Y') + 15; $i++)
                            <option value="{{ $i }}">{{ $i }}</option>
                        @endfor
                    </select>
                </div>
            </div>
            <label class="flex items-center mb-4 text-sm">
                <input type="checkbox" name="is_default" value="1" class="rounded border-gray-300 text-blue-600">
                <span class="ml-2">Set as default</span>
            </label>
            <div class="flex justify-end gap-2">
                <button type="button" onclick="closeBillingMethodModal()" class="px-4 py-2 bg-gray-200 rounded-lg text-sm">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm">Save</button>
            </div>
        </form>
    </div>
</div>
<script>
function openAddBillingMethodModal() {
    document.getElementById('billingMethodModal').classList.remove('hidden');
}
function closeBillingMethodModal() {
    document.getElementById('billingMethodModal').classList.add('hidden');
}
</script>
