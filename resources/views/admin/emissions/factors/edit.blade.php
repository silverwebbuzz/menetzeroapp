@extends('admin.layouts.app')

@section('title', 'Edit Emission Factor | MENetZero')
@section('page-title', 'Edit Emission Factor')

@section('content')
    <div class="bg-white shadow rounded-lg p-6">
        <form method="POST" action="{{ route('admin.emissions.factors.update', $factor->id) }}">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="emission_source_id" class="block text-sm font-medium text-gray-700 mb-1">Emission Source *</label>
                    <select name="emission_source_id" id="emission_source_id" required
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-purple-500 focus:border-purple-500">
                        <option value="">Select Source</option>
                        @foreach($sources as $source)
                            <option value="{{ $source->id }}" {{ old('emission_source_id', $factor->emission_source_id) == $source->id ? 'selected' : '' }}>{{ $source->name }}</option>
                        @endforeach
                    </select>
                    @error('emission_source_id')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="factor_value" class="block text-sm font-medium text-gray-700 mb-1">Factor Value *</label>
                    <input type="number" step="0.000001" name="factor_value" id="factor_value" value="{{ old('factor_value', $factor->factor_value) }}" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-purple-500 focus:border-purple-500">
                    @error('factor_value')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="unit" class="block text-sm font-medium text-gray-700 mb-1">Unit *</label>
                    <input type="text" name="unit" id="unit" value="{{ old('unit', $factor->unit) }}" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-purple-500 focus:border-purple-500">
                    @error('unit')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="region" class="block text-sm font-medium text-gray-700 mb-1">Region</label>
                    <input type="text" name="region" id="region" value="{{ old('region', $factor->region) }}"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-purple-500 focus:border-purple-500">
                </div>

                <div>
                    <label for="co2_factor" class="block text-sm font-medium text-gray-700 mb-1">CO2 Factor</label>
                    <input type="number" step="0.000001" name="co2_factor" id="co2_factor" value="{{ old('co2_factor', $factor->co2_factor) }}"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-purple-500 focus:border-purple-500">
                </div>

                <div>
                    <label for="ch4_factor" class="block text-sm font-medium text-gray-700 mb-1">CH4 Factor</label>
                    <input type="number" step="0.000001" name="ch4_factor" id="ch4_factor" value="{{ old('ch4_factor', $factor->ch4_factor) }}"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-purple-500 focus:border-purple-500">
                </div>

                <div>
                    <label for="n2o_factor" class="block text-sm font-medium text-gray-700 mb-1">N2O Factor</label>
                    <input type="number" step="0.000001" name="n2o_factor" id="n2o_factor" value="{{ old('n2o_factor', $factor->n2o_factor) }}"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-purple-500 focus:border-purple-500">
                </div>

                <div>
                    <label for="total_co2e_factor" class="block text-sm font-medium text-gray-700 mb-1">Total CO2e Factor</label>
                    <input type="number" step="0.000001" name="total_co2e_factor" id="total_co2e_factor" value="{{ old('total_co2e_factor', $factor->total_co2e_factor) }}"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-purple-500 focus:border-purple-500">
                </div>

                <div>
                    <label for="gwp_version" class="block text-sm font-medium text-gray-700 mb-1">GWP Version</label>
                    <select name="gwp_version" id="gwp_version"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-purple-500 focus:border-purple-500">
                        <option value="AR6" {{ old('gwp_version', $factor->gwp_version ?? 'AR6') == 'AR6' ? 'selected' : '' }}>AR6</option>
                        <option value="AR5" {{ old('gwp_version', $factor->gwp_version) == 'AR5' ? 'selected' : '' }}>AR5</option>
                        <option value="AR4" {{ old('gwp_version', $factor->gwp_version) == 'AR4' ? 'selected' : '' }}>AR4</option>
                    </select>
                </div>

                <div>
                    <label for="fuel_category" class="block text-sm font-medium text-gray-700 mb-1">Fuel Category</label>
                    <input type="text" name="fuel_category" id="fuel_category" value="{{ old('fuel_category', $factor->fuel_category) }}"
                           placeholder="e.g. Gaseous fuels, Liquid fuels, Solid fuels, Biofuel, Biomass, Biogas"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-purple-500 focus:border-purple-500">
                    <p class="mt-1 text-xs text-gray-500">Used for Fuel sources (Stationary Combustion)</p>
                </div>

                <div>
                    <label for="fuel_type" class="block text-sm font-medium text-gray-700 mb-1">Fuel Type / Energy Type</label>
                    <input type="text" name="fuel_type" id="fuel_type" value="{{ old('fuel_type', $factor->fuel_type) }}"
                           placeholder="e.g. Natural gas, Diesel, Petrol, Heat, Steam, Cooling"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-purple-500 focus:border-purple-500">
                    <p class="mt-1 text-xs text-gray-500">Used for Fuel sources or Heat/Steam/Cooling to distinguish types</p>
                </div>
            </div>

            <div class="mt-6 flex items-center justify-end space-x-4">
                <a href="{{ route('admin.emissions.factors') }}" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                    Cancel
                </a>
                <button type="submit" class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700">
                    Update Factor
                </button>
            </div>
        </form>
    </div>
@endsection

