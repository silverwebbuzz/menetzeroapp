{{--
    Edit location — mirrors locations/create.blade.php field for field.

    The bug this replaced: the form posted 15 fields and LocationController::
    update() validated and wrote only 7. country, city, address, location_type
    and the three utility toggles were silently discarded, so a location saved
    here kept its blank country while reporting "updated successfully". That is
    why rows created by the old AJAX wizard could never be repaired from the UI.

    Required set matches store()/update(): name, country, staff_count,
    fiscal_year_start, reporting_period, measurement_frequency.

    Year range is 2020–2030 to match store()'s validation. It previously ran
    date('Y') down to 2020, so 2027-2030 were valid on create but unreachable
    here.
--}}
@extends('layouts.app')

@section('title', 'Edit Location - MenetZero')
@section('page-title', 'Edit Location')

@section('content')
@php
    $countries = [
        'UAE' => 'United Arab Emirates',
        'SA'  => 'Saudi Arabia',
        'KW'  => 'Kuwait',
        'QA'  => 'Qatar',
        'BH'  => 'Bahrain',
        'OM'  => 'Oman',
        'US'  => 'United States',
        'UK'  => 'United Kingdom',
        'IN'  => 'India',
        'Other' => 'Other',
    ];

    $locationTypes = ['Co-Working Desks', 'Office', 'Warehouse', 'Factory', 'Retail Store', 'Data Center', 'Other'];

    $months = ['January','February','March','April','May','June',
               'July','August','September','October','November','December'];

    $selectedCountry   = old('country', $location->country);
    $selectedCity      = old('city', $location->city);
    $selectedFrequency = old('measurement_frequency', $location->measurement_frequency ?: 'Annually');
    $selectedYear      = (int) old('reporting_period', $location->reporting_period ?: date('Y'));
    $wfhOn             = (bool) old('staff_work_from_home', $location->staff_work_from_home);
    $receivesBills     = (bool) old('receives_utility_bills', $location->receives_utility_bills);
@endphp

