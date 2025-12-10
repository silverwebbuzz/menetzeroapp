<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Complete Your Business Profile - MenetZero</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        window.tailwind = { config: { theme: { extend: {} } } };
    </script>
    <style>
        /* Design System Variables */
        :root {
            --brand-primary: #10b981;
            --brand-secondary: #059669;
            --brand-accent: #34d399;
            --text-primary: #111827;
            --text-secondary: #6b7280;
            --text-muted: #9ca3af;
            --bg-primary: #ffffff;
            --bg-secondary: #f9fafb;
            --bg-muted: #f3f4f6;
            --border-color: #e5e7eb;
            --border-focus: #10b981;
            --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
            --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
            --radius-sm: 0.375rem;
            --radius-md: 0.5rem;
            --radius-lg: 0.75rem;
        }

        /* Component Styles */
        .card {
            background: var(--bg-primary);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-sm);
        }

        .card-body {
            padding: 1.5rem;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-label {
            display: block;
            font-size: 0.875rem;
            font-weight: 500;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
        }

        .form-input {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            font-size: 0.875rem;
            color: var(--text-primary);
            background: var(--bg-primary);
            transition: all 0.2s ease;
        }

        .form-input:focus {
            outline: none;
            border-color: var(--border-focus);
            box-shadow: 0 0 0 3px rgb(16 185 129 / 0.1);
        }

        .form-select {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='m6 8 4 4 4-4'/%3e%3c/svg%3e");
            background-position: right 0.5rem center;
            background-repeat: no-repeat;
            background-size: 1.5em 1.5em;
            padding-right: 2.5rem;
        }

        .form-textarea {
            resize: vertical;
            min-height: 6rem;
        }

        .form-error {
            color: #dc2626;
            font-size: 0.75rem;
            margin-top: 0.25rem;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.75rem 1.5rem;
            border-radius: var(--radius-md);
            font-size: 0.875rem;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.2s ease;
            cursor: pointer;
            border: none;
        }

        .btn-primary {
            background: var(--brand-primary);
            color: white;
        }

        .btn-primary:hover {
            background: var(--brand-secondary);
        }

        .alert {
            padding: 1rem;
            border-radius: var(--radius-md);
            margin-bottom: 1rem;
        }

        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }

        .step-indicator {
            background: linear-gradient(90deg, var(--brand-secondary) 0%, var(--brand-primary) 100%);
        }

        .brand-gradient {
            background: linear-gradient(135deg, var(--brand-primary) 0%, var(--brand-secondary) 100%);
        }

        .glass {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
    </style>
</head>
<body class="min-h-screen">
    <div class="grid lg:grid-cols-2 min-h-screen">
        <div class="flex items-center justify-center p-4 sm:p-8">
            <div class="w-full max-w-2xl">
                <div class="mb-8">
                    <div class="flex items-center mb-6">
                        <img src="https://app.menetzero.com/public/images/menetzero.svg" alt="MIDDLE EAST NET Zero" class="h-8 w-auto">
                    </div>
                    <h1 class="text-3xl font-semibold text-gray-900">Welcome {{ auth()->user()->name }}!</h1>
                    <p class="mt-2 text-sm text-gray-600">Please complete your business profile to get started with carbon tracking.</p>
                </div>

                <!-- Progress Indicator -->
                <div class="flex flex-col sm:flex-row items-start sm:items-center gap-2 sm:gap-4 mb-8">
                    <div class="step-indicator text-white px-3 sm:px-4 py-2 rounded-lg flex items-center gap-2 text-sm">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                        </svg>
                        <span class="hidden sm:inline">Personal Profile</span>
                        <span class="sm:hidden">Profile</span>
                    </div>
                    <div class="bg-emerald-100 text-emerald-700 px-3 sm:px-4 py-2 rounded-lg flex items-center gap-2 text-sm">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"></path>
                        </svg>
                        <span class="hidden sm:inline">Business Details</span>
                        <span class="sm:hidden">Business</span>
                    </div>
                </div>

                @if(session('success'))
                    <div class="alert alert-success mb-6">
                        {{ session('success') }}
                    </div>
                @endif

                <form method="POST" action="{{ route('company.setup.store') }}" class="space-y-6">
                    @csrf
                    
                    <!-- Business Information -->
                    <div class="card">
                        <div class="card-body">
                            <h3 class="text-lg font-semibold text-gray-900 mb-6">Business Information</h3>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="form-group">
                                    <label class="form-label">Business Name *</label>
                                    <input class="form-input" name="company_name" type="text" value="{{ old('company_name') }}" required placeholder="Enter your business name">
                                    @error('company_name')<p class="form-error">{{ $message }}</p>@enderror
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Business Email</label>
                                    <input class="form-input" name="business_email" type="email" value="{{ old('business_email', auth()->user()->email) }}" placeholder="business@company.com">
                                    @error('business_email')<p class="form-error">{{ $message }}</p>@enderror
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
                                <div class="form-group">
                                    <label class="form-label">Business Website</label>
                                    <input class="form-input" name="business_website" type="url" value="{{ old('business_website') }}" placeholder="https://www.company.com">
                                    @error('business_website')<p class="form-error">{{ $message }}</p>@enderror
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Country</label>
                                    <select class="form-input form-select" name="country">
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
                                    @error('country')<p class="form-error">{{ $message }}</p>@enderror
                                </div>
                            </div>

                            <div class="form-group mt-6">
                                <label class="form-label">Business Address</label>
                                <textarea class="form-input form-textarea" name="business_address" rows="3" placeholder="Enter your registered business address">{{ old('business_address') }}</textarea>
                                @error('business_address')<p class="form-error">{{ $message }}</p>@enderror
                            </div>
                        </div>
                    </div>

                    <!-- Business Details -->
                    <div class="card">
                        <div class="card-body">
                            <h3 class="text-lg font-semibold text-gray-900 mb-2">Business Details</h3>
                            <p class="text-sm text-gray-600 mb-6">Describe briefly what your business does.</p>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="form-group">
                                    <label class="form-label">Business Category</label>
                                    <select class="form-input form-select" name="business_category">
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
                                    @error('business_category')<p class="form-error">{{ $message }}</p>@enderror
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Business Subcategory</label>
                                    <input class="form-input" name="business_subcategory" type="text" value="{{ old('business_subcategory') }}" placeholder="e.g., Software Development">
                                    @error('business_subcategory')<p class="form-error">{{ $message }}</p>@enderror
                                </div>
                            </div>

                            <div class="form-group mt-6">
                                <label class="form-label">Business Description</label>
                                <textarea class="form-input form-textarea" name="business_description" rows="4" placeholder="Tell us a bit about what you do...">{{ old('business_description') }}</textarea>
                                @error('business_description')<p class="form-error">{{ $message }}</p>@enderror
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex flex-col sm:flex-row items-center justify-end pt-6 gap-4">
                        <button type="submit" class="btn btn-primary w-full sm:w-auto">Continue</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="relative brand-gradient text-white hidden lg:block">
            <div class="relative h-full w-full flex items-center justify-center p-8">
                <div class="glass rounded-3xl p-8 w-full max-w-2xl">
                    <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/10 border border-white/20 text-sm">Complete Your Profile</span>
                    <h2 class="text-2xl font-semibold mt-4 mb-6">Why complete your business profile?</h2>
                    <ul class="space-y-4 text-white/90">
                        <li class="flex gap-3">
                            <span class="text-emerald-300">✓</span>
                            <div>
                                <div class="font-medium">Unlock Full Dashboard</div>
                                <div class="text-sm text-white/70">Access all features including locations, measurements, and reports</div>
                            </div>
                        </li>
                        <li class="flex gap-3">
                            <span class="text-emerald-300">✓</span>
                            <div>
                                <div class="font-medium">AI-Powered Insights</div>
                                <div class="text-sm text-white/70">Get smart recommendations based on your industry and data</div>
                            </div>
                        </li>
                        <li class="flex gap-3">
                            <span class="text-emerald-300">✓</span>
                            <div>
                                <div class="font-medium">Compliance Ready</div>
                                <div class="text-sm text-white/70">Meet UAE Net Zero 2050 and international standards</div>
                            </div>
                        </li>
                        <li class="flex gap-3">
                            <span class="text-emerald-300">✓</span>
                            <div>
                                <div class="font-medium">Team Collaboration</div>
                                <div class="text-sm text-white/70">Invite team members and assign roles for better management</div>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
