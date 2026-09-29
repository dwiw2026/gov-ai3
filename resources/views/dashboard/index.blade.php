@extends('layouts.app')
@section('title', 'Dashboard')

@section('content')
<div class="space-y-6">

    <!-- Hero Header -->
    <div class="bg-gradient-to-r from-blue-900 to-blue-700 rounded-2xl p-6 text-white">
        <h1 class="text-2xl font-bold">CivicAI — Government Command Center</h1>
        <p class="text-blue-200 mt-1 text-sm">IBM Bob &rarr; Tools &rarr; Langflow AI &rarr; Laravel Backend &rarr; OPD</p>
    </div>

    <!-- KPI Cards -->
    <div id="kpi-cards" class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white rounded-xl p-4 border border-gray-200 shadow-sm">
            <p class="text-xs text-gray-500 font-medium uppercase tracking-wide">Total Pengaduan</p>
            <p class="text-3xl font-bold text-gray-900 mt-1" id="kpi-total">—</p>
        </div>
        <div class="bg-white rounded-xl p-4 border border-gray-200 shadow-sm">
            <p class="text-xs text-gray-500 font-medium uppercase tracking-wide">Hari Ini</p>
            <p class="text-3xl font-bold text-blue-600 mt-1" id="kpi-today">—</p>
        </div>
        <div class="bg-white rounded-xl p-4 border border-gray-200 shadow-sm">
            <p class="text-xs text-gray-500 font-medium uppercase tracking-wide">Perlu Review</p>
            <p class="text-3xl font-bold text-amber-500 mt-1" id="kpi-review">—</p>
        </div>
        <div class="bg-white rounded-xl p-4 border border-gray-200 shadow-sm">
            <p class="text-xs text-gray-500 font-medium uppercase tracking-wide">SLA Terlewat</p>
            <p class="text-3xl font-bold text-red-500 mt-1" id="kpi-sla">—</p>
        </div>
        <div class="bg-white rounded-xl p-4 border border-gray-200 shadow-sm">
            <p class="text-xs text-gray-500 font-medium uppercase tracking-wide">AI Confidence Rata-rata</p>
            <p class="text-3xl font-bold text-green-600 mt-1" id="kpi-confidence">—</p>
        </div>
        <div class="bg-white rounded-xl p-4 border border-gray-200 shadow-sm">
            <p class="text-xs text-gray-500 font-medium uppercase tracking-wide">Diproses</p>
            <p class="text-3xl font-bold text-indigo-600 mt-1" id="kpi-inprogress">—</p>
        </div>
        <div class="bg-white rounded-xl p-4 border border-gray-200 shadow-sm">
            <p class="text-xs text-gray-500 font-medium uppercase tracking-wide">Selesai Hari Ini</p>
            <p class="text-3xl font-bold text-emerald-600 mt-1" id="kpi-resolved">—</p>
        </div>
        <div class="bg-white rounded-xl p-4 border border-gray-200 shadow-sm">
            <p class="text-xs text-gray-500 font-medium uppercase tracking-wide">Human Review Rate</p>
            <p class="text-3xl font-bold text-orange-500 mt-1" id="kpi-humanreview">—</p>
        </div>
    </div>

    <!-- Architecture Diagram + AI Demo -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        <!-- Architecture flow -->
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
            <h2 class="font-semibold text-gray-900 mb-4">Arsitektur CivicAI</h2>
            <div class="space-y-2 text-sm font-mono">
                <div class="flex items-center gap-2">
                    <span class="w-24 text-center bg-blue-100 text-blue-800 px-2 py-1 rounded font-semibold text-xs">IBM BOB</span>
                    <span class="text-gray-400">←</span>
                    <span class="text-gray-500">Citizen / Operator</span>
                </div>
                <div class="ml-4 text-gray-400">↓ pilih tool</div>
                <div class="flex items-center gap-2 ml-4 flex-wrap gap-y-1">
                    <span class="bg-green-100 text-green-800 px-2 py-0.5 rounded text-xs">analyze_complaint</span>
                    <span class="bg-green-100 text-green-800 px-2 py-0.5 rounded text-xs">get_location</span>
                    <span class="bg-green-100 text-green-800 px-2 py-0.5 rounded text-xs">check_routing</span>
                    <span class="bg-green-100 text-green-800 px-2 py-0.5 rounded text-xs">create_complaint</span>
                    <span class="bg-green-100 text-green-800 px-2 py-0.5 rounded text-xs">assign_officer</span>
                    <span class="bg-green-100 text-green-800 px-2 py-0.5 rounded text-xs">send_notification</span>
                </div>
                <div class="ml-4 text-gray-400">↓</div>
                <div class="flex items-center gap-2 ml-4">
                    <span class="w-24 text-center bg-purple-100 text-purple-800 px-2 py-1 rounded font-semibold text-xs">LANGFLOW</span>
                    <span class="text-gray-500 text-xs">LLM + RAG + Tools → Structured JSON</span>
                </div>
                <div class="ml-4 text-gray-400">↓</div>
                <div class="flex items-center gap-2 ml-4">
                    <span class="w-24 text-center bg-orange-100 text-orange-800 px-2 py-1 rounded font-semibold text-xs">LARAVEL</span>
                    <span class="text-gray-500 text-xs">Government Core + MariaDB</span>
                </div>
                <div class="ml-4 text-gray-400">↓ routing</div>
                <div class="flex items-center gap-2 ml-8 flex-wrap gap-y-1">
                    <span class="bg-red-100 text-red-800 px-2 py-0.5 rounded text-xs">Damkar</span>
                    <span class="bg-yellow-100 text-yellow-800 px-2 py-0.5 rounded text-xs">Satpol PP</span>
                    <span class="bg-blue-100 text-blue-800 px-2 py-0.5 rounded text-xs">Dishub</span>
                    <span class="bg-green-100 text-green-800 px-2 py-0.5 rounded text-xs">DLH</span>
                    <span class="bg-indigo-100 text-indigo-800 px-2 py-0.5 rounded text-xs">DPUPR</span>
                </div>
                <div class="ml-4 text-gray-400">↓ kanal</div>
                <div class="flex items-center gap-2 ml-4">
                    <span class="bg-cyan-100 text-cyan-800 px-2 py-0.5 rounded text-xs">Telegram Bot</span>
                    <span class="bg-gray-100 text-gray-700 px-2 py-0.5 rounded text-xs">IBM Bob Chat</span>
                </div>
            </div>
        </div>

        <!-- Live Complaint Tester (Demo) -->
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
            <h2 class="font-semibold text-gray-900 mb-1">Demo Analisis AI</h2>
            <p class="text-xs text-gray-500 mb-4">Kirim laporan → Langflow → Hasil klasifikasi</p>
            <textarea id="demo-message" rows="3"
                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 resize-none"
                placeholder="Contoh: Ada pengamen di Jalan A. Yani mengganggu pengguna jalan..."></textarea>
            <input id="demo-location" type="text"
                class="w-full mt-2 border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                placeholder="Lokasi (opsional)">
            <button onclick="analyzeDemo()"
                class="mt-3 w-full bg-blue-700 hover:bg-blue-800 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                Analisis via Langflow AI
            </button>
            <div id="demo-result" class="mt-4 hidden">
                <div class="bg-gray-900 rounded-lg p-4 text-xs font-mono text-green-400 overflow-x-auto max-h-64">
                    <pre id="demo-json"></pre>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Complaints Table -->
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm">
        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
            <h2 class="font-semibold text-gray-900">Pengaduan Terbaru</h2>
            <a href="/dashboard/complaints" class="text-blue-600 text-sm hover:underline">Lihat semua →</a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-xs text-gray-500 uppercase tracking-wide">
                    <tr>
                        <th class="px-6 py-3 text-left">Tiket</th>
                        <th class="px-6 py-3 text-left">Kategori</th>
                        <th class="px-6 py-3 text-left">Instansi</th>
                        <th class="px-6 py-3 text-left">Prioritas</th>
                        <th class="px-6 py-3 text-left">AI Conf.</th>
                        <th class="px-6 py-3 text-left">Status</th>
                        <th class="px-6 py-3 text-left">Waktu</th>
                    </tr>
                </thead>
                <tbody id="recent-complaints" class="divide-y divide-gray-100">
                    <tr><td colspan="7" class="px-6 py-8 text-center text-gray-400 text-sm">Memuat data...</td></tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Responsible AI Panel -->
    <div class="bg-white rounded-xl border border-amber-200 shadow-sm p-6">
        <div class="flex items-start gap-3">
            <div class="w-8 h-8 bg-amber-100 rounded-lg flex items-center justify-center flex-shrink-0">
                <svg class="w-5 h-5 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
            </div>
            <div>
                <h3 class="font-semibold text-gray-900">Responsible AI Framework</h3>
                <div class="mt-2 grid grid-cols-2 md:grid-cols-4 gap-3 text-xs">
                    <div class="bg-green-50 border border-green-200 rounded-lg p-3">
                        <p class="font-semibold text-green-800">Confidence Threshold</p>
                        <p class="text-green-700 mt-1">AI confidence &lt; 70% → human review required</p>
                    </div>
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-3">
                        <p class="font-semibold text-blue-800">Human Oversight</p>
                        <p class="text-blue-700 mt-1">AI merekomendasikan, manusia mengambil keputusan final</p>
                    </div>
                    <div class="bg-purple-50 border border-purple-200 rounded-lg p-3">
                        <p class="font-semibold text-purple-800">Audit Trail</p>
                        <p class="text-purple-700 mt-1">Setiap langkah AI tercatat dengan timestamp & confidence</p>
                    </div>
                    <div class="bg-orange-50 border border-orange-200 rounded-lg p-3">
                        <p class="font-semibold text-orange-800">Human Correction</p>
                        <p class="text-orange-700 mt-1">Petugas dapat mengoreksi kategori AI, disimpan sebagai audit</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

