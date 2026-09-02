{{--
    Location set-up — single form.

    This replaced a 3-"step" wizard that was cosmetic: all three panels lived
    inside ONE <form> posting to locations.store, and the step JS only toggled
    display:none. Worse, the Next buttons fired AJAX at locations.store-step,
    which created the location immediately and stashed location_id in the
    session; "Save and Close" then submitted the real form to store(), creating
    a SECOND location. Whether you got a duplicate — and whether measurement
    periods existed at all — depended on which buttons you happened to click.

    One form, one POST, one location. storeStep() and its route are gone.

    Required set is deliberately narrow: a field is required only when the
    calculation engine is wrong without it.
      - country          → emission factors are region-matched (factors are
                           tagged 'UAE' etc; see EmissionCalculationService::76)
      - fiscal_year_start,
        reporting_period,
        measurement_frequency
                         → these three define the measurement periods. They
                           were nullable, which is how a location could exist
                           that no measurement could ever attach to.
      - staff_count      → copied onto every Measurement; intensity denominator

    Everything else stays optional. The utility questions in particular need a
    bill in hand to answer, and only matter at first electricity entry — so
    they are collapsed, not blocking.
--}}
@extends('layouts.app')

@section('title', 'Add New Location - MenetZero')
@section('page-title', 'Add New Location')

