@props(['title', 'rows', 'columns', 'labels', 'plans'])

<div class="mb-10">
    <h2 class="text-xl font-bold text-gray-900 mb-3">{{ $title }}</h2>
    <div class="overflow-x-auto bg-white rounded-xl border border-gray-200">
        <table class="min-w-full text-sm">
            <thead>
                <tr class="border-b border-gray-200 bg-gray-50">
                    <th class="text-left font-semibold text-gray-700 px-4 py-3 w-1/4">Feature</th>
                    @foreach($columns as $code)
                        <th class="text-center font-semibold text-gray-700 px-3 py-3">
                            {{ $labels[$code]['name'] ?? ($plans[$code]->plan_name ?? $code) }}
                        </th>
                    @endforeach
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($rows as $row)
                    <tr>
                        <td class="px-4 py-3 text-gray-800 font-medium">{{ $row['label'] }}</td>
                        @foreach($columns as $code)
                            @php $cell = $row['cells'][$code] ?? false; @endphp
                            <td class="px-3 py-3 text-center text-gray-700">
                                @if($cell === true)
                                    <svg class="w-5 h-5 mx-auto text-green-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                                @elseif($cell === false)
                                    <span class="text-gray-300">—</span>
                                @else
                                    {{ $cell }}
                                @endif
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