<script>
async function loadKPIs() {
    try {
        const r = await fetch('/api/analytics/overview');
        const d = await r.json();
        document.getElementById('kpi-total').textContent       = d.total_complaints ?? '—';
        document.getElementById('kpi-today').textContent       = d.today_complaints ?? '—';
        document.getElementById('kpi-review').textContent      = d.pending_review ?? '—';
        document.getElementById('kpi-sla').textContent         = d.sla_breached ?? '—';
        document.getElementById('kpi-confidence').textContent  = (d.avg_ai_confidence ?? 0) + '%';
        document.getElementById('kpi-inprogress').textContent  = d.in_progress ?? '—';
        document.getElementById('kpi-resolved').textContent    = d.resolved_today ?? '—';
        document.getElementById('kpi-humanreview').textContent = (d.human_review_rate ?? 0) + '%';
    } catch(e) { console.warn('KPI load failed', e); }
}

async function loadRecentComplaints() {
    try {
        const r = await fetch('/api/complaints?per_page=8');
        const d = await r.json();
        const tbody = document.getElementById('recent-complaints');
        if (!d.data?.length) {
            tbody.innerHTML = '<tr><td colspan="7" class="px-6 py-8 text-center text-gray-400 text-sm">Belum ada pengaduan</td></tr>';
            return;
        }
        const priorityColor = {critical:'red', high:'orange', medium:'yellow', low:'green'};
        tbody.innerHTML = d.data.map(c => `
            <tr class="hover:bg-gray-50 cursor-pointer" onclick="location.href='/dashboard/complaints/${c.ticket_number}'">
                <td class="px-6 py-3 font-mono text-xs text-blue-700">${c.ticket_number}</td>
                <td class="px-6 py-3 capitalize">${c.category ?? '—'}</td>
                <td class="px-6 py-3 text-xs">${c.agency?.name ?? '—'}</td>
                <td class="px-6 py-3">
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                        ${c.priority === 'critical' ? 'bg-red-100 text-red-800' :
                          c.priority === 'high' ? 'bg-orange-100 text-orange-800' :
                          c.priority === 'medium' ? 'bg-yellow-100 text-yellow-800' :
                          'bg-green-100 text-green-800'}">${c.priority}</span>
                </td>
                <td class="px-6 py-3 text-xs">${c.ai_confidence ? Math.round(c.ai_confidence*100)+'%' : '—'}</td>
                <td class="px-6 py-3">
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                        ${c.status === 'resolved' ? 'bg-green-100 text-green-800' :
                          c.status === 'awaiting_review' ? 'bg-amber-100 text-amber-800' :
                          'bg-blue-100 text-blue-800'}">${c.status}</span>
                </td>
                <td class="px-6 py-3 text-xs text-gray-500">${new Date(c.created_at).toLocaleString('id-ID')}</td>
            </tr>
        `).join('');
    } catch(e) { console.warn('Complaints load failed', e); }
}

async function analyzeDemo() {
    const message  = document.getElementById('demo-message').value.trim();
    const location = document.getElementById('demo-location').value.trim();
    if (!message) { alert('Masukkan deskripsi laporan terlebih dahulu.'); return; }

    const btn = document.querySelector('button[onclick="analyzeDemo()"]');
    btn.textContent = 'Menganalisis...';
    btn.disabled = true;

    try {
        const r = await fetch('/api/complaints/analyze', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
            body: JSON.stringify({ message, location_text: location || undefined, channel: 'web' }),
        });
        const d = await r.json();
        document.getElementById('demo-json').textContent = JSON.stringify(d, null, 2);
        document.getElementById('demo-result').classList.remove('hidden');
    } catch(e) {
        alert('Gagal menganalisis: ' + e.message);
    } finally {
        btn.textContent = 'Analisis via Langflow AI';
        btn.disabled = false;
    }
}

loadKPIs();
loadRecentComplaints();
setInterval(() => { loadKPIs(); loadRecentComplaints(); }, 30000);
</script>
@endsection
