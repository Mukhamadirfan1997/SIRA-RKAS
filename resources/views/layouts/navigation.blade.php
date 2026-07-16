    <aside class="sidebar" id="sira-sidebar" aria-label="Navigasi utama">

    <div class="sidebar-logo">
        <div class="sidebar-logo-icon">SR</div>
        <div class="sidebar-logo-text">
            <div class="text-white font-bold text-sm leading-tight">SIRA-RKAS</div>
            <div class="text-slate-500 text-[11px]">Sistem Informasi Anggaran</div>
        </div>
    </div>

    <nav class="sidebar-nav">

        @if(auth()->user()->isAdminKecamatan())

            <a href="{{ route('dashboard.kecamatan') }}" class="sidebar-link {{ request()->routeIs('dashboard.kecamatan') ? 'active' : '' }}">
                <svg aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                <span class="nav-text">Dashboard Kecamatan</span>
            </a>

            <div class="sidebar-section-label">Pengelolaan Wilayah</div>

            <div x-data="{ open: {{ request()->routeIs('kecamatan.*') || request()->routeIs('profil-sekolah.*') || request()->routeIs('user-sekolah.*') ? 'true' : 'false' }} }">
                <button @click="open = !open" class="sidebar-dropdown-btn" :class="{ 'open': open }">
                    <div class="flex items-center gap-3">
                        <svg aria-hidden="true" class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        <span class="nav-text">Data Kecamatan</span>
                    </div>
                    <svg aria-hidden="true" class="chevron" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div class="sidebar-submenu" x-show="open" x-transition>
                    <a href="{{ route('kecamatan.index') }}" class="{{ request()->routeIs('kecamatan.*') ? 'text-blue-400!' : '' }}">
                        <svg aria-hidden="true" class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><circle cx="10" cy="10" r="3"/></svg>
                        <span class="nav-text">Kecamatan</span>
                    </a>
                    <a href="{{ route('profil-sekolah.index') }}" class="{{ request()->routeIs('profil-sekolah.*') ? 'text-blue-400!' : '' }}">
                        <svg aria-hidden="true" class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><circle cx="10" cy="10" r="3"/></svg>
                        <span class="nav-text">Profil Sekolah</span>
                    </a>
                    <a href="{{ route('user-sekolah.index') }}" class="{{ request()->routeIs('user-sekolah.*') ? 'text-blue-400!' : '' }}">
                        <svg aria-hidden="true" class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><circle cx="10" cy="10" r="3"/></svg>
                        <span class="nav-text">User Akun</span>
                    </a>
                </div>
            </div>

            <div class="sidebar-section-label">Master Data</div>

            <div x-data="{ open: {{ request()->routeIs('tahun-anggaran.*') || request()->routeIs('sumber-dana.*') || request()->routeIs('jenis-belanja.*') || request()->routeIs('master-program.*') || request()->routeIs('master-kode-rekening.*') ? 'true' : 'false' }} }">
                <button @click="open = !open" class="sidebar-dropdown-btn" :class="{ 'open': open }">
                    <div class="flex items-center gap-3">
                        <svg aria-hidden="true" class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4"/></svg>
                        <span class="nav-text">Referensi & Master</span>
                    </div>
                    <svg aria-hidden="true" class="chevron" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div class="sidebar-submenu" x-show="open" x-transition>
                    <a href="{{ route('tahun-anggaran.index') }}">
                        <svg aria-hidden="true" class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><circle cx="10" cy="10" r="3"/></svg>
                        <span class="nav-text">Tahun Anggaran</span>
                    </a>
                    <a href="{{ route('sumber-dana.index') }}">
                        <svg aria-hidden="true" class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><circle cx="10" cy="10" r="3"/></svg>
                        <span class="nav-text">Sumber Dana</span>
                    </a>
                    <a href="{{ route('jenis-belanja.index') }}">
                        <svg aria-hidden="true" class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><circle cx="10" cy="10" r="3"/></svg>
                        <span class="nav-text">Jenis Belanja</span>
                    </a>
                    <a href="{{ route('master-program.index') }}">
                        <svg aria-hidden="true" class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><circle cx="10" cy="10" r="3"/></svg>
                        <span class="nav-text">Master Program</span>
                    </a>
                    <a href="{{ route('master-kode-rekening.index') }}">
                        <svg aria-hidden="true" class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><circle cx="10" cy="10" r="3"/></svg>
                        <span class="nav-text">Kode Rekening</span>
                    </a>
                </div>
            </div>

            <div class="sidebar-section-label">Laporan</div>

            <a href="{{ route('laporan.index') }}" class="sidebar-link {{ request()->routeIs('laporan.*') ? 'active' : '' }}">
                <svg aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                <span class="nav-text">Semua Laporan</span>
            </a>

        @endif

        @if(!auth()->user()->isAdminKecamatan())

            <a href="{{ route('dashboard') }}" class="sidebar-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                <svg aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                <span class="nav-text">Dashboard</span>
            </a>

            <div class="sidebar-section-label">Anggaran RKAS</div>

            <a href="{{ route('rkas.index') }}" class="sidebar-link {{ request()->routeIs('rkas.*') ? 'active' : '' }}">
                <svg aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                <span class="nav-text">Data RKAS</span>
            </a>

            <a href="{{ route('import-rkas.index') }}" class="sidebar-link {{ request()->routeIs('import-rkas.*') ? 'active' : '' }}">
                <svg aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                <span class="nav-text">Import RKAS</span>
            </a>

            <div class="sidebar-section-label">Transaksi</div>

            <a href="{{ route('transaksi-bku.index') }}" class="sidebar-link {{ request()->routeIs('transaksi-bku.*') ? 'active' : '' }}">
                <svg aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                <span class="nav-text">Buku Kas Umum</span>
            </a>

            <div class="sidebar-section-label">Laporan</div>

            <a href="{{ route('laporan.index') }}" class="sidebar-link {{ request()->routeIs('laporan.index') || request()->routeIs('laporan.bku.*') || request()->routeIs('laporan.rekap-rekening.*') || request()->routeIs('laporan.rekap-kuartal.*') ? 'active' : '' }}">
                <svg aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                <span class="nav-text">Semua Laporan</span>
            </a>

        @endif

    </nav>

    <div class="sidebar-footer">
        <div class="sidebar-user">
            <div class="sidebar-avatar">{{ strtoupper(substr(Auth::user()->name, 0, 2)) }}</div>
            <div class="sidebar-user-text">
                <div class="text-sm font-semibold text-white truncate">{{ Auth::user()->name }}</div>
                <div class="text-xs text-slate-500 truncate">{{ Auth::user()->getRoleNames()->first() ?? 'user' }}</div>
            </div>
        </div>
        <button onclick="toggleDarkMode()" class="mt-2 w-full flex items-center gap-2 px-2 py-2 rounded-xl text-xs font-medium text-slate-400 hover:text-white hover:bg-white/5 transition-all duration-150" title="Toggle dark mode">
            <svg class="w-4 h-4 dark:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
            </svg>
            <svg class="w-4 h-4 hidden dark:block" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
            </svg>
            <span class="nav-text dark:hidden">Mode Gelap</span>
            <span class="nav-text hidden dark:block">Mode Terang</span>
        </button>
    </div>

</aside>