@section('content')
@php
    $isOnboarding = request('onboarding')
        || (isset($company) && $company->locations()->where('is_active', true)->count() === 0);

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

    $currentYear = (int) date('Y');
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

    @if($isOnboarding)
        @include('partials.onboarding-stepper', ['current' => 'location'])
    @endif

    @foreach(['success' => 'green', 'info' => 'blue', 'error' => 'red'] as $key => $tone)
        @if(session($key))
            <div class="bg-{{ $tone }}-50 border border-{{ $tone }}-200 text-{{ $tone }}-800 px-4 py-3 rounded-lg mb-4">
                {{ session($key) }}
            </div>
        @endif
    @endforeach

    <div class="mb-6">
        <h2 class="text-2xl font-bold text-gray-900">Set up your location</h2>
        <p class="text-gray-600 mt-1">
            Takes about a minute. You can add more locations later, and change any of this at any time.
        </p>
    </div>

    @if($errors->any())
        <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg mb-6">
            <p class="font-medium">Please check the highlighted fields.</p>
            <ul class="list-disc list-inside text-sm mt-1">
                @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('locations.store') }}" class="space-y-6">
        @csrf
        @if($isOnboarding)
            <input type="hidden" name="onboarding" value="1">
        @endif

        {{-- ── Location ────────────────────────────────────────────── --}}
        <div class="bg-white rounded-lg border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-1">Location</h3>
            <p class="text-sm text-gray-500 mb-5">Where this site is. The country sets which emission factors we use.</p>

            <div class="space-y-4">
                <div>
                    <label class="lbl" for="name">Location name <span class="req">*</span></label>
                    <input type="text" id="name" name="name" value="{{ old('name') }}" required
                           placeholder="e.g. Head Office, Dubai Warehouse" class="fld">
                    @error('name')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="lbl" for="country">Country <span class="req">*</span></label>
                        <select name="country" id="country" required class="fld">
                            <option value="">Select a country</option>
                            @foreach($countries as $code => $label)
                                <option value="{{ $code }}" @selected(old('country') === $code)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <p class="text-xs text-gray-500 mt-1">Determines your grid electricity and fuel factors.</p>
                        @error('country')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="lbl" for="city">City</label>
                        {{-- data-selected lets the JS restore old('city') after a
                             failed submit. The previous version rebuilt this list
                             on change only, so the choice was silently lost. --}}
                        <select name="city" id="city" class="fld" data-selected="{{ old('city') }}">
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
                            <option value="{{ $type }}" @selected(old('location_type') === $type)>{{ $type }}</option>
                        @endforeach
                    </select>
                    @error('location_type')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="lbl" for="address">Address</label>
                    <textarea name="address" id="address" rows="2"
                              placeholder="Enter the full address" class="fld">{{ old('address') }}</textarea>
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
                           value="{{ old('staff_count') }}" placeholder="10" class="fld">
                    <p class="text-xs text-gray-500 mt-1">
                        Include full-time, flexible and remote employees. Exclude contractors not on your payroll.
                    </p>
                    @error('staff_count')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>

                <div class="flex items-center justify-between border-t pt-4">
                    <p class="text-sm font-medium text-gray-900">Have staff regularly worked from home?</p>
                    <label class="toggle-switch">
                        <input type="checkbox" id="wfh_toggle" name="staff_work_from_home" value="1"
                               {{ old('staff_work_from_home') ? 'checked' : '' }}>
                        <span class="slider"></span>
                    </label>
                </div>

                <div id="wfh-percentage-section" class="md:w-1/2 {{ old('staff_work_from_home') ? '' : 'hidden' }}">
                    <label class="lbl" for="work_from_home_percentage">Percentage of staff working from home</label>
                    <input type="number" id="work_from_home_percentage" name="work_from_home_percentage"
                           min="0" max="100" step="0.01"
                           value="{{ old('work_from_home_percentage', 100) }}" class="fld">
                    @error('work_from_home_percentage')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>
            </div>
        </div>

        {{-- ── Reporting period ───────────────────────────────────── --}}
        <div class="bg-white rounded-lg border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-1">Reporting period</h3>
            <p class="text-sm text-gray-500 mb-5">
                This creates the periods you enter data into. You can add more years later.
            </p>

            <div class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="lbl" for="fiscal_year_start">Fiscal year starts <span class="req">*</span></label>
                        <select name="fiscal_year_start" id="fiscal_year_start" required class="fld">
                            @foreach($months as $month)
                                <option value="{{ $month }}" @selected(old('fiscal_year_start', 'January') === $month)>{{ $month }}</option>
                            @endforeach
                        </select>
                        @error('fiscal_year_start')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="lbl" for="reporting_period">Reporting year <span class="req">*</span></label>
                        <select name="reporting_period" id="reporting_period" required class="fld">
                            @for($year = 2020; $year <= 2030; $year++)
                                <option value="{{ $year }}" @selected((int) old('reporting_period', $currentYear) === $year)>{{ $year }}</option>
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
                                       @checked(old('measurement_frequency', 'Annually') === $freq)
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

        {{-- ── Utility bills (optional) ───────────────────────────── --}}
        <details class="bg-white rounded-lg border border-gray-200 p-6">
            <summary class="cursor-pointer font-semibold text-gray-900">
                Utility and building details
                <span class="font-normal text-sm text-gray-500">— optional, you can set this later</span>
            </summary>

            <p class="text-sm text-gray-500 mt-3">
                Not sure? Leave these. They only affect how we treat shared-building electricity,
                and you can change them before entering your first bill.
            </p>

            <div class="space-y-4 mt-4">
                <div class="flex items-center justify-between">
                    <p class="text-sm font-medium text-gray-900">Do you receive the utility bills for the entire office building?</p>
                    <label class="toggle-switch">
                        <input type="checkbox" id="receives_utility_bills" name="receives_utility_bills" value="1"
                               {{ old('receives_utility_bills') ? 'checked' : '' }}>
                        <span class="slider"></span>
                    </label>
                </div>

                <div class="bg-gray-50 rounded-lg p-4 {{ old('receives_utility_bills') ? 'hidden' : '' }}" id="building-details">
                    <h5 class="text-sm font-semibold text-gray-900 mb-3">Office building details</h5>
                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <p class="text-sm font-medium text-gray-900">Do you pay your proportion of the electricity bill for this location?</p>
                            <label class="toggle-switch">
                                <input type="checkbox" name="pays_electricity_proportion" value="1"
                                       {{ old('pays_electricity_proportion') ? 'checked' : '' }}>
                                <span class="slider"></span>
                            </label>
                        </div>
                        <div class="flex items-center justify-between">
                            <p class="text-sm font-medium text-gray-900">Is your office space part of a larger building with shared services (lifts, lobbies, aircon)?</p>
                            <label class="toggle-switch">
                                <input type="checkbox" name="shared_building_services" value="1"
                                       {{ old('shared_building_services') ? 'checked' : '' }}>
                                <span class="slider"></span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        </details>

        <div class="flex justify-between items-center">
            <a href="{{ $isOnboarding ? route('client.dashboard') : route('locations.index') }}"
               class="px-4 py-2 text-gray-700 hover:text-gray-900">Cancel</a>
            <button type="submit" class="px-6 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700 transition">
                {{ $isOnboarding ? 'Save and start entering data' : 'Save location' }}
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

        list.forEach(function (city) {
            var opt = document.createElement('option');
            opt.value = city;
            opt.textContent = city;
            if (city === want) { opt.selected = true; }
            cityEl.appendChild(opt);
        });
    }

    countryEl.addEventListener('change', function () { fillCities(false); });

    // Utility toggle: receiving the whole building's bill makes the
    // apportionment questions meaningless, so hide and clear them.
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

    // Frequency is the one choice whose consequences people do not anticipate.
    var preview = document.getElementById('period-preview');
    function showPreview() {
        var picked = document.querySelector('.freq-radio:checked');
        if (!picked) { preview.textContent = ''; return; }
        var n = parseInt(picked.getAttribute('data-periods'), 10);
        var year = document.getElementById('reporting_period').value;
        preview.textContent = 'Creates ' + n + (n === 1 ? ' period' : ' periods')
            + ' for ' + year + ' that you can enter data into.';
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
