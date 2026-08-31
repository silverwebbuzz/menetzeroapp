{{--
  Shared package comparison matrix for company + consultant request forms.
  Expects: $matrix (CompanyPackageOptions::comparisonMatrix()), $selectedPackage (string)
  Optional: $inputName (default package_code)
--}}
@php
    $inputName = $inputName ?? 'package_code';
    $packages = $packages ?? \App\Data\CompanyPackageOptions::packages();
    $selectionMode = $selectionMode ?? 'radio'; // radio | none
    $selectedPackage = $selectionMode === 'none'
        ? null
        : ($selectedPackage ?? old($inputName, \App\Data\CompanyPackageOptions::CODES[0]));
    $columns = $matrix['columns'] ?? \App\Data\CompanyPackageOptions::CODES;
@endphp

<div class="bg-white rounded-xl border border-gray-200 overflow-hidden" id="package-comparison-matrix">
    <div class="px-4 py-3 border-b border-gray-100">
        <h2 class="text-sm font-semibold text-gray-900">Compare packages</h2>
        <p class="text-xs text-gray-500 mt-0.5">
            @if($selectionMode === 'none')
                Capabilities only — enter quantities per package below. Pricing is confirmed offline.
            @else
                Select a column — capabilities only; pricing is confirmed offline.
            @endif
        </p>
    </div>
    <div class="overflow-x-auto">
        <table class="min-w-[720px] w-full text-sm">
            <thead>
                <tr class="bg-gray-50 border-b border-gray-200">
                    <th scope="col" class="sticky left-0 z-10 bg-gray-50 px-3 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide min-w-[12rem]">
                        Feature
                    </th>
                    @foreach($columns as $code)
                        @php $pkg = $packages[$code] ?? ['name' => $code, 'summary' => '']; @endphp
                        <th scope="col" class="px-2 py-3 text-center min-w-[7.5rem] align-bottom">
                            @if($selectionMode === 'radio')
                                <label class="cursor-pointer inline-flex flex-col items-center gap-1.5 group">
                                    <input
                                        type="radio"
                                        name="{{ $inputName }}"
                                        value="{{ $code }}"
                                        class="rounded-full border-gray-300 text-teal-600 focus:ring-teal-500"
                                        @checked($selectedPackage === $code)
                                        required
                                        data-package-code="{{ $code }}"
                                    >
                                    <span class="text-sm font-bold text-gray-900 group-hover:text-teal-700 leading-tight">{{ $pkg['name'] }}</span>
                                    <span class="text-[10px] font-normal text-gray-500 leading-snug max-w-[7rem] hidden sm:block">{{ $pkg['summary'] }}</span>
                                </label>
                            @else
                                <div class="inline-flex flex-col items-center gap-1">
                                    <span class="text-sm font-bold text-gray-900 leading-tight">{{ $pkg['name'] }}</span>
                                    <span class="text-[10px] font-normal text-gray-500 leading-snug max-w-[7rem] hidden sm:block">{{ $pkg['summary'] }}</span>
                                </div>
                            @endif
                        </th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach($matrix['sections'] as $section)
                    <tr class="bg-slate-50/80">
                        <td colspan="{{ count($columns) + 1 }}" class="px-3 py-2 text-[11px] font-semibold uppercase tracking-wide text-slate-600">
                            {{ $section['title'] }}
                        </td>
                    </tr>
                    @foreach($section['rows'] as $row)
                        <tr class="border-t border-gray-100 hover:bg-teal-50/30">
                            <td class="sticky left-0 z-10 bg-white px-3 py-2 text-gray-800 font-medium text-xs sm:text-sm">
                                {{ $row['label'] }}
                            </td>
                            @foreach($columns as $code)
                                @php $cell = $row['cells'][$code] ?? false; @endphp
                                <td class="px-2 py-2 text-center text-xs sm:text-sm package-col" data-col="{{ $code }}">
                                    @if($cell === true)
                                        <span class="inline-flex text-teal-600 font-semibold" title="Included">✓</span>
                                    @elseif($cell === false)
                                        <span class="text-gray-300" title="Not included">—</span>
                                    @else
                                        <span class="text-gray-700">{{ $cell }}</span>
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                @endforeach
            </tbody>
        </table>
    </div>
    <p class="px-4 py-2 text-[11px] text-gray-400 border-t border-gray-100">
        Outputs are draft working papers for review — not regulatory certification or third-party assurance.
    </p>
</div>

<style>
    #package-comparison-matrix input[type="radio"]:checked + span {
        color: rgb(15 118 110); /* teal-700 */
    }
    {{-- Generated from the same CODES the columns come from. Written out by
         hand these drifted the moment the catalogue changed: the selectors
         still named retired packages, so no column highlighted. --}}
@foreach (($columns ?? []) as $columnCode)
    #package-comparison-matrix:has(input[value="{{ $columnCode }}"]:checked) td.package-col[data-col="{{ $columnCode }}"]@if (! $loop->last),@endif
@endforeach
    {
        background-color: rgba(20, 184, 166, 0.08);
    }
</style>