<style>
    .toggle-switch { position: relative; display: inline-block; width: 44px; height: 24px; flex: none; }
    .toggle-switch input { opacity: 0; width: 0; height: 0; }
    .slider {
        position: absolute; cursor: pointer; inset: 0;
        background-color: #ccc; transition: .3s; border-radius: 24px;
    }
    .slider:before {
        position: absolute; content: ""; height: 18px; width: 18px;
        left: 3px; bottom: 3px; background-color: #fff;
        transition: .3s; border-radius: 50%;
    }
    input:checked + .slider { background-color: #f97316; }
    input:checked + .slider:before { transform: translateX(20px); }
    .fld { width: 100%; padding: .5rem .75rem; border: 1px solid #d1d5db; border-radius: .5rem; }
    .fld:focus { outline: none; border-color: #f97316; box-shadow: 0 0 0 2px rgba(249,115,22,.35); }
    .lbl { display: block; font-size: .875rem; font-weight: 500; color: #374151; margin-bottom: .5rem; }
    .req { color: #dc2626; }
</style>

<div class="max-w-4xl mx-auto">

    <div class="mb-6">
        <h2 class="text-2xl font-bold text-gray-900">Edit location</h2>
        <p class="text-gray-600 mt-1">{{ $location->name }}</p>
    </div>

    @if (session('success'))
        <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg mb-6">
            {{ session('success') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg mb-6">
            <p class="font-medium">Please check the highlighted fields.</p>
            <ul class="list-disc list-inside text-sm mt-1">
                @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    @endif

    @if (blank($location->country))
        <div class="bg-amber-50 border border-amber-200 text-amber-800 px-4 py-3 rounded-lg mb-6">
            <p class="font-medium">This location is missing its country.</p>
            <p class="text-sm mt-1">
                Emission factors are matched by country, so figures for this location may be using
                generic factors. Set it below and save.
            </p>
        </div>
    @endif

    <form method="POST" action="{{ route('locations.update', $location) }}" class="space-y-6">
        @csrf
        @method('PUT')

        {{-- ── Location ────────────────────────────────────────────── --}}
        <div class="bg-white rounded-lg border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-1">Location</h3>
            <p class="text-sm text-gray-500 mb-5">Where this site is. The country sets which emission factors we use.</p>

            <div class="space-y-4">
                <div>
                    <label class="lbl" for="name">Location name <span class="req">*</span></label>
                    <input type="text" id="name" name="name" required
                           value="{{ old('name', $location->name) }}"
                           placeholder="e.g. Head Office, Dubai Warehouse" class="fld">
                    @error('name')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="lbl" for="country">Country <span class="req">*</span></label>
                        <select name="country" id="country" required class="fld">
                            <option value="">Select a country</option>
                            @foreach($countries as $code => $label)
                                <option value="{{ $code }}" @selected($selectedCountry === $code)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <p class="text-xs text-gray-500 mt-1">Determines your grid electricity and fuel factors.</p>
                        @error('country')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="lbl" for="city">City</label>
                        {{-- Single source for the city list, in the JS below.
                             The old view hardcoded it twice — once for change,
                             once for load — and the two copies could drift. --}}
                        <select name="city" id="city" class="fld" data-selected="{{ $selectedCity }}">
                            <option value="">Select country first</option>
                        </select>
                        @error('city')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div>
                    <label class="lbl" for="location_type">Location type</label>
                    <select name="location_type" id="location_type" class="fld">
                        <option value="">Select location type</option>
                        @foreach($locationTypes as $type)
                            <option value="{{ $type }}" @selected(old('location_type', $location->location_type) === $type)>{{ $type }}</option>
                        @endforeach
                    </select>
                    @error('location_type')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="lbl" for="address">Address</label>
                    <textarea name="address" id="address" rows="2"
                              placeholder="Enter the full address" class="fld">{{ old('address', $location->address) }}</textarea>
                    @error('address')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>
            </div>
        </div>

        {{-- ── People ─────────────────────────────────────────────── --}}
        <div class="bg-white rounded-lg border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-1">People</h3>
            <p class="text-sm text-gray-500 mb-5">Used for per-employee intensity figures in your reports.</p>

            <div class="space-y-4">
                <div class="md:w-1/2">
                    <label class="lbl" for="staff_count">Number of staff (FTE) <span class="req">*</span></label>
                    <input type="number" id="staff_count" name="staff_count" min="1" required
                           value="{{ old('staff_count', $location->staff_count) }}" placeholder="10" class="fld">
                    <p class="text-xs text-gray-500 mt-1">
                        Include full-time, flexible and remote employees. Exclude contractors not on your payroll.
                    </p>
                    @error('staff_count')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>

                <div class="flex items-center justify-between border-t pt-4">
                    <p class="text-sm font-medium text-gray-900">Have staff regularly worked from home?</p>
                    <label class="toggle-switch">
                        <input type="checkbox" id="wfh_toggle" name="staff_work_from_home" value="1" @checked($wfhOn)>
                        <span class="slider"></span>
                    </label>
                </div>

                <div id="wfh-percentage-section" class="md:w-1/2 {{ $wfhOn ? '' : 'hidden' }}">
                    <label class="lbl" for="work_from_home_percentage">Percentage of staff working from home</label>
                    <input type="number" id="work_from_home_percentage" name="work_from_home_percentage"
                           min="0" max="100" step="0.01"
                           value="{{ old('work_from_home_percentage', $location->work_from_home_percentage ?? 100) }}"
                           class="fld">
                    @error('work_from_home_percentage')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>
            </div>
        </div>

        {{-- ── Reporting period ───────────────────────────────────── --}}
        <div class="bg-white rounded-lg border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-1">Reporting period</h3>
            <p class="text-sm text-gray-500 mb-5">
                Changing these adds any missing periods. Periods you have already entered data into are kept.
            </p>

            <div class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="lbl" for="fiscal_year_start">Fiscal year starts <span class="req">*</span></label>
                        <select name="fiscal_year_start" id="fiscal_year_start" required class="fld">
                            @foreach($months as $month)
                                <option value="{{ $month }}" @selected(old('fiscal_year_start', $location->fiscal_year_start ?: 'January') === $month)>{{ $month }}</option>
                            @endforeach
                        </select>
                        @error('fiscal_year_start')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="lbl" for="reporting_period">Reporting year <span class="req">*</span></label>
                        <select name="reporting_period" id="reporting_period" required class="fld">
                            @for($year = 2020; $year <= 2030; $year++)
                                <option value="{{ $year }}" @selected($selectedYear === $year)>{{ $year }}</option>
                            @endfor
                        </select>
                        @error('reporting_period')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div>
                    <span class="lbl">Measurement frequency <span class="req">*</span></span>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
                        @foreach(['Annually' => 1, 'Half Yearly' => 2, 'Quarterly' => 4, 'Monthly' => 12] as $freq => $count)
                            <label class="flex items-center border border-gray-300 rounded-lg px-3 py-2 cursor-pointer hover:bg-gray-50">
                                <input type="radio" name="measurement_frequency" value="{{ $freq }}"
                                       data-periods="{{ $count }}" required
                                       @checked($selectedFrequency === $freq)
                                       class="mr-2 freq-radio">
                                <span class="text-sm">{{ $freq }}</span>
                            </label>
                        @endforeach
                    </div>
                    <p class="text-sm text-gray-600 mt-2" id="period-preview"></p>
                    @error('measurement_frequency')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>
            </div>
        </div>

        {{-- ── Utility bills ──────────────────────────────────────── --}}
        <details class="bg-white rounded-lg border border-gray-200 p-6" {{ $receivesBills ? 'open' : '' }}>
            <summary class="cursor-pointer font-semibold text-gray-900">
                Utility and building details
                <span class="font-normal text-sm text-gray-500">— optional</span>
            </summary>

            <div class="space-y-4 mt-4">
                <div class="flex items-center justify-between">
                    <p class="text-sm font-medium text-gray-900">Do you receive the utility bills for the entire office building?</p>
                    <label class="toggle-switch">
                        <input type="checkbox" id="receives_utility_bills" name="receives_utility_bills" value="1" @checked($receivesBills)>
                        <span class="slider"></span>
                    </label>
                </div>

                <div class="bg-gray-50 rounded-lg p-4 {{ $receivesBills ? 'hidden' : '' }}" id="building-details">
                    <h5 class="text-sm font-semibold text-gray-900 mb-3">Office building details</h5>
                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <p class="text-sm font-medium text-gray-900">Do you pay your proportion of the electricity bill for this location?</p>
                            <label class="toggle-switch">
                                <input type="checkbox" name="pays_electricity_proportion" value="1"
                                       @checked(old('pays_electricity_proportion', $location->pays_electricity_proportion))>
                                <span class="slider"></span>
                            </label>
                        </div>
                        <div class="flex items-center justify-between">
                            <p class="text-sm font-medium text-gray-900">Is your office space part of a larger building with shared services (lifts, lobbies, aircon)?</p>
                            <label class="toggle-switch">
                                <input type="checkbox" name="shared_building_services" value="1"
                                       @checked(old('shared_building_services', $location->shared_building_services))>
                                <span class="slider"></span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        </details>

        {{-- ── Head office ────────────────────────────────────────── --}}
        <div class="bg-white rounded-lg border border-gray-200 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-900">Is this your head office?</p>
                    <p class="text-xs text-gray-500 mt-1">
                        Only one location can be the head office. Setting it here removes it from the current one.
                    </p>
                </div>
                <label class="toggle-switch">
                    <input type="checkbox" name="is_head_office" value="1"
                           @checked(old('is_head_office', $location->is_head_office))>
                    <span class="slider"></span>
                </label>
            </div>
            @error('is_head_office')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
        </div>

        <div class="flex justify-between items-center">
            <a href="{{ route('locations.index') }}" class="px-4 py-2 text-gray-700 hover:text-gray-900">Cancel</a>
            <button type="submit" class="px-6 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700 transition">
                Save changes
            </button>
        </div>
    </form>
</div>

<script>
(function () {
    var CITIES = {
        'UAE': ['Dubai', 'Abu Dhabi', 'Sharjah', 'Ajman', 'Ras Al Khaimah', 'Fujairah', 'Umm Al Quwain'],
        'SA':  ['Riyadh', 'Jeddah', 'Mecca', 'Medina', 'Dammam'],
        'KW':  ['Kuwait City', 'Hawalli', 'Ahmadi'],
        'QA':  ['Doha', 'Al Rayyan', 'Al Wakrah'],
        'BH':  ['Manama', 'Riffa', 'Muharraq'],
        'OM':  ['Muscat', 'Salalah', 'Nizwa'],
        'US':  ['New York', 'Los Angeles', 'Chicago', 'Houston', 'Phoenix'],
        'UK':  ['London', 'Birmingham', 'Manchester', 'Glasgow', 'Liverpool'],
        'IN':  ['Mumbai', 'Delhi', 'Bangalore', 'Chennai', 'Kolkata']
    };

    var countryEl = document.getElementById('country');
    var cityEl    = document.getElementById('city');

    function fillCities(keepSelection) {
        var list = CITIES[countryEl.value] || [];
        var want = keepSelection ? cityEl.getAttribute('data-selected') : '';
        cityEl.innerHTML = '';

        var first = document.createElement('option');
        first.value = '';
        first.textContent = list.length ? 'Select city' : 'Select country first';
        cityEl.appendChild(first);

        var matched = false;
        list.forEach(function (city) {
            var opt = document.createElement('option');
            opt.value = city;
            opt.textContent = city;
            if (city === want) { opt.selected = true; matched = true; }
            cityEl.appendChild(opt);
        });

        // A stored city that is not in the list for this country (free text
        // from an older record) would otherwise vanish on save. Keep it.
        if (want && !matched) {
            var custom = document.createElement('option');
            custom.value = want;
            custom.textContent = want;
            custom.selected = true;
            cityEl.appendChild(custom);
        }
    }

    countryEl.addEventListener('change', function () { fillCities(false); });

    var receives = document.getElementById('receives_utility_bills');
    var details  = document.getElementById('building-details');
    receives.addEventListener('change', function () {
        if (this.checked) {
            details.classList.add('hidden');
            document.querySelector('input[name="pays_electricity_proportion"]').checked = false;
            document.querySelector('input[name="shared_building_services"]').checked = false;
        } else {
            details.classList.remove('hidden');
        }
    });

    var wfh = document.getElementById('wfh_toggle');
    var wfhSection = document.getElementById('wfh-percentage-section');
    wfh.addEventListener('change', function () {
        wfhSection.classList.toggle('hidden', !this.checked);
    });

    var preview = document.getElementById('period-preview');
    function showPreview() {
        var picked = document.querySelector('.freq-radio:checked');
        if (!picked) { preview.textContent = ''; return; }
        var n = parseInt(picked.getAttribute('data-periods'), 10);
        var year = document.getElementById('reporting_period').value;
        preview.textContent = 'Creates ' + n + (n === 1 ? ' period' : ' periods')
            + ' for ' + year + '. Periods you have already entered data into are kept.';
    }
    document.querySelectorAll('.freq-radio').forEach(function (el) {
        el.addEventListener('change', showPreview);
    });
    document.getElementById('reporting_period').addEventListener('change', showPreview);

    fillCities(true);
    showPreview();
})();
</script>
@endsection
