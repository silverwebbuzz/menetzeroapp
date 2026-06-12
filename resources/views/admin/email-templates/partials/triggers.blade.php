@php
    use App\Support\EmailTriggerRegistry;

    $triggers = $triggers ?? [];
@endphp

@if($triggers === [])
    <span class="text-xs text-gray-400">—</span>
@else
    <ul class="space-y-2 mt-1">
        @foreach($triggers as $trigger)
            @php
                $url = EmailTriggerRegistry::url($trigger);
                $status = $trigger['status'] ?? 'live';
                $statusClass = match ($status) {
                    'planned' => 'bg-amber-100 text-amber-800',
                    'internal' => 'bg-slate-100 text-slate-700',
                    default => 'bg-green-100 text-green-800',
                };
                $statusLabel = match ($status) {
                    'planned' => 'Not wired',
                    'internal' => 'Internal',
                    default => 'Live',
                };
            @endphp
            <li class="text-xs">
                <div class="flex flex-wrap items-center gap-1.5">
                    <span class="px-1.5 py-0.5 rounded text-[10px] font-medium uppercase tracking-wide {{ $statusClass }}">{{ $statusLabel }}</span>
                    @if($url)
                        <a href="{{ $url }}" target="_blank" rel="noopener" class="text-indigo-700 hover:underline font-medium">{{ $trigger['label'] }}</a>
                    @else
                        <span class="text-gray-800 font-medium">{{ $trigger['label'] }}</span>
                    @endif
                </div>
                @if(!empty($trigger['file']))
                    <div class="font-mono text-[11px] text-gray-500 mt-0.5">{{ $trigger['file'] }}</div>
                @endif
                @if(!empty($trigger['note']))
                    <div class="text-gray-500 mt-0.5">{{ $trigger['note'] }}</div>
                @endif
            </li>
        @endforeach
    </ul>
@endif
