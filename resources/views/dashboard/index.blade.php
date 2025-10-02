@extends('layouts.app')

@section('title', 'Dashboard - CarbonTracker')
@section('page-title', 'Dashboard')

@section('content')
<style>
    :root { --brand:#004D40; --accent:#26A69A; --bg:#F9FAFB; }
    .card { border:1px solid #e5e7eb; border-radius:1rem; background:#fff; box-shadow:0 10px 20px -10px rgba(0,0,0,.08); transition: box-shadow .25s ease, transform .25s ease; }
    .card:hover { box-shadow:0 16px 28px -12px rgba(0,0,0,.12); transform: translateY(-1px); }
    .chip { display:inline-flex; align-items:center; gap:.5rem; padding:.25rem .5rem; border-radius:9999px; font-size:.75rem; border:1px solid #e5e7eb; }
</style>

<div class="space-y-8">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-semibold text-gray-900">Dashboard</h2>
            <p class="text-sm text-gray-500">Welcome back, {{ auth()->user()->name }}</p>
        </div>
        <a href="#" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg border text-[color:var(--brand)] border-[color:var(--accent)]/30 bg-[color:var(--accent)]/10 hover:bg-[color:var(--accent)]/20 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v8m4-4H8"/></svg>
            Export Report
        </a>
    </div>

    <!-- 1) Monthly CO2 Trend -->
    <div class="card p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-gray-900">Monthly CO₂ Trend</h3>
            <span class="chip"><span class="w-2 h-2 rounded-full" style="background:var(--accent)"></span> Updated</span>
        </div>
        <div class="h-80"><canvas id="trendChart" class="w-full h-full"></canvas></div>
        <div class="mt-5 overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="text-gray-500"><tr><th class="py-2 pr-6 text-left">Metric</th><th class="py-2 pr-6 text-left">YTD Total (tCO₂e)</th><th class="py-2 pr-6 text-left">Monthly Avg</th></tr></thead>
                <tbody class="text-gray-900">
                    <tr><td class="py-2 pr-6">Total</td><td class="py-2 pr-6" id="sumTotal">—</td><td class="py-2 pr-6" id="avgTotal">—</td></tr>
                    <tr><td class="py-2 pr-6">Scope 1</td><td class="py-2 pr-6" id="sumS1">—</td><td class="py-2 pr-6" id="avgS1">—</td></tr>
                    <tr><td class="py-2 pr-6">Scope 2</td><td class="py-2 pr-6" id="sumS2">—</td><td class="py-2 pr-6" id="avgS2">—</td></tr>
                    <tr><td class="py-2 pr-6">Scope 3</td><td class="py-2 pr-6" id="sumS3">—</td><td class="py-2 pr-6" id="avgS3">—</td></tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- 2) Energy & Water Usage -->
        <div class="card p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Energy & Water Usage</h3>
            <div class="h-80"><canvas id="energyChart" class="w-full h-full"></canvas></div>
            <div class="mt-5 overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="text-gray-500"><tr><th class="py-2 pr-6 text-left">Resource</th><th class="py-2 pr-6 text-left">YTD Total</th><th class="py-2 pr-6 text-left">Monthly Avg</th></tr></thead>
                    <tbody class="text-gray-900">
                        <tr><td class="py-2 pr-6">Electricity (kWh)</td><td class="py-2 pr-6" id="sumElec">—</td><td class="py-2 pr-6" id="avgElec">—</td></tr>
                        <tr><td class="py-2 pr-6">Fuel (L)</td><td class="py-2 pr-6" id="sumFuel">—</td><td class="py-2 pr-6" id="avgFuel">—</td></tr>
                        <tr><td class="py-2 pr-6">Water (m³)</td><td class="py-2 pr-6" id="sumWater">—</td><td class="py-2 pr-6" id="avgWater">—</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- 3) Waste Generation -->
        <div class="card p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Waste Generation</h3>
            <div class="h-80"><canvas id="wasteChart" class="w-full h-full"></canvas></div>
            <div class="mt-5 overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="text-gray-500"><tr><th class="py-2 pr-6 text-left">Type</th><th class="py-2 pr-6 text-left">YTD Total (kg)</th><th class="py-2 pr-6 text-left">Monthly Avg</th></tr></thead>
                    <tbody class="text-gray-900">
                        <tr><td class="py-2 pr-6">Hazardous</td><td class="py-2 pr-6" id="sumHaz">—</td><td class="py-2 pr-6" id="avgHaz">—</td></tr>
                        <tr><td class="py-2 pr-6">Non‑Hazardous</td><td class="py-2 pr-6" id="sumNon">—</td><td class="py-2 pr-6" id="avgNon">—</td></tr>
                        <tr><td class="py-2 pr-6">Recycled</td><td class="py-2 pr-6" id="sumRec">—</td><td class="py-2 pr-6" id="avgRec">—</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- 4) Scope Contribution Share -->
        <div class="card p-6 lg:col-span-2">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Scope Contribution Share</h3>
            <div class="h-80"><canvas id="shareChart" class="w-full h-full"></canvas></div>
            <div class="mt-5 overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="text-gray-500"><tr><th class="py-2 pr-6 text-left">Scope</th><th class="py-2 pr-6 text-left">Share</th></tr></thead>
                    <tbody class="text-gray-900">
                        <tr><td class="py-2 pr-6">Scope 1</td><td class="py-2 pr-6" id="shareS1">—</td></tr>
                        <tr><td class="py-2 pr-6">Scope 2</td><td class="py-2 pr-6" id="shareS2">—</td></tr>
                        <tr><td class="py-2 pr-6">Scope 3</td><td class="py-2 pr-6" id="shareS3">—</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
    const s1 = [110, 120, 115, 118, 122, 125, 128, 130, 129, 131, 133, 136];
    const s2 = [90, 88, 92, 94, 96, 95, 93, 92, 94, 95, 96, 97];
    const s3 = [220, 230, 240, 245, 250, 255, 260, 262, 265, 268, 270, 275];
    const total = months.map((_, i) => s1[i] + s2[i] + s3[i]);

    const brand = getComputedStyle(document.documentElement).getPropertyValue('--brand') || '#004D40';
    const accent = getComputedStyle(document.documentElement).getPropertyValue('--accent') || '#26A69A';

    // 1) Monthly CO2 Trend
    const tctx = document.getElementById('trendChart').getContext('2d');
    new Chart(tctx, { type: 'line', data: { labels: months, datasets: [
        { label:'Total', data: total, borderColor: accent.trim(), backgroundColor: 'rgba(38,166,154,.12)', fill:true, tension:.35 },
        { label:'Scope 1', data: s1, borderColor:'#ef4444', backgroundColor:'transparent', tension:.35 },
        { label:'Scope 2', data: s2, borderColor:'#f59e0b', backgroundColor:'transparent', tension:.35 },
        { label:'Scope 3', data: s3, borderColor:'#3b82f6', backgroundColor:'transparent', tension:.35 }
    ]}, options:{ responsive:true, maintainAspectRatio:false, interaction:{mode:'index', intersect:false}, plugins:{ legend:{ position:'bottom' } }, scales:{ y:{ beginAtZero:false, grid:{ color:'#eef2ff'}}, x:{ grid:{ display:false }}} });

    const sum = arr => arr.reduce((a,b)=>a+b,0);
    const avg = arr => (sum(arr)/arr.length).toFixed(1);
    document.getElementById('sumTotal').textContent = sum(total).toFixed(1);
    document.getElementById('avgTotal').textContent = avg(total);
    document.getElementById('sumS1').textContent = sum(s1).toFixed(1); document.getElementById('avgS1').textContent = avg(s1);
    document.getElementById('sumS2').textContent = sum(s2).toFixed(1); document.getElementById('avgS2').textContent = avg(s2);
    document.getElementById('sumS3').textContent = sum(s3).toFixed(1); document.getElementById('avgS3').textContent = avg(s3);

    // 2) Energy & Water Usage (combo)
    const electricity = [4200,4300,4400,4550,4620,4680,4700,4720,4750,4780,4800,4850];
    const fuel = [1200,1210,1225,1230,1240,1250,1260,1270,1280,1290,1300,1310];
    const water = [900,910,905,920,930,940,950,960,965,970,980,990];
    const ectx = document.getElementById('energyChart').getContext('2d');
    new Chart(ectx, { type:'bar', data:{ labels:months, datasets:[
        { type:'bar', label:'Electricity (kWh)', data:electricity, backgroundColor: brand.trim(), borderRadius:6 },
        { type:'bar', label:'Fuel (L)', data:fuel, backgroundColor:'#64748b', borderRadius:6 },
        { type:'line', label:'Water (m³)', data:water, borderColor: accent.trim(), backgroundColor:'transparent', tension:.35, yAxisID:'y1' }
    ]}, options:{ responsive:true, maintainAspectRatio:false, interaction:{mode:'index', intersect:false}, plugins:{ legend:{ position:'bottom' }}, scales:{ y:{ beginAtZero:true, grid:{ color:'#eef2ff'}}, y1:{ beginAtZero:true, position:'right', grid:{ drawOnChartArea:false }} } });
    document.getElementById('sumElec').textContent = sum(electricity).toFixed(0); document.getElementById('avgElec').textContent = avg(electricity);
    document.getElementById('sumFuel').textContent = sum(fuel).toFixed(0); document.getElementById('avgFuel').textContent = avg(fuel);
    document.getElementById('sumWater').textContent = sum(water).toFixed(0); document.getElementById('avgWater').textContent = avg(water);

    // 3) Waste Generation
    const wh = [120,130,110,150,140,160,170,165,155,150,148,160];
    const wn = [800,820,810,830,840,845,855,860,870,880,890,900];
    const wr = [300,320,330,340,350,360,370,380,390,400,410,420];
    const wctx = document.getElementById('wasteChart').getContext('2d');
    new Chart(wctx, { type:'bar', data:{ labels:months, datasets:[
        { label:'Hazardous', data:wh, backgroundColor:'#ef4444', borderRadius:6 },
        { label:'Non‑Hazardous', data:wn, backgroundColor:'#f59e0b', borderRadius:6 },
        { label:'Recycled', data:wr, backgroundColor:'#22c55e', borderRadius:6 }
    ]}, options:{ responsive:true, maintainAspectRatio:false, plugins:{ legend:{ position:'bottom' }}, scales:{ y:{ beginAtZero:true, grid:{ color:'#eef2ff'} } } });
    document.getElementById('sumHaz').textContent = sum(wh).toFixed(0); document.getElementById('avgHaz').textContent = avg(wh);
    document.getElementById('sumNon').textContent = sum(wn).toFixed(0); document.getElementById('avgNon').textContent = avg(wn);
    document.getElementById('sumRec').textContent = sum(wr).toFixed(0); document.getElementById('avgRec').textContent = avg(wr);

    // 4) Scope Contribution Share
    const scTotals = [sum(s1), sum(s2), sum(s3)];
    const scShare = scTotals.map(v => (v / sum(scTotals) * 100));
    const sctx = document.getElementById('shareChart').getContext('2d');
    new Chart(sctx, { type:'doughnut', data:{ labels:['Scope 1','Scope 2','Scope 3'], datasets:[{ data: scTotals, backgroundColor:['#ef4444','#f59e0b','#3b82f6'], borderWidth:0 }] }, options:{ responsive:true, maintainAspectRatio:false, plugins:{ legend:{ position:'bottom' } } });
    document.getElementById('shareS1').textContent = scShare[0].toFixed(1) + '%';
    document.getElementById('shareS2').textContent = scShare[1].toFixed(1) + '%';
    document.getElementById('shareS3').textContent = scShare[2].toFixed(1) + '%';
</script>
@endpush
