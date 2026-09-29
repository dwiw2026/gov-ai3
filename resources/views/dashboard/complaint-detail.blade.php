@extends('layouts.app')
@section('title', $complaint->ticket_number)

@section('content')
<div class="space-y-6">

    <div class="flex items-center gap-3">
        <a href="/dashboard/complaints" class="text-blue-600 hover:underline text-sm">← Kembali</a>
        <span class="text-gray-400">/</span>
        <span class="font-mono text-blue-800 font-semibold">{{ $complaint->ticket_number }}</span>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Main Info -->
        <div class="lg:col-span-2 space-y-4">

            <!-- Header Card -->
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h1 class="text-xl font-bold text-gray-900 font-mono">{{ $complaint->ticket_number }}</h1>
                        <p class="text-sm text-gray-500 mt-1">{{ $complaint->created_at->format('d M Y, H:i') }}</p>
                    </div>
                    <div class="flex items-center gap-2 flex-wrap justify-end">
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium
                            {{ $complaint->status === 'resolved' ? 'bg-green-100 text-green-800' :
                               ($complaint->status === 'awaiting_review' ? 'bg-amber-100 text-amber-800' :
                               ($complaint->status === 'rejected' ? 'bg-red-100 text-red-800' : 'bg-blue-100 text-blue-800')) }}">
                            {{ $complaint->status_label }}
                        </span>
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium
                            {{ $complaint->priority === 'critical' ? 'bg-red-100 text-red-800' :
                               ($complaint->priority === 'high' ? 'bg-orange-100 text-orange-800' :
                               ($complaint->priority === 'medium' ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800')) }}">
                            {{ ucfirst($complaint->priority) }}
                        </span>
                    </div>
                </div>

                <div class="mt-4 p-4 bg-gray-50 rounded-lg">
                    <p class="text-sm font-medium text-gray-600 mb-1">Laporan Asli</p>
                    <p class="text-gray-900">{{ $complaint->raw_message }}</p>
                </div>

                @if($complaint->location_text)
                <div class="mt-3 flex items-center gap-2 text-sm text-gray-600">
                    <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/></svg>
                    {{ $complaint->location_text }}
                </div>
                @endif
            </div>

            <!-- AI Analysis -->
            <div class="bg-white rounded-xl border border-blue-200 shadow-sm p-6">
                <div class="flex items-center gap-2 mb-4">
                    <div class="w-6 h-6 bg-blue-100 rounded flex items-center justify-center">
                        <svg class="w-4 h-4 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
                    </div>
                    <h2 class="font-semibold text-gray-900">Analisis AI (via Langflow)</h2>
                    @if($complaint->ai_confidence)
                        <span class="ml-auto text-sm font-semibold {{ $complaint->ai_confidence >= 0.80 ? 'text-green-600' : ($complaint->ai_confidence >= 0.70 ? 'text-amber-600' : 'text-red-600') }}">
                            Confidence: {{ round($complaint->ai_confidence * 100) }}%
                        </span>
                    @endif
                </div>

                @if($complaint->requires_human_review)
                <div class="mb-4 p-3 bg-amber-50 border border-amber-200 rounded-lg flex items-start gap-2">
                    <svg class="w-4 h-4 text-amber-600 mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    <p class="text-sm text-amber-800"><strong>Review Manual Diperlukan</strong> — AI confidence di bawah ambang batas ({{ round(config('civicai.ai.confidence_threshold', 0.70) * 100) }}%).</p>
                </div>
                @endif

                <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                    <div class="bg-gray-50 rounded-lg p-3">
                        <p class="text-xs text-gray-500">Kategori</p>
                        <p class="font-semibold capitalize mt-0.5">{{ $complaint->category ?? '—' }}</p>
                    </div>
                    <div class="bg-gray-50 rounded-lg p-3">
                        <p class="text-xs text-gray-500">Sub-kategori</p>
                        <p class="font-semibold capitalize mt-0.5">{{ $complaint->subcategory ?? '—' }}</p>
                    </div>
                    <div class="bg-gray-50 rounded-lg p-3">
                        <p class="text-xs text-gray-500">Instansi</p>
                        <p class="font-semibold mt-0.5">{{ $complaint->agency?->name ?? '—' }}</p>
                    </div>
                    @if($complaint->ai_reason)
                    <div class="col-span-2 md:col-span-3 bg-blue-50 rounded-lg p-3">
                        <p class="text-xs text-blue-600">Alasan AI</p>
                        <p class="text-sm text-blue-900 mt-0.5">{{ $complaint->ai_reason }}</p>
                    </div>
                    @endif
                    @if($complaint->ai_recommended_action)
                    <div class="col-span-2 md:col-span-3 bg-green-50 rounded-lg p-3">
                        <p class="text-xs text-green-600">Rekomendasi Tindakan</p>
                        <p class="text-sm text-green-900 mt-0.5">{{ $complaint->ai_recommended_action }}</p>
                    </div>
                    @endif
                </div>

                @if($complaint->corrected_category)
                <div class="mt-3 p-3 bg-orange-50 border border-orange-200 rounded-lg text-sm">
                    <p class="font-semibold text-orange-800">Koreksi Manusia</p>
                    <p class="text-orange-700">Kategori dikoreksi menjadi: <strong>{{ $complaint->corrected_category }}</strong></p>
                    @if($complaint->correction_notes)
                    <p class="text-orange-600 mt-1">{{ $complaint->correction_notes }}</p>
                    @endif
                </div>
                @endif
            </div>

            <!-- Audit Trail -->
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
                <h2 class="font-semibold text-gray-900 mb-4">Audit Trail</h2>
                <div class="space-y-3">
                    @forelse($complaint->auditLogs as $log)
                    <div class="flex items-start gap-3">
                        <div class="w-2 h-2 bg-blue-500 rounded-full mt-2 flex-shrink-0"></div>
                        <div class="flex-1">
                            <div class="flex items-center gap-2">
                                <span class="text-sm font-medium text-gray-900">{{ ucfirst($log->event) }}</span>
                                <span class="text-xs text-gray-400">{{ $log->created_at->format('d/m H:i') }}</span>
                                @if($log->ai_confidence)
                                    <span class="text-xs {{ $log->ai_confidence >= 0.80 ? 'text-green-600' : 'text-amber-600' }}">conf: {{ round($log->ai_confidence * 100) }}%</span>
                                @endif
                            </div>
                            @if($log->description)
                                <p class="text-sm text-gray-600 mt-0.5">{{ $log->description }}</p>
                            @endif
                        </div>
                    </div>
                    @empty
                    <p class="text-sm text-gray-400">Tidak ada audit log</p>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-4">

            <!-- Quick Info -->
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 space-y-3 text-sm">
                <h3 class="font-semibold text-gray-900">Informasi</h3>
                <div class="flex justify-between">
                    <span class="text-gray-500">Kanal</span>
                    <span class="font-medium capitalize">{{ $complaint->channel }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500">Warga</span>
                    <span class="font-medium">{{ $complaint->citizen_name ?? 'Anonim' }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500">SLA</span>
                    @if($complaint->sla_deadline)
                        <span class="font-medium {{ $complaint->isSlaBreached() ? 'text-red-600' : 'text-green-600' }}">
                            {{ $complaint->isSlaBreached() ? 'Terlewat' : $complaint->sla_deadline->format('d/m H:i') }}
                        </span>
                    @else
                        <span class="text-gray-400">—</span>
                    @endif
                </div>
                @if($complaint->officer)
                <div class="flex justify-between">
                    <span class="text-gray-500">Petugas</span>
                    <span class="font-medium">{{ $complaint->officer->name }}</span>
                </div>
                @endif
            </div>

            <!-- Update Status -->
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
                <h3 class="font-semibold text-gray-900 mb-3">Update Status</h3>
                <form id="status-form" class="space-y-3">
                    @csrf
                    <select id="new-status" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                        <option value="in_progress">Diproses</option>
                        <option value="resolved">Selesai</option>
                        <option value="rejected">Ditolak</option>
                    </select>
                    <textarea id="correction-notes" rows="2" placeholder="Catatan (opsional)"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm resize-none"></textarea>
                    <button type="button" onclick="updateStatus()"
                        class="w-full bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-blue-800 transition-colors">
                        Update Status
                    </button>
                </form>
            </div>

            <!-- Raw AI Output -->
            @if($complaint->ai_raw_output)
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
                <h3 class="font-semibold text-gray-900 mb-2">Output Langflow (Raw)</h3>
                <div class="bg-gray-900 rounded-lg p-3 text-xs font-mono text-green-400 max-h-40 overflow-y-auto">
                    <pre>{{ json_encode($complaint->ai_raw_output, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>

<script>
async function updateStatus() {
    const status = document.getElementById('new-status').value;
    const notes  = document.getElementById('correction-notes').value;
    try {
        const r = await fetch('/api/complaints/{{ $complaint->ticket_number }}/status', {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify({ status, correction_notes: notes || undefined }),
        });
        const d = await r.json();
        if (d.success) {
            location.reload();
        } else {
            alert('Gagal update status');
        }
    } catch(e) { alert('Error: ' + e.message); }
}
</script>
@endsection
