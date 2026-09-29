@extends('layouts.app')
@section('title', 'Analitik')

@section('content')
<div class="space-y-6">

    <div>
        <h1 class="text-xl font-bold text-gray-900">Analitik & Kinerja AI</h1>
        <p class="text-sm text-gray-500 mt-1">Performa CivicAI — IBM Bob + Langflow + Laravel</p>
    </div>

    <!-- AI Performance Cards -->
    <div id="ai-stats" class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white rounded-xl p-4 border border-gray-200 shadow-sm text-center">
            <p class="text-xs text-gray-500 uppercase tracking-wide">Total Dianalisis AI</p>
            <p class="text-3xl font-bold text-blue-700 mt-1" id="stat-analyzed">—</p>
        </div>
        <div class="bg-white rounded-xl p-4 border border-gray-200 shadow-sm text-center">
            <p class="text-xs text-gray-500 uppercase tracking-wide">Confidence ≥ 80%</p>
            <p class="text-3xl font-bold text-green-600 mt-1" id="stat-highconf">—</p>
        </div>
        <div class="bg-white rounded-xl p-4 border border-gray-200 shadow-sm text-center">
            <p class="text-xs text-gray-500 uppercase tracking-wide">Confidence &lt; 70%</p>
            <p class="text-3xl font-bold text-red-500 mt-1" id="stat-lowconf">—</p>
        </div>
        <div class="bg-white rounded-xl p-4 border border-gray-200 shadow-sm text-center">
            <p class="text-xs text-gray-500 uppercase tracking-wide">Koreksi Manusia</p>
            <p class="text-3xl font-bold text-amber-500 mt-1" id="stat-corrections">—</p>
        </div>
    </div>

    <!-- Charts row -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

        <!-- By Category -->
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
            <h2 class="font-semibold text-gray-900 mb-4">Pengaduan per Kategori</h2>
            <div id="category-chart" class="space-y-2"></div>
        </div>

        <!-- By Agency -->
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
            <h2 class="font-semibold text-gray-900 mb-4">Pengaduan per Instansi OPD</h2>
            <div id="agency-chart" class="space-y-2"></div>
        </div>
    </div>

    <!-- Channels -->
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
        <h2 class="font-semibold text-gray-900 mb-4">Distribusi Kanal Masuk</h2>
        <div id="channel-chart" class="flex flex-wrap gap-4"></div>
    </div>

    <!-- Responsible AI Section -->
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
        <h2 class="font-semibold text-gray-900 mb-4">Responsible AI Metrics</h2>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-xs text-gray-500 uppercase tracking-wide">
                    <tr>
                        <th class="px-4 py-3 text-left">Metrik</th>
                        <th class="px-4 py-3 text-left">Nilai</th>
                        <th class="px-4 py-3 text-left">Keterangan</th>
                    </tr>
                </thead>
                <tbody id="ai-metrics-table" class="divide-y divide-gray-100">
                    <tr><td colspan="3" class="px-4 py-4 text-center text-gray-400">Memuat...</td></tr>
                </tbody>
            </table>
        </div>
    </div>

</div>

<script>
async function loadAnalytics() {
    const [aiPerf, byCat, byAgency] = await Promise.all([
        fetch('/api/analytics/ai-performance').then(r => r.json()),
        fetch('/api/analytics/by-category').then(r => r.json()),
        fetch('/api/analytics/by-agency').then(r => r.json()),
    ]);

    // AI stats
    document.getElementById('stat-analyzed').textContent    = aiPerf.ai_analyzed ?? '—';
    document.getElementById('stat-highconf').textContent    = aiPerf.high_confidence ?? '—';
    document.getElementById('stat-lowconf').textContent     = aiPerf.low_confidence ?? '—';
    document.getElementById('stat-corrections').textContent = aiPerf.human_corrections ?? '—';

    // Category chart
    const catEl = document.getElementById('category-chart');
    const maxCat = Math.max(...(byCat.map(d => d.count) || [1]));
    catEl.innerHTML = byCat.map(d => `
        <div class="flex items-center gap-3">
            <div class="w-28 text-xs text-gray-600 capitalize truncate">${d.category || 'Umum'}</div>
            <div class="flex-1 bg-gray-100 rounded-full h-3">
                <div class="bg-blue-500 h-3 rounded-full" style="width:${Math.round(d.count/maxCat*100)}%"></div>
            </div>
            <div class="w-6 text-xs text-gray-700 text-right">${d.count}</div>
        </div>
    `).join('') || '<p class="text-gray-400 text-sm">Belum ada data</p>';

    // Agency chart
    const agencyEl = document.getElementById('agency-chart');
    const maxAgency = Math.max(...(byAgency.map(d => d.complaints_count) || [1]));
    agencyEl.innerHTML = byAgency.map(d => `
        <div class="flex items-center gap-3">
            <div class="w-28 text-xs text-gray-600 truncate">${d.name}</div>
            <div class="flex-1 bg-gray-100 rounded-full h-3">
                <div class="bg-purple-500 h-3 rounded-full" style="width:${d.complaints_count > 0 ? Math.round(d.complaints_count/maxAgency*100) : 0}%"></div>
            </div>
            <div class="w-6 text-xs text-gray-700 text-right">${d.complaints_count}</div>
        </div>
    `).join('') || '<p class="text-gray-400 text-sm">Belum ada data</p>';

    // Channels
    const channelEl = document.getElementById('channel-chart');
    channelEl.innerHTML = (aiPerf.channels || []).map(c => `
        <div class="flex items-center gap-2 bg-gray-50 px-4 py-3 rounded-lg">
            <span class="w-3 h-3 bg-blue-500 rounded-full"></span>
            <span class="text-sm font-medium capitalize">${c.channel}</span>
            <span class="text-sm text-gray-500 font-bold">${c.count}</span>
        </div>
    `).join('') || '<p class="text-gray-400 text-sm">Belum ada data</p>';

    // Responsible AI table
    const metricsTable = document.getElementById('ai-metrics-table');
    const metrics = [
        ['Total Pengaduan', aiPerf.total_complaints, 'Total laporan masuk ke sistem'],
        ['Dianalisis AI', aiPerf.ai_analyzed, 'Laporan yang diproses oleh Langflow'],
        ['Rata-rata Confidence', (aiPerf.avg_confidence_pct ?? 0) + '%', 'Rata-rata kepercayaan AI dalam klasifikasi'],
        ['Confidence ≥ 80%', aiPerf.high_confidence, 'Klasifikasi dengan kepercayaan tinggi'],
        ['Confidence 70–80%', aiPerf.medium_confidence, 'Klasifikasi dengan kepercayaan sedang'],
        ['Confidence < 70%', aiPerf.low_confidence, 'Butuh review manual (Responsible AI)'],
        ['Koreksi Manusia', aiPerf.human_corrections, 'Klasifikasi yang dikoreksi oleh operator'],
        ['Tingkat Koreksi', (aiPerf.correction_rate ?? 0) + '%', 'Persentase laporan yang dikoreksi manusia'],
    ];
    metricsTable.innerHTML = metrics.map(([label, val, note]) => `
        <tr>
            <td class="px-4 py-3 font-medium text-gray-900">${label}</td>
            <td class="px-4 py-3 font-bold text-gray-800">${val ?? '—'}</td>
            <td class="px-4 py-3 text-gray-500 text-xs">${note}</td>
        </tr>
    `).join('');
}

loadAnalytics();
</script>
@endsection
