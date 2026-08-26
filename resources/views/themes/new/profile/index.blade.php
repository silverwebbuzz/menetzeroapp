{{--
    MENetZero 2.0 - My Profile (Phase 6 body migration).

    THREE POST FORMS, all preserved with their csrf tokens:
        profile.update.personal
        profile.update.password
        profile.update.company   (multipart - carries the logo upload)

    TAB CONTRACT - load-bearing. showTab() lives in the shared partial
    profile/partials/index-scripts and is called from inline onclick handlers.
    It requires, and this page provides:
      - class .tab-button on each tab, toggling .active / .inactive
      - class .tab-content on each panel, toggling .active
      - id pairs {name}-tab and {name}-content for personal, company, password
    Renaming any of those stops the tabs switching. The CSS below is this
    theme's own; only the class NAMES are shared.

    SECTOR CASCADE, also in the shared partial: reads ids sector, industry and
    business_subcategory, plus the data-id attribute on each option, and calls
    /api/industries and /api/subcategories. Those ids and attributes are
    reproduced exactly.

    No plan gating on this page - verified against the original, zero gate calls.

    Controller data: $user $company $sectors $industries $subcategories
--}}
@extends('layouts.app')

@section('title', 'My Profile - MenetZero')
@section('page-title', 'My Profile')

@push('styles')
    <style>
        .pf-tabs { display: flex; gap: 4px; flex-wrap: wrap; }
        .tab-button { display: inline-flex; align-items: center; gap: 7px;
            height: 32px; padding: 0 12px; font-size: 12.5px; font-weight: 500;
            border: 1px solid transparent; background: none; cursor: pointer;
            color: var(--ink-2); white-space: nowrap; }
        .tab-button svg { width: 15px; height: 15px; }
        .tab-button.active { background: var(--accent-tint);
            border-color: var(--accent-line); color: var(--accent); font-weight: 600; }
        .tab-button.inactive { background: none; color: var(--ink-3); border-color: transparent; }
        .tab-button.inactive:hover { background: var(--canvas-2); color: var(--ink); }
        .tab-content { display: none; }
        .tab-content.active { display: block; }

        .pf-grid { display: grid; gap: 16px; grid-template-columns: repeat(2, minmax(0, 1fr)); }
        @media (max-width: 860px) { .pf-grid { grid-template-columns: 1fr; } }
        .pf-avatar { flex-shrink: 0; width: 52px; height: 52px; border-radius: 50%;
            background: var(--accent-tint); border: 1px solid var(--accent-line);
            display: flex; align-items: center; justify-content: center;
            font-size: 19px; font-weight: 600; color: var(--accent); }
        .pf-logo { width: 88px; height: 88px; border: 1px solid var(--line);
            background: var(--canvas); display: flex; align-items: center;
            justify-content: center; overflow: hidden; flex-shrink: 0; }
        .pf-logo img { max-width: 100%; max-height: 100%; object-fit: contain; }
        .pf-err { font-size: 11.5px; color: var(--bad); margin-top: 4px; }
        .pf-hint { font-size: 11.5px; color: var(--ink-3); margin-top: 4px; }
        .pf-sub { border-top: 1px solid var(--line); padding-top: 18px; margin-top: 18px; }
        .pf-sub h4 { font-size: 12.5px; font-weight: 600; margin: 0 0 12px; }
    </style>
@endpush

