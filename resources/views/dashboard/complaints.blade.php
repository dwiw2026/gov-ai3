@extends('layouts.app')
@section('title', 'Daftar Pengaduan')

@section('content')
<div class="space-y-6">

    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl font-bold text-gray-900">Daftar Pengaduan</h1>
            <p class="text-sm text-gray-500 mt-1">Manajemen tiket pengaduan masyarakat</p>
        </div>
    </div>

    <!-- Filters -->
    <form method="GET" class="bg-white rounded-xl border border-gray-200 shadow-sm p-4">
        <div class="flex flex-wrap gap-3">
            <select name="status" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
                <option value="">Semua Status</option>
                <option value="pending" @selected(request('status')=='pending')>Menunggu</option>
                <option value="awaiting_review" @selected(request('status')=='awaiting_review')>Perlu Review</option>
                <option value="routed" @selected(request('status')=='routed')>Diteruskan</option>
                <option value="assigned" @selected(request('status')=='assigned')>Ditugaskan</option>
                <option value="in_progress" @selected(request('status')=='in_progress')>Diproses</option>
                <option value="resolved" @selected(request('status')=='resolved')>Selesai</option>
            </select>
            <select name="agency_id" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
                <option value="">Semua Instansi</option>
                @foreach($agencies as $agency)
                    <option value="{{ $agency->id }}" @selected(request('agency_id')==$agency->id)>{{ $agency->name }}</option>
                @endforeach
            </select>
            <button type="submit" class="bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-blue-800 transition-colors">Filter</button>
            <a href="/dashboard/complaints" class="border border-gray-300 text-gray-700 px-4 py-2 rounded-lg text-sm font-medium hover:bg-gray-50 transition-colors">Reset</a>
        </div>
    </form>

    <!-- Table -->
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-xs text-gray-500 uppercase tracking-wide">
                    <tr>
                        <th class="px-6 py-3 text-left">Tiket</th>
                        <th class="px-6 py-3 text-left">Deskripsi</th>
                        <th class="px-6 py-3 text-left">Kategori</th>
                        <th class="px-6 py-3 text-left">Instansi</th>
                        <th class="px-6 py-3 text-left">Prioritas</th>
                        <th class="px-6 py-3 text-left">AI Conf.</th>
                        <th class="px-6 py-3 text-left">Status</th>
                        <th class="px-6 py-3 text-left">Kanal</th>
                        <th class="px-6 py-3 text-left">Waktu</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($complaints as $c)
                    <tr class="hover:bg-gray-50 cursor-pointer" onclick="location.href='/dashboard/complaints/{{ $c->ticket_number }}'">
                        <td class="px-6 py-3 font-mono text-xs text-blue-700 font-semibold">{{ $c->ticket_number }}</td>
                        <td class="px-6 py-3 max-w-xs">
                            <p class="truncate text-gray-900">{{ Str::limit($c->raw_message, 60) }}</p>
                            @if($c->location_text)
                            <p class="text-xs text-gray-400 truncate">{{ $c->location_text }}</p>
                            @endif
                        </td>
                        <td class="px-6 py-3 capitalize text-gray-700">{{ $c->category ?? '—' }}</td>
                        <td class="px-6 py-3 text-xs text-gray-600">{{ $c->agency?->name ?? '—' }}</td>
                        <td class="px-6 py-3">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                                {{ $c->priority === 'critical' ? 'bg-red-100 text-red-800' :
                                   ($c->priority === 'high' ? 'bg-orange-100 text-orange-800' :
                                   ($c->priority === 'medium' ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800')) }}">
                                {{ $c->priority }}
                            </span>
                        </td>
                        <td class="px-6 py-3 text-xs">
                            @if($c->ai_confidence)
                                <span class="{{ $c->ai_confidence >= 0.80 ? 'text-green-600' : ($c->ai_confidence >= 0.70 ? 'text-amber-600' : 'text-red-600') }} font-semibold">
                                    {{ round($c->ai_confidence * 100) }}%
                                </span>
                            @else —
                            @endif
                        </td>
                        <td class="px-6 py-3">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                                {{ $c->status === 'resolved' ? 'bg-green-100 text-green-800' :
                                   ($c->status === 'awaiting_review' ? 'bg-amber-100 text-amber-800' :
                                   ($c->status === 'rejected' ? 'bg-red-100 text-red-800' : 'bg-blue-100 text-blue-800')) }}">
                                {{ $c->status_label }}
                            </span>
                            @if($c->requires_human_review && !in_array($c->status, ['resolved','rejected']))
                                <span class="ml-1 text-amber-500" title="Perlu review manual">⚠</span>
                            @endif
                        </td>
                        <td class="px-6 py-3">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-700">
                                {{ $c->channel }}
                            </span>
                        </td>
                        <td class="px-6 py-3 text-xs text-gray-500">{{ $c->created_at->diffForHumans() }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="px-6 py-12 text-center text-gray-400">
                            <p>Belum ada pengaduan</p>
                            <p class="text-xs mt-1">Pengaduan masuk melalui Telegram Bot atau IBM Bob</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($complaints->hasPages())
        <div class="px-6 py-4 border-t border-gray-100">
            {{ $complaints->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
