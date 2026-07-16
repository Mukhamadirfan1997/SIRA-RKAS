<x-app-layout>
    <x-slot name="header">
        <div class="page-title">Dashboard Monitoring Kecamatan</div>
    </x-slot>

    @if(!$tahunAnggaranAktif)
        <div class="alert-warning mb-6">
            <svg aria-hidden="true" class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            <span>Tahun anggaran belum diaktifkan. Silakan aktifkan di menu <a href="{{ route('tahun-anggaran.index') }}" class="underline font-semibold hover:text-amber-900">Tahun Anggaran</a> terlebih dahulu.</span>
        </div>
    @endif

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
        <div class="stat-card indigo">
            <div class="stat-icon bg-indigo-50">
                <svg aria-hidden="true" class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
            </div>
            <div class="stat-label">Total Sekolah</div>
            <div class="stat-value text-indigo-700">{{ count($sekolahs) }}</div>
        </div>

        <div class="stat-card blue">
            <div class="stat-icon bg-blue-50">
                <svg aria-hidden="true" class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            </div>
            <div class="stat-label">Total Anggaran</div>
            <div class="stat-value text-blue-700">Rp {{ number_format($grandRencana, 0, ',', '.') }}</div>
        </div>

        <div class="stat-card green">
            <div class="stat-icon bg-emerald-50">
                <svg aria-hidden="true" class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
            </div>
            <div class="stat-label">Total Realisasi</div>
            <div class="stat-value text-emerald-700">Rp {{ number_format($grandRealisasi, 0, ',', '.') }}</div>
        </div>

        <div class="stat-card amber">
            <div class="stat-icon bg-amber-50">
                <svg aria-hidden="true" class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
            </div>
            <div class="stat-label">Rata-rata Capaian</div>
            <div class="stat-value text-amber-700">{{ $avgCapaian }}%</div>
            <div class="progress-bar">
                <div class="progress-fill bg-amber-400" style="width: {{ min(100, $avgCapaian) }}%"></div>
            </div>
        </div>

        <div class="stat-card red">
            <div class="stat-icon bg-red-50">
                <svg aria-hidden="true" class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
            </div>
            <div class="stat-label">Belum Upload</div>
            <div class="stat-value text-red-700">{{ $belumUploadCount }}</div>
            <div class="text-xs text-red-400 mt-1">dari {{ count($sekolahs) }} sekolah</div>
        </div>

        <div class="stat-card indigo">
            <div class="stat-icon bg-indigo-50">
                <svg aria-hidden="true" class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            </div>
            <div class="stat-label">RKAS</div>
            <div class="stat-value text-indigo-700">
                <a href="{{ route('rkas.index', ['tahun' => request('tahun', $tahunAnggaranAktif->tahun ?? '')]) }}" class="hover:underline">Lihat RKAS</a>
            </div>
        </div>
    </div>

    @if($belumUploadCount > 0)
        <div class="alert-warning mb-6">
            <svg aria-hidden="true" class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            <span>Ada <strong>{{ $belumUploadCount }} sekolah</strong> dari {{ count($sekolahs) }} sekolah yang belum upload data RKAS bulan <strong>{{ \Carbon\Carbon::create()->month((int) $bulan)->translatedFormat('F') }}</strong>. Silakan hubungi sekolah terkait untuk segera mengupload data.</span>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5 mb-6">
        <div class="lg:col-span-2 card">
            <div class="card-header">
                <span class="card-title">Perbandingan Realisasi per Sekolah</span>
            </div>
            <div class="card-body">
                @if(count($chartLabels) > 0)
                    <canvas id="barChart" style="max-height:280px"></canvas>
                @else
                    <div class="text-center py-8 text-slate-400">
                        <p class="text-sm">Belum ada data</p>
                    </div>
                @endif
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <span class="card-title">Proporsi Anggaran</span>
            </div>
            <div class="card-body flex items-center justify-center">
                @if($grandRencana > 0)
                    <canvas id="doughnutChart" style="max-height:260px"></canvas>
                @else
                    <div class="text-center py-8 text-slate-400">
                        <p class="text-sm">Belum ada data</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header flex items-center justify-between">
            <span class="card-title">Data Sekolah</span>
            <form method="GET" action="{{ route('dashboard.kecamatan') }}" class="flex items-center gap-3">
                <select name="tahun" class="form-select" onchange="this.form.submit()" style="min-width:100px">
                    @foreach($tahunList as $t)
                        <option value="{{ $t->tahun }}" {{ request('tahun', $tahunAnggaranAktif->tahun ?? '') == $t->tahun ? 'selected' : '' }}>
                            {{ $t->tahun }}
                        </option>
                    @endforeach
                </select>
                <select id="bulan" name="bulan" class="form-select" onchange="this.form.submit()" style="min-width:140px">
                    @foreach(range(1, 12) as $m)
                        <option value="{{ $m }}" {{ $bulan == $m ? 'selected' : '' }}>
                            {{ \Carbon\Carbon::create()->month($m)->translatedFormat('F') }}
                        </option>
                    @endforeach
                </select>
                <select name="status" class="form-select" onchange="this.form.submit()" style="min-width:160px">
                    <option value="">Semua Status</option>
                    <option value="belum_upload" {{ request('status') == 'belum_upload' ? 'selected' : '' }}>Belum Upload</option>
                    <option value="telah_import" {{ request('status') == 'telah_import' ? 'selected' : '' }}>Telah Import</option>
                </select>
            </form>
        </div>
        <div class="overflow-x-auto">
            <table class="data-table">
                <thead>
                    <tr>
                        <th class="text-center" style="width:40px">No</th>
                        <th>Sekolah</th>
                        <th class="text-right whitespace-nowrap" style="min-width:140px">Target Rencana</th>
                        <th class="text-right whitespace-nowrap" style="min-width:140px">Realisasi BKU</th>
                        <th class="text-right whitespace-nowrap" style="min-width:140px">Sisa Anggaran</th>
                        <th class="text-center">Capaian</th>
                        <th class="text-center">Status Import</th>
                        <th class="text-center">Laporan</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($sekolahs as $sekolah)
                        <tr class="{{ $sekolah->status_import === 'Belum Upload' ? 'bg-red-50/50' : '' }}">
                            <td class="text-center text-slate-500 text-sm">{{ $loop->iteration }}</td>
                            <td>
                                <div class="font-medium text-slate-800">{{ $sekolah->nama }}</div>
                                <div class="text-xs text-slate-500">NPSN: {{ $sekolah->npsn }}</div>
                            </td>
                            <td class="text-right text-slate-700 whitespace-nowrap">
                                Rp {{ number_format($sekolah->total_rencana, 0, ',', '.') }}
                            </td>
                            <td class="text-right font-semibold text-blue-600 whitespace-nowrap">
                                Rp {{ number_format($sekolah->total_realisasi, 0, ',', '.') }}
                            </td>
                            <td class="text-right font-semibold whitespace-nowrap {{ $sekolah->sisa >= 0 ? 'text-emerald-600' : 'text-red-600' }}">
                                Rp {{ number_format($sekolah->sisa, 0, ',', '.') }}
                            </td>
                            <td class="text-center">
                                @if($sekolah->persentase >= 100)
                                    <span class="badge badge-green">100% Tercapai</span>
                                @elseif($sekolah->persentase > 0)
                                    <span class="badge badge-blue">{{ number_format($sekolah->persentase, 1, ',', '.') }}%</span>
                                @else
                                    <span class="badge badge-gray">0%</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if($sekolah->status_import == 'success')
                                    <span class="badge badge-green">Telah Import</span>
                                @elseif($sekolah->status_import == 'processing')
                                    <span class="badge badge-yellow">Diproses...</span>
                                @elseif($sekolah->status_import == 'Belum Upload')
                                    <span class="badge badge-red">Belum Upload</span>
                                @else
                                    <span class="badge badge-gray">{{ $sekolah->status_import }}</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <div class="flex gap-1 justify-center flex-wrap">
                                    <a href="{{ route('admin.laporan.bku', ['sekolah' => $sekolah->id, 'bulan' => $bulan, 'tahun' => $tahunAnggaranAktif?->tahun]) }}"
                                       class="btn btn-success btn-sm">BKU</a>
                                     <a href="{{ route('admin.laporan.rekap-rekening', ['sekolah' => $sekolah->id, 'bulan' => $bulan, 'tahun' => $tahunAnggaranAktif?->tahun]) }}"
                                        class="btn btn-info btn-sm">Rekap</a>
                                     <a href="{{ route('admin.laporan.rekap-kuartal', ['sekolah' => $sekolah->id, 'bulan' => $bulan, 'tahun' => $tahunAnggaranAktif?->tahun]) }}"
                                        class="btn btn-warning btn-sm">Tribulan</a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-12 text-center text-slate-400">
                                <svg aria-hidden="true" class="w-12 h-12 mx-auto mb-3 opacity-30" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                                <p>Tidak ada data sekolah.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        @if(count($chartLabels) > 0)
        new Chart(document.getElementById('barChart'), {
            type: 'bar',
            data: {
                labels: @json($chartLabels),
                datasets: [
                    {
                        label: 'Rencana',
                        data: @json($chartRencana),
                        backgroundColor: 'rgba(99,102,241,0.15)',
                        borderColor: '#6366f1',
                        borderWidth: 1,
                        borderRadius: 4,
                        barPercentage: 0.7
                    },
                    {
                        label: 'Realisasi',
                        data: @json($chartRealisasi),
                        backgroundColor: 'rgba(16,185,129,0.7)',
                        borderColor: '#10b981',
                        borderWidth: 1,
                        borderRadius: 4,
                        barPercentage: 0.7
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'top', labels: { usePointStyle: true, padding: 16, font: { size: 11 } } },
                    tooltip: {
                        callbacks: {
                            label: function(ctx) {
                                return ctx.dataset.label + ': Rp ' + new Intl.NumberFormat('id-ID').format(ctx.parsed.y);
                            }
                        }
                    }
                },
                scales: {
                    x: { ticks: { font: { size: 10 }, maxRotation: 45 } },
                    y: {
                        ticks: {
                            font: { size: 10 },
                            callback: function(v) { return 'Rp ' + new Intl.NumberFormat('id-ID').format(v); }
                        },
                        grid: { color: 'rgba(0,0,0,0.04)' }
                    }
                }
            }
        });

        new Chart(document.getElementById('doughnutChart'), {
            type: 'doughnut',
            data: {
                labels: ['Realisasi', 'Sisa Anggaran'],
                datasets: [{
                    data: [{{ $grandRealisasi }}, {{ $grandSisa > 0 ? $grandSisa : 0 }}],
                    backgroundColor: ['#10b981', '#e2e8f0'],
                    borderWidth: 2,
                    borderColor: '#ffffff',
                    hoverOffset: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '65%',
                plugins: {
                    legend: { position: 'bottom', labels: { usePointStyle: true, padding: 12, font: { size: 11 } } },
                    tooltip: {
                        callbacks: {
                            label: function(ctx) {
                                return ctx.label + ': Rp ' + new Intl.NumberFormat('id-ID').format(ctx.parsed);
                            }
                        }
                    }
                }
            }
        });
        @endif
    });
    </script>
</x-app-layout>
