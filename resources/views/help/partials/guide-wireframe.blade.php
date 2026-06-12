@php
    $variant = $variant ?? 'default';
    $theme = $theme ?? 'company';
    $alt = $alt ?? 'Guide illustration';
    $brand = $theme === 'consultant' ? '#2563eb' : '#16a34a';
    $brandSoft = $theme === 'consultant' ? '#eff6ff' : '#dcfce7';
    $sidebarFill = $theme === 'consultant' ? '#0f2b6b' : '#ffffff';
    $sidebarText = $theme === 'consultant' ? '#ffffff' : '#64748b';
@endphp

<svg
    class="portal-guide-figure__svg"
    viewBox="0 0 960 540"
    role="img"
    aria-label="{{ $alt }}"
    xmlns="http://www.w3.org/2000/svg"
>
    <rect width="960" height="540" fill="#f8fafc"/>
    {{-- Sidebar --}}
    <rect x="0" y="0" width="200" height="540" fill="{{ $sidebarFill }}" stroke="#e2e8f0" stroke-width="1"/>
    <rect x="20" y="24" width="100" height="14" rx="4" fill="{{ $theme === 'consultant' ? 'rgba(255,255,255,0.35)' : '#e2e8f0' }}"/>
    @for($i = 0; $i < 5; $i++)
        <rect x="16" y="{{ 72 + ($i * 36) }}" width="168" height="24" rx="6" fill="{{ $i === 0 ? $brandSoft : ($theme === 'consultant' ? 'rgba(255,255,255,0.08)' : '#f1f5f9') }}"/>
        @if($i === 0)
            <rect x="16" y="{{ 72 + ($i * 36) }}" width="3" height="24" rx="1" fill="{{ $brand }}"/>
        @endif
    @endfor
    {{-- Header --}}
    <rect x="200" y="0" width="760" height="56" fill="#ffffff" stroke="#e2e8f0" stroke-width="1"/>
    <rect x="224" y="20" width="220" height="16" rx="4" fill="#cbd5e1"/>
    {{-- Main canvas --}}
    <rect x="200" y="56" width="760" height="484" fill="#f8fafc"/>

    @switch($variant)
        @case('dashboard')
            <rect x="224" y="80" width="180" height="14" rx="4" fill="#0f172a" opacity="0.85"/>
            <rect x="224" y="108" width="280" height="10" rx="3" fill="#94a3b8"/>
            @foreach([[224, 140], [392, 140], [560, 140], [728, 140]] as [$x, $y])
                <rect x="{{ $x }}" y="{{ $y }}" width="152" height="88" rx="10" fill="#ffffff" stroke="#e2e8f0"/>
                <rect x="{{ $x + 16 }}" y="{{ $y + 16 }}" width="72" height="8" rx="3" fill="#94a3b8"/>
                <rect x="{{ $x + 16 }}" y="{{ $y + 36 }}" width="96" height="18" rx="4" fill="{{ $brand }}" opacity="0.85"/>
            @endforeach
            <rect x="224" y="248" width="656" height="200" rx="12" fill="#ffffff" stroke="#e2e8f0"/>
            <rect x="248" y="272" width="120" height="10" rx="3" fill="#64748b"/>
            @for($b = 0; $b < 6; $b++)
                <rect x="{{ 268 + ($b * 88) }}" y="320" width="48" height="{{ 40 + ($b % 3) * 24 }}" rx="4" fill="{{ $brandSoft }}"/>
                <rect x="{{ 268 + ($b * 88) }}" y="{{ 320 + 40 + ($b % 3) * 24 + 8 }}" width="48" height="8" rx="2" fill="{{ $brand }}" opacity="0.5"/>
            @endfor
            <circle cx="860" cy="120" r="14" fill="{{ $brand }}"/>
            <text x="860" y="125" text-anchor="middle" fill="#fff" font-size="12" font-weight="700" font-family="Inter, system-ui, sans-serif">1</text>
            @break

        @case('locations')
            <rect x="224" y="80" width="200" height="16" rx="4" fill="#0f172a" opacity="0.85"/>
            <rect x="224" y="112" width="656" height="72" rx="10" fill="#ffffff" stroke="#e2e8f0"/>
            <rect x="244" y="132" width="100" height="10" rx="3" fill="#64748b"/>
            <rect x="360" y="128" width="200" height="28" rx="6" fill="#f8fafc" stroke="#e2e8f0"/>
            <rect x="580" y="128" width="88" height="28" rx="6" fill="{{ $brand }}" opacity="0.9"/>
            @foreach([[224, 204], [224, 292], [224, 380]] as [$x, $y])
                <rect x="{{ $x }}" y="{{ $y }}" width="656" height="72" rx="10" fill="#ffffff" stroke="#e2e8f0"/>
                <rect x="{{ $x + 20 }}" y="{{ $y + 20 }}" width="140" height="12" rx="3" fill="#0f172a" opacity="0.8"/>
                <rect x="{{ $x + 20 }}" y="{{ $y + 40 }}" width="220" height="8" rx="3" fill="#94a3b8"/>
                <rect x="{{ $x + 520 }}" y="{{ $y + 24 }}" width="96" height="24" rx="12" fill="{{ $brandSoft }}"/>
            @endforeach
            <circle cx="860" cy="148" r="14" fill="{{ $brand }}"/>
            <text x="860" y="153" text-anchor="middle" fill="#fff" font-size="12" font-weight="700" font-family="Inter, system-ui, sans-serif">1</text>
            @break

        @case('quick-input')
            <rect x="224" y="80" width="260" height="16" rx="4" fill="#0f172a" opacity="0.85"/>
            <rect x="224" y="112" width="656" height="120" rx="10" fill="#ffffff" stroke="#e2e8f0"/>
            <rect x="244" y="132" width="48" height="8" rx="2" fill="#64748b"/>
            <rect x="244" y="148" width="160" height="28" rx="6" fill="#f8fafc" stroke="#e2e8f0"/>
            <rect x="432" y="132" width="56" height="8" rx="2" fill="#64748b"/>
            <rect x="432" y="148" width="200" height="28" rx="6" fill="#f8fafc" stroke="#e2e8f0"/>
            <rect x="224" y="252" width="656" height="180" rx="10" fill="#ffffff" stroke="#e2e8f0"/>
            <rect x="244" y="272" width="120" height="10" rx="3" fill="#64748b"/>
            @for($r = 0; $r < 4; $r++)
                <rect x="244" y="{{ 296 + ($r * 28) }}" width="616" height="20" rx="4" fill="{{ $r === 0 ? $brandSoft : '#f8fafc' }}" stroke="#e2e8f0"/>
            @endfor
            <circle cx="420" cy="162" r="14" fill="{{ $brand }}"/>
            <text x="420" y="167" text-anchor="middle" fill="#fff" font-size="12" font-weight="700" font-family="Inter, system-ui, sans-serif">1</text>
            <circle cx="860" cy="284" r="14" fill="{{ $brand }}"/>
            <text x="860" y="289" text-anchor="middle" fill="#fff" font-size="12" font-weight="700" font-family="Inter, system-ui, sans-serif">2</text>
            @break

        @case('reports')
            <rect x="224" y="80" width="220" height="16" rx="4" fill="#0f172a" opacity="0.85"/>
            <rect x="760" y="76" width="120" height="32" rx="8" fill="{{ $brand }}" opacity="0.9"/>
            <rect x="224" y="120" width="656" height="300" rx="10" fill="#ffffff" stroke="#e2e8f0"/>
            <rect x="244" y="140" width="80" height="10" rx="3" fill="#64748b"/>
            @for($c = 0; $c < 4; $c++)
                <rect x="{{ 244 + ($c * 150) }}" y="164" width="120" height="10" rx="3" fill="#94a3b8"/>
            @endfor
            @for($r = 0; $r < 7; $r++)
                <rect x="244" y="{{ 196 + ($r * 28) }}" width="616" height="20" rx="4" fill="{{ $r % 2 === 0 ? '#f8fafc' : '#ffffff' }}" stroke="#e2e8f0"/>
            @endfor
            <circle cx="820" cy="92" r="14" fill="{{ $brand }}"/>
            <text x="820" y="97" text-anchor="middle" fill="#fff" font-size="12" font-weight="700" font-family="Inter, system-ui, sans-serif">1</text>
            @break

        @case('disclosures')
            <rect x="224" y="80" width="240" height="16" rx="4" fill="#0f172a" opacity="0.85"/>
            @foreach([[224, 120, 'IFRS S2'], [432, 120, 'IFRS S1'], [640, 120, 'GRI'], [224, 280, 'ESG']] as [$x, $y, $label])
                <rect x="{{ $x }}" y="{{ $y }}" width="184" height="{{ $y === 280 ? 120 : 140 }}" rx="10" fill="#ffffff" stroke="#e2e8f0"/>
                <rect x="{{ $x + 16 }}" y="{{ $y + 16 }}" width="100" height="12" rx="3" fill="#0f172a" opacity="0.75"/>
                <rect x="{{ $x + 16 }}" y="{{ $y + 40 }}" width="140" height="8" rx="3" fill="#94a3b8"/>
                <rect x="{{ $x + 16 }}" y="{{ $y + 64 }}" width="152" height="8" rx="3" fill="#e2e8f0"/>
                <rect x="{{ $x + 16 }}" y="{{ $y + 88 }}" width="120" height="8" rx="4" fill="{{ $brandSoft }}"/>
            @endforeach
            <circle cx="392" cy="136" r="14" fill="{{ $brand }}"/>
            <text x="392" y="141" text-anchor="middle" fill="#fff" font-size="12" font-weight="700" font-family="Inter, system-ui, sans-serif">1</text>
            @break

        @case('team')
            <rect x="224" y="80" width="180" height="16" rx="4" fill="#0f172a" opacity="0.85"/>
            <rect x="784" y="76" width="96" height="32" rx="8" fill="{{ $brand }}" opacity="0.9"/>
            <rect x="224" y="120" width="656" height="300" rx="10" fill="#ffffff" stroke="#e2e8f0"/>
            @for($r = 0; $r < 6; $r++)
                <circle cx="264" cy="{{ 164 + ($r * 44) }}" r="14" fill="{{ $brandSoft }}"/>
                <rect x="288" y="{{ 154 + ($r * 44) }}" width="140" height="10" rx="3" fill="#0f172a" opacity="0.7"/>
                <rect x="288" y="{{ 170 + ($r * 44) }}" width="100" height="8" rx="3" fill="#94a3b8"/>
                <rect x="720" y="{{ 158 + ($r * 44) }}" width="80" height="20" rx="10" fill="#f1f5f9"/>
            @endfor
            <circle cx="832" cy="92" r="14" fill="{{ $brand }}"/>
            <text x="832" y="97" text-anchor="middle" fill="#fff" font-size="12" font-weight="700" font-family="Inter, system-ui, sans-serif">1</text>
            @break

        @case('billing')
            <rect x="224" y="80" width="200" height="16" rx="4" fill="#0f172a" opacity="0.85"/>
            @foreach([[224, 120], [408, 120], [592, 120], [776, 120]] as $i => [$x, $y])
                <rect x="{{ $x }}" y="{{ $y }}" width="152" height="200" rx="10" fill="#ffffff" stroke="{{ $i === 1 ? $brand : '#e2e8f0' }}" stroke-width="{{ $i === 1 ? 2 : 1 }}"/>
                <rect x="{{ $x + 16 }}" y="{{ $y + 16 }}" width="72" height="12" rx="3" fill="#0f172a" opacity="0.75"/>
                <rect x="{{ $x + 16 }}" y="{{ $y + 40 }}" width="96" height="20" rx="4" fill="{{ $brand }}" opacity="{{ $i === 1 ? 0.9 : 0.35 }}"/>
                @for($l = 0; $l < 4; $l++)
                    <rect x="{{ $x + 16 }}" y="{{ $y + 76 + ($l * 22) }}" width="120" height="8" rx="3" fill="#e2e8f0"/>
                @endfor
            @endforeach
            <circle cx="480" cy="136" r="14" fill="{{ $brand }}"/>
            <text x="480" y="141" text-anchor="middle" fill="#fff" font-size="12" font-weight="700" font-family="Inter, system-ui, sans-serif">1</text>
            @break

        @case('consultants')
            @foreach([[224, 120], [432, 120], [640, 120], [224, 300], [432, 300]] as [$x, $y])
                <rect x="{{ $x }}" y="{{ $y }}" width="184" height="160" rx="10" fill="#ffffff" stroke="#e2e8f0"/>
                <circle cx="{{ $x + 40 }}" cy="{{ $y + 40 }}" r="24" fill="{{ $brandSoft }}"/>
                <rect x="{{ $x + 76 }}" y="{{ $y + 28 }}" width="88" height="10" rx="3" fill="#0f172a" opacity="0.75"/>
                <rect x="{{ $x + 76 }}" y="{{ $y + 46 }}" width="64" height="8" rx="3" fill="#94a3b8"/>
                <rect x="{{ $x + 16 }}" y="{{ $y + 88 }}" width="152" height="8" rx="3" fill="#e2e8f0"/>
                <rect x="{{ $x + 16 }}" y="{{ $y + 112 }}" width="96" height="24" rx="6" fill="{{ $brand }}" opacity="0.85"/>
            @endforeach
            <circle cx="392" cy="136" r="14" fill="{{ $brand }}"/>
            <text x="392" y="141" text-anchor="middle" fill="#fff" font-size="12" font-weight="700" font-family="Inter, system-ui, sans-serif">1</text>
            @break

        @case('clients')
            <rect x="224" y="80" width="200" height="16" rx="4" fill="#0f172a" opacity="0.85"/>
            <rect x="784" y="76" width="96" height="32" rx="8" fill="{{ $brand }}" opacity="0.9"/>
            <rect x="224" y="120" width="656" height="300" rx="10" fill="#ffffff" stroke="#e2e8f0"/>
            @for($r = 0; $r < 5; $r++)
                <rect x="244" y="{{ 144 + ($r * 52) }}" width="616" height="40" rx="6" fill="{{ $r === 0 ? $brandSoft : '#f8fafc' }}" stroke="#e2e8f0"/>
                <rect x="260" y="{{ 158 + ($r * 52) }}" width="160" height="10" rx="3" fill="#0f172a" opacity="0.75"/>
                <rect x="520" y="{{ 158 + ($r * 52) }}" width="48" height="10" rx="3" fill="#94a3b8"/>
                <rect x="760" y="{{ 154 + ($r * 52) }}" width="72" height="20" rx="6" fill="{{ $brand }}" opacity="0.75"/>
            @endfor
            <circle cx="832" cy="92" r="14" fill="{{ $brand }}"/>
            <text x="832" y="97" text-anchor="middle" fill="#fff" font-size="12" font-weight="700" font-family="Inter, system-ui, sans-serif">1</text>
            @break

        @case('workspaces')
            <rect x="224" y="12" width="360" height="32" rx="6" fill="{{ $brandSoft }}" stroke="{{ $brand }}" stroke-width="1"/>
            <rect x="240" y="22" width="280" height="10" rx="3" fill="{{ $brand }}" opacity="0.6"/>
            <rect x="224" y="80" width="220" height="16" rx="4" fill="#0f172a" opacity="0.85"/>
            @foreach([[224, 120], [224, 220], [224, 320]] as [$x, $y])
                <rect x="{{ $x }}" y="{{ $y }}" width="656" height="80" rx="10" fill="#ffffff" stroke="#e2e8f0"/>
                <rect x="{{ $x + 20 }}" y="{{ $y + 20 }}" width="180" height="12" rx="3" fill="#0f172a" opacity="0.8"/>
                <rect x="{{ $x + 20 }}" y="{{ $y + 40 }}" width="80" height="8" rx="3" fill="#94a3b8"/>
                <rect x="{{ $x + 520 }}" y="{{ $y + 24 }}" width="112" height="32" rx="8" fill="{{ $brand }}" opacity="0.9"/>
            @endforeach
            <circle cx="560" cy="28" r="14" fill="{{ $brand }}"/>
            <text x="560" y="33" text-anchor="middle" fill="#fff" font-size="12" font-weight="700" font-family="Inter, system-ui, sans-serif">1</text>
            <circle cx="860" cy="156" r="14" fill="{{ $brand }}"/>
            <text x="860" y="161" text-anchor="middle" fill="#fff" font-size="12" font-weight="700" font-family="Inter, system-ui, sans-serif">2</text>
            @break

        @case('packs')
            <rect x="224" y="80" width="180" height="16" rx="4" fill="#0f172a" opacity="0.85"/>
            @foreach([[224, 120], [408, 120], [592, 120], [776, 120]] as $i => [$x, $y])
                <rect x="{{ $x }}" y="{{ $y }}" width="152" height="220" rx="10" fill="#ffffff" stroke="{{ $i === 2 ? $brand : '#e2e8f0' }}" stroke-width="{{ $i === 2 ? 2 : 1 }}"/>
                <rect x="{{ $x + 16 }}" y="{{ $y + 16 }}" width="80" height="12" rx="3" fill="#0f172a" opacity="0.75"/>
                <rect x="{{ $x + 16 }}" y="{{ $y + 36 }}" width="64" height="8" rx="3" fill="#94a3b8"/>
                <rect x="{{ $x + 16 }}" y="{{ $y + 56 }}" width="88" height="18" rx="4" fill="{{ $brand }}" opacity="0.85"/>
                @for($l = 0; $l < 5; $l++)
                    <rect x="{{ $x + 16 }}" y="{{ $y + 88 + ($l * 20) }}" width="120" height="8" rx="3" fill="#e2e8f0"/>
                @endfor
                <rect x="{{ $x + 16 }}" y="{{ $y + 184 }}" width="120" height="24" rx="6" fill="{{ $brandSoft }}"/>
            @endforeach
            <circle cx="656" cy="136" r="14" fill="{{ $brand }}"/>
            <text x="656" y="141" text-anchor="middle" fill="#fff" font-size="12" font-weight="700" font-family="Inter, system-ui, sans-serif">1</text>
            @break

        @case('directory')
            <rect x="224" y="80" width="240" height="16" rx="4" fill="#0f172a" opacity="0.85"/>
            <rect x="224" y="120" width="400" height="300" rx="10" fill="#ffffff" stroke="#e2e8f0"/>
            <circle cx="284" cy="168" r="36" fill="{{ $brandSoft }}"/>
            @for($f = 0; $f < 6; $f++)
                <rect x="340" y="{{ 140 + ($f * 28) }}" width="{{ $f === 0 ? 180 : 240 }}" height="10" rx="3" fill="{{ $f === 0 ? '#0f172a' : '#e2e8f0' }}" opacity="{{ $f === 0 ? 0.8 : 1 }}"/>
            @endfor
            <rect x="648" y="120" width="232" height="300" rx="10" fill="#ffffff" stroke="#e2e8f0"/>
            <rect x="668" y="140" width="120" height="10" rx="3" fill="#64748b"/>
            @for($l = 0; $l < 4; $l++)
                <rect x="668" y="{{ 168 + ($l * 56) }}" width="192" height="40" rx="6" fill="#f8fafc" stroke="#e2e8f0"/>
            @endfor
            <circle cx="460" cy="152" r="14" fill="{{ $brand }}"/>
            <text x="460" y="157" text-anchor="middle" fill="#fff" font-size="12" font-weight="700" font-family="Inter, system-ui, sans-serif">1</text>
            @break

        @case('client-tools')
            <rect x="224" y="80" width="280" height="16" rx="4" fill="#0f172a" opacity="0.85"/>
            <rect x="224" y="120" width="200" height="300" rx="10" fill="#ffffff" stroke="#e2e8f0"/>
            @for($n = 0; $n < 6; $n++)
                <rect x="244" y="{{ 140 + ($n * 36) }}" width="160" height="24" rx="6" fill="{{ $n === 2 ? $brandSoft : '#f8fafc' }}"/>
            @endfor
            <rect x="440" y="120" width="440" height="300" rx="10" fill="#ffffff" stroke="#e2e8f0"/>
            <rect x="460" y="140" width="160" height="10" rx="3" fill="#64748b"/>
            <rect x="460" y="164" width="400" height="28" rx="6" fill="#f8fafc" stroke="#e2e8f0"/>
            <rect x="460" y="208" width="400" height="28" rx="6" fill="#f8fafc" stroke="#e2e8f0"/>
            <rect x="460" y="260" width="400" height="120" rx="8" fill="#f8fafc" stroke="#e2e8f0"/>
            <circle cx="540" cy="178" r="14" fill="{{ $brand }}"/>
            <text x="540" y="183" text-anchor="middle" fill="#fff" font-size="12" font-weight="700" font-family="Inter, system-ui, sans-serif">1</text>
            @break

        @default
            <rect x="224" y="120" width="656" height="300" rx="10" fill="#ffffff" stroke="#e2e8f0"/>
            <rect x="244" y="140" width="200" height="12" rx="3" fill="#64748b"/>
            <rect x="244" y="168" width="400" height="10" rx="3" fill="#e2e8f0"/>
            <rect x="244" y="188" width="360" height="10" rx="3" fill="#e2e8f0"/>
    @endswitch
</svg>
