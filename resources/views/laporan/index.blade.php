<x-app-layout>
    <x-slot name="header">
        <div class="page-title">Laporan</div>
    </x-slot>

    @if(!$tahunAnggaranAktif)
        <div class="alert-warning mb-6">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            <span>Tahun anggaran belum diaktifkan. Laporan tidak akan menampilkan data. Silakan aktifkan di menu <a href="{{ route('tahun-anggaran.index') }}" class="underline font-semibold hover:text-amber-900">Tahun Anggaran</a> terlebih dahulu.</span>
        </div>
    @endif

    @if(auth()->user()->isAdminKecamatan())
        <div class="card mb-6 border-0 shadow-sm" style="border-radius:16px;overflow:hidden;">
            <div class="h-1.5 bg-gradient-to-r from-indigo-400 via-indigo-500 to-indigo-600"></div>
            <div class="card-body" style="padding:20px 24px;">
                <div class="flex items-end gap-4 flex-wrap">
                    <div class="flex-1 min-w-[220px]">
                        <label class="form-label" style="font-size:11px;font-weight:600;color:#64748b;text-transform:uppercase;letter-spacing:0.5px;">Pilih Sekolah</label>
                        <select id="pilih-sekolah" class="form-select" style="border-radius:10px;border-color:#e2e8f0;">
                            <option value="">-- Pilih Sekolah --</option>
                            @foreach($sekolahs as $s)
                                <option value="{{ $s->id }}">{{ $s->nama }} ({{ $s->npsn }})</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6" id="laporan-cards">
            <div class="group block bg-white rounded-2xl border border-slate-200 shadow-sm hover:shadow-xl hover:border-emerald-300 transition-all duration-300 overflow-hidden cursor-pointer laporan-card" data-type="bku">
                <div class="h-2 bg-gradient-to-r from-emerald-400 to-emerald-600"></div>
                <div class="p-6 text-center">
                    <div class="w-16 h-16 mx-auto mb-4 rounded-2xl bg-gradient-to-br from-emerald-50 to-emerald-100 flex items-center justify-center group-hover:scale-110 transition-transform duration-300 shadow-sm">
                        <svg class="w-8 h-8 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    </div>
                    <h3 class="text-base font-bold text-slate-800 mb-1">BKU Bulanan</h3>
                    <p class="text-xs text-slate-500 leading-relaxed">Buku Kas Umum per bulan dengan saldo berjalan</p>
                </div>
            </div>

            <div class="group block bg-white rounded-2xl border border-slate-200 shadow-sm hover:shadow-xl hover:border-blue-300 transition-all duration-300 overflow-hidden cursor-pointer laporan-card" data-type="rekap">
                <div class="h-2 bg-gradient-to-r from-blue-400 to-blue-600"></div>
                <div class="p-6 text-center">
                    <div class="w-16 h-16 mx-auto mb-4 rounded-2xl bg-gradient-to-br from-blue-50 to-blue-100 flex items-center justify-center group-hover:scale-110 transition-transform duration-300 shadow-sm">
                        <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    </div>
                    <h3 class="text-base font-bold text-slate-800 mb-1">Rekap Realisasi</h3>
                    <p class="text-xs text-slate-500 leading-relaxed">Rekap realisasi per kode rekening per bulan</p>
                </div>
            </div>

            <div class="group block bg-white rounded-2xl border border-slate-200 shadow-sm hover:shadow-xl hover:border-amber-300 transition-all duration-300 overflow-hidden cursor-pointer laporan-card" data-type="kuartal">
                <div class="h-2 bg-gradient-to-r from-amber-400 to-amber-600"></div>
                <div class="p-6 text-center">
                    <div class="w-16 h-16 mx-auto mb-4 rounded-2xl bg-gradient-to-br from-amber-50 to-amber-100 flex items-center justify-center group-hover:scale-110 transition-transform duration-300 shadow-sm">
                        <svg class="w-8 h-8 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    </div>
                    <h3 class="text-base font-bold text-slate-800 mb-1">Rekap Kuartal</h3>
                    <p class="text-xs text-slate-500 leading-relaxed">Rekap realisasi per kuartal (3 bulan)</p>
                </div>
            </div>

            <div class="group block bg-white rounded-2xl border border-slate-200 shadow-sm hover:shadow-xl hover:border-violet-300 transition-all duration-300 overflow-hidden cursor-pointer laporan-card" data-type="siplah">
                <div class="h-2 bg-gradient-to-r from-violet-400 to-violet-600"></div>
                <div class="p-6 text-center">
                    <div class="w-16 h-16 mx-auto mb-4 rounded-2xl bg-gradient-to-br from-violet-50 to-violet-100 flex items-center justify-center group-hover:scale-110 transition-transform duration-300 shadow-sm">
                        <svg class="w-8 h-8 text-violet-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                    </div>
                    <h3 class="text-base font-bold text-slate-800 mb-1">Rekap SIPLAH</h3>
                    <p class="text-xs text-slate-500 leading-relaxed">Proporsi pengeluaran SIPLAH vs Non-SIPLAH</p>
                </div>
            </div>
        </div>

        <script>
            document.querySelectorAll('.laporan-card').forEach(function(card) {
                card.addEventListener('click', function() {
                    var select = document.getElementById('pilih-sekolah');
                    var sekolahId = select.value;
                    if (!sekolahId) {
                        alert('Pilih sekolah terlebih dahulu!');
                        select.focus();
                        return;
                    }
                    var type = card.getAttribute('data-type');
                    var bulan = new Date().getMonth() + 1;
                    var urls = {
                        'bku': '{{ url("laporan") }}/' + sekolahId + '/bku?bulan=' + bulan,
                        'rekap': '{{ url("laporan") }}/' + sekolahId + '/rekap-rekening?bulan=' + bulan,
                        'kuartal': '{{ url("laporan") }}/' + sekolahId + '/rekap-kuartal?bulan=' + bulan,
                        'siplah': '{{ url("laporan") }}/' + sekolahId + '/rekap-siplah?bulan=' + bulan
                    };
                    window.location.href = urls[type];
                });
            });
        </script>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <a href="{{ route('laporan.bku.preview', ['bulan' => date('n')]) }}"
               class="group block bg-white rounded-2xl border border-slate-200 shadow-sm hover:shadow-xl hover:border-emerald-300 transition-all duration-300 overflow-hidden">
                <div class="h-2 bg-gradient-to-r from-emerald-400 to-emerald-600"></div>
                <div class="p-6 text-center">
                    <div class="w-16 h-16 mx-auto mb-4 rounded-2xl bg-gradient-to-br from-emerald-50 to-emerald-100 flex items-center justify-center group-hover:scale-110 transition-transform duration-300 shadow-sm">
                        <svg class="w-8 h-8 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    </div>
                    <h3 class="text-base font-bold text-slate-800 mb-1">BKU Bulanan</h3>
                    <p class="text-xs text-slate-500 leading-relaxed">Buku Kas Umum per bulan dengan saldo berjalan</p>
                </div>
            </a>

            <a href="{{ route('laporan.rekap-rekening.preview', ['bulan' => date('n')]) }}"
               class="group block bg-white rounded-2xl border border-slate-200 shadow-sm hover:shadow-xl hover:border-blue-300 transition-all duration-300 overflow-hidden">
                <div class="h-2 bg-gradient-to-r from-blue-400 to-blue-600"></div>
                <div class="p-6 text-center">
                    <div class="w-16 h-16 mx-auto mb-4 rounded-2xl bg-gradient-to-br from-blue-50 to-blue-100 flex items-center justify-center group-hover:scale-110 transition-transform duration-300 shadow-sm">
                        <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    </div>
                    <h3 class="text-base font-bold text-slate-800 mb-1">Rekap Realisasi</h3>
                    <p class="text-xs text-slate-500 leading-relaxed">Rekap realisasi per kode rekening per bulan</p>
                </div>
            </a>

            <a href="{{ route('laporan.rekap-kuartal.preview', ['bulan' => date('n')]) }}"
               class="group block bg-white rounded-2xl border border-slate-200 shadow-sm hover:shadow-xl hover:border-amber-300 transition-all duration-300 overflow-hidden">
                <div class="h-2 bg-gradient-to-r from-amber-400 to-amber-600"></div>
                <div class="p-6 text-center">
                    <div class="w-16 h-16 mx-auto mb-4 rounded-2xl bg-gradient-to-br from-amber-50 to-amber-100 flex items-center justify-center group-hover:scale-110 transition-transform duration-300 shadow-sm">
                        <svg class="w-8 h-8 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    </div>
                    <h3 class="text-base font-bold text-slate-800 mb-1">Rekap Kuartal</h3>
                    <p class="text-xs text-slate-500 leading-relaxed">Rekap realisasi per kuartal (3 bulan)</p>
                </div>
            </a>

            <a href="{{ route('laporan.rekap-siplah.preview', ['bulan' => date('n')]) }}"
               class="group block bg-white rounded-2xl border border-slate-200 shadow-sm hover:shadow-xl hover:border-violet-300 transition-all duration-300 overflow-hidden">
                <div class="h-2 bg-gradient-to-r from-violet-400 to-violet-600"></div>
                <div class="p-6 text-center">
                    <div class="w-16 h-16 mx-auto mb-4 rounded-2xl bg-gradient-to-br from-violet-50 to-violet-100 flex items-center justify-center group-hover:scale-110 transition-transform duration-300 shadow-sm">
                        <svg class="w-8 h-8 text-violet-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                    </div>
                    <h3 class="text-base font-bold text-slate-800 mb-1">Rekap SIPLAH</h3>
                    <p class="text-xs text-slate-500 leading-relaxed">Proporsi pengeluaran SIPLAH vs Non-SIPLAH</p>
                </div>
            </a>
        </div>
    @endif
</x-app-layout>
