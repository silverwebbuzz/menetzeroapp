@props(['title', 'rows', 'columns', 'labels', 'plans'])

<div class="mb-10">
    <h2 class="text-xl font-bold text-gray-900 mb-3">{{ $title }}</h2>
    <div class="plan-comparison-table-wrap table-wrap">
        <table class="table plan-comparison-table">
            <thead>
                <tr>
                    <th>Feature</th>
                    @foreach($columns as $code)
                        <th class="text-center">{{ $labels[$code]['name'] ?? ($plans[$code]->plan_name ?? $code) }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach($rows as $row)
                    <tr>
                        <td class="cell-strong">{{ $row['label'] }}</td>
                        @foreach($columns as $code)
                            @php $cell = $row['cells'][$code] ?? false; @endphp
                            <td class="text-center">
                                @if($cell === true)
                                    <svg class="w-5 h-5 mx-auto text-green-500" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                                @elseif($cell === false)
                                    <span class="text-gray-300" aria-hidden="true">—</span>
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
