<!-- Company Setup Modal -->
<div id="companySetupModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-lg shadow-xl max-w-4xl w-full max-h-[90vh] overflow-y-auto">
        <!-- Modal Header -->
        <div class="flex items-center justify-between p-6 border-b border-gray-200">
            <div class="flex items-center gap-3">
                <img src="https://app.menetzero.com/public/images/menetzero.svg" alt="MIDDLE EAST NET Zero" class="h-6 w-auto">
                <div>
                    <h2 class="text-xl font-semibold text-gray-900">Complete Your Business Profile</h2>
                    <p class="text-sm text-gray-600">Please complete your business profile to get started with carbon tracking.</p>
                </div>
            </div>
            <button onclick="closeCompanySetupModal()" class="text-gray-400 hover:text-gray-600 transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>

        <!-- Modal Body -->
        <div class="p-6">
            <!-- Progress Indicator -->
            <div class="flex items-center gap-4 mb-6">
                <div class="bg-gradient-to-r from-emerald-600 to-emerald-500 text-white px-4 py-2 rounded-lg flex items-center gap-2 text-sm">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                    </svg>
                    Personal Profile ✓
                </div>
                <div class="bg-emerald-100 text-emerald-700 px-4 py-2 rounded-lg flex items-center gap-2 text-sm">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"></path>
                    </svg>
                    Business Details
                </div>
            </div>

            @if(session('success'))
                <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg mb-6">
                    {{ session('success') }}
                </div>
            @endif

            <form method="POST" action="{{ route('company.setup.store') }}" class="space-y-6" id="companySetupForm">
                @csrf
                
                <!-- Business Information -->
                <div class="bg-white border border-gray-200 rounded-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Business Information</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Business Name *</label>
                            <input class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500" 
                                   name="company_name" type="text" value="{{ old('company_name') }}" required placeholder="Enter your business name">
                            @error('company_name')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Business Email</label>
                            <input class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500" 
                                   name="business_email" type="email" value="{{ old('business_email', auth()->user()->email) }}" placeholder="business@company.com">
                            @error('business_email')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Business Website</label>
                            <input class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500" 
                                   name="business_website" type="url" value="{{ old('business_website') }}" placeholder="https://www.company.com">
                            @error('business_website')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Country</label>
                            <select class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500" name="country">
                                <option value="">Select Country</option>
                                <option value="UAE" {{ old('country') == 'UAE' ? 'selected' : '' }}>United Arab Emirates</option>
                                <option value="SA" {{ old('country') == 'SA' ? 'selected' : '' }}>Saudi Arabia</option>
                                <option value="KW" {{ old('country') == 'KW' ? 'selected' : '' }}>Kuwait</option>
                                <option value="QA" {{ old('country') == 'QA' ? 'selected' : '' }}>Qatar</option>
                                <option value="BH" {{ old('country') == 'BH' ? 'selected' : '' }}>Bahrain</option>
                                <option value="OM" {{ old('country') == 'OM' ? 'selected' : '' }}>Oman</option>
                                <option value="US" {{ old('country') == 'US' ? 'selected' : '' }}>United States</option>
                                <option value="UK" {{ old('country') == 'UK' ? 'selected' : '' }}>United Kingdom</option>
                                <option value="IN" {{ old('country') == 'IN' ? 'selected' : '' }}>India</option>
                                <option value="Other" {{ old('country') == 'Other' ? 'selected' : '' }}>Other</option>
                            </select>
                            @error('country')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <div class="mt-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Business Address</label>
                        <textarea class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500" 
                                  name="business_address" rows="3" placeholder="Enter your registered business address">{{ old('business_address') }}</textarea>
                        @error('business_address')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>
                </div>

                <!-- Business Details -->
                <div class="bg-white border border-gray-200 rounded-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Business Details</h3>
                    <p class="text-sm text-gray-600 mb-4">Describe briefly what your business does.</p>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Business Category</label>
                            <select class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500" name="business_category">
                                <option value="">Select Category</option>
                                <option value="Technology" {{ old('business_category') == 'Technology' ? 'selected' : '' }}>Technology</option>
                                <option value="Manufacturing" {{ old('business_category') == 'Manufacturing' ? 'selected' : '' }}>Manufacturing</option>
                                <option value="Construction" {{ old('business_category') == 'Construction' ? 'selected' : '' }}>Construction</option>
                                <option value="Healthcare" {{ old('business_category') == 'Healthcare' ? 'selected' : '' }}>Healthcare</option>
                                <option value="Finance" {{ old('business_category') == 'Finance' ? 'selected' : '' }}>Finance</option>
                                <option value="Retail" {{ old('business_category') == 'Retail' ? 'selected' : '' }}>Retail</option>
                                <option value="Energy" {{ old('business_category') == 'Energy' ? 'selected' : '' }}>Energy</option>
                                <option value="Transportation" {{ old('business_category') == 'Transportation' ? 'selected' : '' }}>Transportation</option>
                                <option value="Other" {{ old('business_category') == 'Other' ? 'selected' : '' }}>Other</option>
                            </select>
                            @error('business_category')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Business Subcategory</label>
                            <input class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500" 
                                   name="business_subcategory" type="text" value="{{ old('business_subcategory') }}" placeholder="e.g., Software Development">
                            @error('business_subcategory')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <div class="mt-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Business Description</label>
                        <textarea class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500" 
                                  name="business_description" rows="4" placeholder="Tell us a bit about what you do...">{{ old('business_description') }}</textarea>
                        @error('business_description')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex items-center justify-between pt-6 border-t border-gray-200">
                    <a href="{{ route('company.setup.skip') }}" class="text-gray-600 hover:text-gray-800 font-medium">Skip for now</a>
                    <div class="flex gap-3">
                        <button type="button" onclick="closeCompanySetupModal()" class="px-4 py-2 text-gray-600 hover:text-gray-800 font-medium">Cancel</button>
                        <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white px-6 py-2 rounded-lg font-medium transition-colors">Complete Setup</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openCompanySetupModal() {
    document.getElementById('companySetupModal').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

function closeCompanySetupModal() {
    document.getElementById('companySetupModal').classList.add('hidden');
    document.body.style.overflow = 'auto';
}

// Close modal when clicking outside
document.getElementById('companySetupModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeCompanySetupModal();
    }
});

// Close modal with Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeCompanySetupModal();
    }
});
</script>