@section('content')
<div class="mnz-stack" data-pillar="neutral">

    <div class="mnz-pagehead">
        <div>
            <div class="mnz-kicker">Settings</div>
            <h1>My Profile</h1>
            <p class="mnz-lead">Manage your personal details, company information and security settings.</p>
        </div>
    </div>

    {{-- Summary --}}
    <div class="mnz-panel">
        <div class="mnz-panel__body">
            <div style="display:flex;align-items:center;gap:14px;flex-wrap:wrap">
                <div class="pf-avatar">{{ strtoupper(substr($user->name, 0, 1)) }}</div>
                <div style="min-width:0;flex:1">
                    <div style="font-size:15px;font-weight:600">{{ $user->name }}</div>
                    <div style="font-size:12.5px;color:var(--ink-3)">{{ $user->email }}</div>
                    <div style="display:flex;align-items:center;gap:7px;margin-top:6px;flex-wrap:wrap">
                        <span style="font-size:11.5px;color:var(--ink-3)">{{ $user->designation ?? 'No designation' }}</span>
                        @if($company)
                            <span class="mnz-chip mnz-chip--ok">{{ $company->name }}</span>
                        @else
                            <span class="mnz-chip mnz-chip--warn">No company</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Tabs --}}
    <div class="pf-tabs" role="tablist">
        <button onclick="showTab('personal')" id="personal-tab" class="tab-button active" type="button">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
            </svg>
            Personal
        </button>
        <button onclick="showTab('company')" id="company-tab" class="tab-button inactive" type="button">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
            </svg>
            Company
        </button>
        <button onclick="showTab('password')" id="password-tab" class="tab-button inactive" type="button">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
            </svg>
            Password
        </button>
    </div>

    {{-- Personal --}}
    <div id="personal-content" class="tab-content active">
        <div class="mnz-panel">
            <div class="mnz-panel__head">
                <h2 style="font-size:14px;font-weight:600;margin:0">Personal Information</h2>
            </div>
            <div class="mnz-panel__body">
                <form method="POST" action="{{ route('profile.update.personal') }}">
                    @csrf
                    <div class="pf-grid">
                        <div class="mnz-field">
                            <label class="mnz-label" for="name">Full Name *</label>
                            <input type="text" name="name" id="name" value="{{ old('name', $user->name) }}" class="mnz-input" required>
                            @error('name')<p class="pf-err">{{ $message }}</p>@enderror
                        </div>
                        <div class="mnz-field">
                            <label class="mnz-label" for="email_display">Email Address</label>
                            <input type="email" id="email_display" value="{{ $user->email }}" class="mnz-input" disabled>
                            <p class="pf-hint">Email cannot be changed</p>
                        </div>
                        <div class="mnz-field">
                            <label class="mnz-label" for="phone">Phone Number</label>
                            <input type="tel" name="phone" id="phone" value="{{ old('phone', $user->phone) }}" class="mnz-input">
                            @error('phone')<p class="pf-err">{{ $message }}</p>@enderror
                        </div>
                        <div class="mnz-field">
                            <label class="mnz-label" for="designation">Designation</label>
                            <input type="text" name="designation" id="designation" value="{{ old('designation', $user->designation) }}" class="mnz-input">
                            @error('designation')<p class="pf-err">{{ $message }}</p>@enderror
                        </div>
                    </div>
                    <div style="display:flex;justify-content:flex-end;margin-top:18px">
                        <button type="submit" class="mnz-btn mnz-btn--primary">Update Personal Information</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Password --}}
    <div id="password-content" class="tab-content">
        <div class="mnz-panel">
            <div class="mnz-panel__head">
                <h2 style="font-size:14px;font-weight:600;margin:0">Change Password</h2>
            </div>
            <div class="mnz-panel__body">
                <form method="POST" action="{{ route('profile.update.password') }}">
                    @csrf
                    <div style="max-width:34rem;display:flex;flex-direction:column;gap:16px">
                        <div class="mnz-field">
                            <label class="mnz-label" for="current_password">Current Password *</label>
                            <input type="password" name="current_password" id="current_password" class="mnz-input" required autocomplete="current-password">
                            @error('current_password')<p class="pf-err">{{ $message }}</p>@enderror
                        </div>
                        <div class="mnz-field">
                            <label class="mnz-label" for="new_password">New Password *</label>
                            <input type="password" name="new_password" id="new_password" class="mnz-input" required autocomplete="new-password" minlength="8">
                            <p class="pf-hint">Password must be at least 8 characters long.</p>
                            @error('new_password')<p class="pf-err">{{ $message }}</p>@enderror
                        </div>
                        <div class="mnz-field">
                            <label class="mnz-label" for="new_password_confirmation">Confirm New Password *</label>
                            <input type="password" name="new_password_confirmation" id="new_password_confirmation" class="mnz-input" required autocomplete="new-password" minlength="8">
                            @error('new_password_confirmation')<p class="pf-err">{{ $message }}</p>@enderror
                        </div>
                        <div class="mnz-panel" style="border-color:var(--accent-line);background:var(--accent-tint)">
                            <div class="mnz-panel__body" style="font-size:12.5px">
                                <p style="margin:0 0 6px;font-weight:600">Password Requirements:</p>
                                <ul style="margin:0;padding-left:18px">
                                    <li>Minimum 8 characters</li>
                                    <li>Use a combination of letters, numbers, and symbols for better security</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div style="display:flex;justify-content:flex-end;margin-top:18px">
                        <button type="submit" class="mnz-btn mnz-btn--primary">Update Password</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Company --}}
    <div id="company-content" class="tab-content">
        @if($company)
            <div class="mnz-panel">
                <div class="mnz-panel__head">
                    <h2 style="font-size:14px;font-weight:600;margin:0">Company Information</h2>
                </div>
                <div class="mnz-panel__body">
                    @error('company')
                        <div class="mnz-panel" style="border-color:var(--bad-line);background:var(--bad-tint);margin-bottom:16px">
                            <div class="mnz-panel__body" style="color:var(--bad);font-size:12.5px">{{ $message }}</div>
                        </div>
                    @enderror
                    @if(session('success'))
                        <div class="mnz-panel" style="border-color:var(--ok-line);background:var(--ok-tint);margin-bottom:16px">
                            <div class="mnz-panel__body" style="color:var(--ok);font-size:12.5px">{{ session('success') }}</div>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('profile.update.company') }}" enctype="multipart/form-data">
                        @csrf

                        {{-- Logo --}}
                        <div style="border-bottom:1px solid var(--line);padding-bottom:18px">
                            <h4 style="font-size:12.5px;font-weight:600;margin:0 0 4px">Company Logo</h4>
                            <p class="pf-hint" style="margin:0 0 12px">Upload your logo to appear on PDF GHG inventory reports. PNG or JPG recommended (max 2 MB).</p>
                            <div style="display:flex;align-items:center;gap:18px;flex-wrap:wrap">
                                <div class="pf-logo">
                                    @if($company->logo_url)
                                        <img src="{{ $company->logo_url }}" alt="{{ $company->name }} logo">
                                    @else
                                        <span style="font-size:24px;font-weight:700;color:var(--accent)">{{ strtoupper(substr($company->name, 0, 1)) }}</span>
                                    @endif
                                </div>
                                <div style="display:flex;flex-direction:column;gap:10px">
                                    <input type="file" name="company_logo" accept="image/jpeg,image/png,image/webp" style="font-size:12.5px">
                                    @error('company_logo')<p class="pf-err">{{ $message }}</p>@enderror
                                    @if($company->logo_path)
                                        <label style="display:inline-flex;align-items:center;gap:7px;font-size:12.5px;color:var(--ink-2)">
                                            <input type="checkbox" name="remove_logo" value="1">
                                            Remove current logo
                                        </label>
                                    @endif
                                </div>
                            </div>
                        </div>

                        {{-- Basics --}}
                        <div class="pf-grid" style="margin-top:18px">
                            <div class="mnz-field">
                                <label class="mnz-label" for="company_name">Company Name *</label>
                                <input type="text" name="company_name" id="company_name" value="{{ old('company_name', $company->name) }}" class="mnz-input" required>
                                @error('company_name')<p class="pf-err">{{ $message }}</p>@enderror
                            </div>
                            <div class="mnz-field">
                                <label class="mnz-label" for="business_email">Business Email</label>
                                <input type="email" name="business_email" id="business_email" value="{{ old('business_email', $company->email) }}" class="mnz-input">
                                @error('business_email')<p class="pf-err">{{ $message }}</p>@enderror
                            </div>
                            <div class="mnz-field">
                                <label class="mnz-label" for="business_website">Business Website</label>
                                <input type="url" name="business_website" id="business_website" value="{{ old('business_website', $company->website) }}" class="mnz-input">
                                @error('business_website')<p class="pf-err">{{ $message }}</p>@enderror
                            </div>
                            <div class="mnz-field">
                                <label class="mnz-label" for="country">Country</label>
                                <select name="country" id="country" class="mnz-select">
                                    <option value="">Select Country</option>
                                    <option value="UAE" {{ old('country', $company->country) == 'UAE' ? 'selected' : '' }}>United Arab Emirates</option>
                                    <option value="SA" {{ old('country', $company->country) == 'SA' ? 'selected' : '' }}>Saudi Arabia</option>
                                    <option value="KW" {{ old('country', $company->country) == 'KW' ? 'selected' : '' }}>Kuwait</option>
                                    <option value="QA" {{ old('country', $company->country) == 'QA' ? 'selected' : '' }}>Qatar</option>
                                    <option value="BH" {{ old('country', $company->country) == 'BH' ? 'selected' : '' }}>Bahrain</option>
                                    <option value="OM" {{ old('country', $company->country) == 'OM' ? 'selected' : '' }}>Oman</option>
                                    <option value="US" {{ old('country', $company->country) == 'US' ? 'selected' : '' }}>United States</option>
                                    <option value="UK" {{ old('country', $company->country) == 'UK' ? 'selected' : '' }}>United Kingdom</option>
                                    <option value="IN" {{ old('country', $company->country) == 'IN' ? 'selected' : '' }}>India</option>
                                    <option value="Other" {{ old('country', $company->country) == 'Other' ? 'selected' : '' }}>Other</option>
                                </select>
                                @error('country')<p class="pf-err">{{ $message }}</p>@enderror
                            </div>
                        </div>

                        <div class="mnz-field" style="margin-top:16px">
                            <label class="mnz-label" for="business_address">Business Address</label>
                            <textarea name="business_address" id="business_address" rows="3" class="mnz-textarea">{{ old('business_address', $company->address) }}</textarea>
                            @error('business_address')<p class="pf-err">{{ $message }}</p>@enderror
                        </div>

                        {{-- UAE --}}
                        <div class="pf-sub">
                            <h4>UAE Specific Information</h4>
                            <div class="pf-grid">
                                <div class="mnz-field">
                                    <label class="mnz-label" for="emirate">Emirate</label>
                                    <select name="emirate" id="emirate" class="mnz-select">
                                        <option value="">Select Emirate</option>
                                        <option value="Abu Dhabi" {{ old('emirate', $company->emirate) == 'Abu Dhabi' ? 'selected' : '' }}>Abu Dhabi</option>
                                        <option value="Dubai" {{ old('emirate', $company->emirate) == 'Dubai' ? 'selected' : '' }}>Dubai</option>
                                        <option value="Sharjah" {{ old('emirate', $company->emirate) == 'Sharjah' ? 'selected' : '' }}>Sharjah</option>
                                        <option value="Ajman" {{ old('emirate', $company->emirate) == 'Ajman' ? 'selected' : '' }}>Ajman</option>
                                        <option value="Ras Al Khaimah" {{ old('emirate', $company->emirate) == 'Ras Al Khaimah' ? 'selected' : '' }}>Ras Al Khaimah</option>
                                        <option value="Fujairah" {{ old('emirate', $company->emirate) == 'Fujairah' ? 'selected' : '' }}>Fujairah</option>
                                        <option value="Umm Al Quwain" {{ old('emirate', $company->emirate) == 'Umm Al Quwain' ? 'selected' : '' }}>Umm Al Quwain</option>
                                    </select>
                                    @error('emirate')<p class="pf-err">{{ $message }}</p>@enderror
                                </div>
                                <div class="mnz-field">
                                    <label class="mnz-label" for="license_no">License Number</label>
                                    <input type="text" name="license_no" id="license_no" value="{{ old('license_no', $company->license_no) }}" class="mnz-input">
                                    @error('license_no')<p class="pf-err">{{ $message }}</p>@enderror
                                </div>
                            </div>
                        </div>

                        {{-- Business details. The three ids below are read by the
                             shared cascade script - do not rename. --}}
                        <div class="pf-sub">
                            <h4>Business Details</h4>
                            <div class="pf-grid">
                                <div class="mnz-field">
                                    <label class="mnz-label" for="sector">Sector</label>
                                    <select name="sector" id="sector" class="mnz-select">
                                        <option value="">Select Sector</option>
                                        @foreach($sectors as $sector)
                                            <option value="{{ $sector->name }}" data-id="{{ $sector->id }}" {{ old('sector', $company->sector) == $sector->name ? 'selected' : '' }}>{{ $sector->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('sector')<p class="pf-err">{{ $message }}</p>@enderror
                                </div>
                                <div class="mnz-field">
                                    <label class="mnz-label" for="industry">Industry</label>
                                    <select name="industry" id="industry" class="mnz-select" {{ $industries->isEmpty() ? 'disabled' : '' }}>
                                        <option value="">Select Industry</option>
                                        @foreach($industries as $industry)
                                            <option value="{{ $industry->name }}" data-id="{{ $industry->id }}" {{ old('industry', $company->industry) == $industry->name ? 'selected' : '' }}>{{ $industry->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('industry')<p class="pf-err">{{ $message }}</p>@enderror
                                </div>
                                <div class="mnz-field">
                                    <label class="mnz-label" for="business_subcategory">Business Subcategory</label>
                                    <select name="business_subcategory" id="business_subcategory" class="mnz-select" {{ $subcategories->isEmpty() ? 'disabled' : '' }}>
                                        <option value="">Select Subcategory (Optional)</option>
                                        @foreach($subcategories as $subcategory)
                                            <option value="{{ $subcategory->name }}" {{ old('business_subcategory', $company->business_subcategory) == $subcategory->name ? 'selected' : '' }}>{{ $subcategory->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('business_subcategory')<p class="pf-err">{{ $message }}</p>@enderror
                                </div>
                            </div>

                            <div class="mnz-field" style="margin-top:16px">
                                <label class="mnz-label" for="business_description">Business Description</label>
                                <textarea name="business_description" id="business_description" rows="4" class="mnz-textarea">{{ old('business_description', $company->description) }}</textarea>
                                @error('business_description')<p class="pf-err">{{ $message }}</p>@enderror
                            </div>
                        </div>

                        <div style="display:flex;justify-content:flex-end;margin-top:18px">
                            <button type="submit" class="mnz-btn mnz-btn--primary">Update Company Information</button>
                        </div>
                    </form>
                </div>
            </div>
        @else
            <div class="mnz-panel">
                <div class="mnz-empty">
                    <div class="mnz-empty__title">No Company Associated</div>
                    <div class="mnz-empty__text">You don't have a company profile yet. Complete your business profile to get started.</div>
                    <a href="{{ route('client.dashboard') }}" class="mnz-btn mnz-btn--primary">Complete Business Profile</a>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
    {{-- Shared with the old theme - see profile/partials/index-scripts. --}}
    @include('profile.partials.index-scripts')
@endpush
