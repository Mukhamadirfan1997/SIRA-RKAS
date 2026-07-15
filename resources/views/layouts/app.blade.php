<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ isset($pageTitle) ? $pageTitle . ' — ' : '' }}SIRA-RKAS</title>

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <link href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" rel="stylesheet" />
        <script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
        <script src="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/select2.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    </head>
    <body class="font-sans antialiased bg-slate-50">

        <div id="page-loader" class="fixed inset-0 z-50 flex items-center justify-center bg-white/80 transition-opacity duration-300" style="display:none">
            <div class="flex flex-col items-center gap-3">
                <svg class="animate-spin h-8 w-8 text-indigo-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span class="text-sm text-slate-500 font-medium">Memuat...</span>
            </div>
        </div>

        <script>
            document.addEventListener('DOMContentLoaded', function() { document.getElementById('page-loader').style.display = 'flex'; });
            window.addEventListener('load', function() {
                var el = document.getElementById('page-loader');
                el.style.opacity = '0';
                setTimeout(function() { el.style.display = 'none'; }, 300);
            });
        </script>

        @include('layouts.navigation')

        <div class="main-wrapper">

            <div class="top-header">
                <div class="flex items-center gap-3">
                    <button onclick="toggleSidebar()" class="p-2 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition-all duration-150" title="Toggle sidebar">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                        </svg>
                    </button>
                    @isset($header)
                        {{ $header }}
                    @endisset
                </div>
                <div class="flex items-center gap-3">
                    <div class="flex items-center gap-2.5">
                        <div class="sidebar-avatar text-xs">
                            {{ strtoupper(substr(Auth::user()->name, 0, 2)) }}
                        </div>
                        <span class="text-sm font-medium text-slate-600">{{ Auth::user()->name }}</span>
                    </div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="flex items-center gap-1.5 text-sm text-slate-400 hover:text-red-600 transition-all duration-150 hover:bg-red-50 px-3 py-1.5 rounded-lg">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                            Keluar
                        </button>
                    </form>
                </div>
            </div>

            <main class="page-content">
                @if(\Carbon\Carbon::now()->day >= 22)
                    <div class="alert-warning mb-6">
                        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        <span>Pengingat: Kita sudah memasuki minggu terakhir bulan <strong>{{ \Carbon\Carbon::now()->translatedFormat('F') }}</strong>. Silakan segera input transaksi BKU agar data Realisasi Anggaran selalu up-to-date.</span>
                    </div>
                @endif

                {{ $slot }}
            </main>

        </div>

        <script>
        (function() {
            if (localStorage.getItem('sidebar-collapsed') === 'true') {
                document.body.classList.add('sidebar-collapsed');
            }
            window.toggleSidebar = function() {
                document.body.classList.toggle('sidebar-collapsed');
                localStorage.setItem('sidebar-collapsed', document.body.classList.contains('sidebar-collapsed'));
            };
        })();
        </script>

    </body>
</html>
