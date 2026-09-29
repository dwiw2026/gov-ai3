<!DOCTYPE html>
<html lang="id" class="h-full bg-gray-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'CivicAI') — Agen Cerdas Pengaduan Masyarakat</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full">
<div class="min-h-full">
    <!-- Navbar -->
    <nav class="bg-blue-900 shadow-lg">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <div class="flex items-center gap-8">
                    <a href="/dashboard" class="flex items-center gap-3">
                        <div class="w-8 h-8 bg-blue-400 rounded-lg flex items-center justify-center text-white font-bold text-sm">CA</div>
                        <span class="text-white font-semibold text-lg">CivicAI</span>
                        <span class="text-blue-300 text-xs hidden sm:block">Intelligent Government Response Agent</span>
                    </a>
                    <div class="hidden md:flex items-center gap-1">
                        <a href="/dashboard" class="text-blue-200 hover:text-white hover:bg-blue-800 px-3 py-2 rounded-md text-sm font-medium transition-colors">Dashboard</a>
                        <a href="/dashboard/complaints" class="text-blue-200 hover:text-white hover:bg-blue-800 px-3 py-2 rounded-md text-sm font-medium transition-colors">Pengaduan</a>
                        <a href="/dashboard/analytics" class="text-blue-200 hover:text-white hover:bg-blue-800 px-3 py-2 rounded-md text-sm font-medium transition-colors">Analitik</a>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <span class="inline-flex items-center gap-1.5 px-2 py-1 bg-green-500/20 text-green-300 rounded-full text-xs font-medium">
                        <span class="w-1.5 h-1.5 bg-green-400 rounded-full animate-pulse"></span>
                        IBM Bob + Langflow Active
                    </span>
                </div>
            </div>
        </div>
    </nav>

    <!-- Page Content -->
    <main class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="bg-white border-t border-gray-200 mt-8">
        <div class="max-w-7xl mx-auto px-4 py-4 text-center text-xs text-gray-500">
            CivicAI — Dari laporan menjadi tindakan &bull; IBM Bob + Langflow + Laravel + MariaDB
        </div>
    </footer>
</div>
</body>
</html>
