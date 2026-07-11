<x-app-layout>
    @php
        $isAdmin = $isAdmin ?? false;
        $sid = $adminSekolahId ?? null;
    @endphp
    <x-slot name="header">
        <div class="page-title">Rekap Kuartal {{ $qLabel }}{{ $isAdmin && $profil ? ' — ' . $profil->nama : '' }}</div>
    </x-slot>

    <div class="card mb-6 border-0 shadow-sm" style="border-radius:16px;overflow:hidden;">
        <div class="h-1.5 bg-gradient-to-r from-amber-400 via-amber-500 to-amber-600"></div>
        <div class="card-body" style="padding:20px 24px;">
            <form method="GET" action="{{ $isAdmin ? route('admin.laporan.rekap-kuartal', ['sekolah' => $sid]) : route('laporan.rekap-kuartal.preview') }}" class="flex items-end gap-4 flex-wrap">
                <div class="flex-1 min-w-[180px]">
                    <label class="form-label" style="font-size:11px;font-weight:600;color:#64748b;text-transform:uppercase;letter-spacing:0.5px;">Bulan (menentukan kuartal)</label>
                    <select name="bulan" class="form-select" onchange="this.form.submit()" style="border-radius:10px;border-color:#e2e8f0;">
                        @foreach(range(1, 12) as $m)
                            <option value="{{ $m }}" {{ $bulan == $m ? 'selected' : '' }}>
                                {{ \Carbon\Carbon::create()->month($m)->translatedFormat('F') }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="min-w-[180px]">
                    <label class="form-label" style="font-size:11px;font-weight:600;color:#64748b;text-transform:uppercase;letter-spacing:0.5px;">Tanggal Cetak</label>
                    <input type="date" id="tanggal-cetak" value="{{ \Carbon\Carbon::now()->format('Y-m-d') }}" class="form-input" style="border-radius:10px;border-color:#e2e8f0;padding:8px 12px;">
                </div>
                <div class="flex gap-2 items-center">
                    <button type="button" onclick="cetakPdf()" class="inline-flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-emerald-500 to-emerald-600 text-white text-sm font-semibold rounded-xl hover:from-emerald-600 hover:to-emerald-700 shadow-sm transition-all">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                        Cetak PDF
                    </button>
                    <a href="{{ $isAdmin ? route('admin.laporan.rekap-kuartal.export-excel', ['sekolah' => $sid, 'bulan' => $bulan]) : route('laporan.rekap-kuartal.export-excel', ['bulan' => $bulan]) }}"
                       class="inline-flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-blue-500 to-blue-600 text-white text-sm font-semibold rounded-xl hover:from-blue-600 hover:to-blue-700 shadow-sm transition-all">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        Excel
                    </a>
                    <a href="{{ $isAdmin ? route('dashboard.kecamatan') : route('laporan.index') }}"
                       class="inline-flex items-center gap-1.5 px-3 py-2 text-sm font-medium text-slate-500 hover:text-slate-800 bg-slate-100 hover:bg-slate-200 rounded-xl transition-all ml-1">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                        Kembali
                    </a>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm" style="border-radius:16px;overflow:hidden;">
        <div class="card-header" style="border-bottom:1px solid #f1f5f9;padding:16px 24px;background:linear-gradient(135deg,#fffbeb 0%,#fef3c7 100%);">
            <span class="card-title" style="font-size:14px;">Rekap Kuartal {{ $qLabel }} — {{ $periodeLabel }}</span>
        </div>
        <div class="overflow-x-auto">
            <table class="data-table">
                <thead>
                    <tr>
                        <th class="text-center" style="width:4%">No</th>
                        <th style="width:14%">Kode Rekening</th>
                        <th style="width:22%">Uraian Anggaran</th>
                        @foreach($bulanNames as $name)
                            <th class="text-right" style="width:14%">{{ $name }}</th>
                        @endforeach
                        <th class="text-right" style="width:14%">Total {{ $qLabel }}</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $grandTotalPerBulan = array_fill_keys($bulanMonths, 0);
                        $grandTotalAll = 0;
                        $i = 1;
                    @endphp

                    @forelse($grouped as $jenisBelanja => $items)
                        <tr style="background:linear-gradient(135deg,#fffbeb 0%,#fef3c7 100%);">
                            <td colspan="{{ 3 + count($bulanNames) + 1 }}" class="font-bold text-amber-800 text-sm">{{ $jenisBelanja }}</td>
                        </tr>

                        @php
                            $subTotalPerBulan = array_fill_keys($bulanMonths, 0);
                            $subTotalAll = 0;
                        @endphp

                        @foreach($items->sortBy('kodeRekening.kode') as $item)
                            <tr>
                                <td class="text-center">{{ $i++ }}</td>
                                <td style="font-family:monospace">{{ $item->kodeRekening?->kode ?? '-' }}</td>
                                <td>{{ $item->uraian }}</td>
                                @foreach($bulanMonths as $b)
                                    @php $r = $item->realisasi_per_bulan[$b] ?? 0; @endphp
                                    <td class="text-right">{{ $r > 0 ? 'Rp ' . number_format($r, 0, ',', '.') : '—' }}</td>
                                @endforeach
                                <td class="text-right font-semibold">Rp {{ number_format($item->total_realisasi, 0, ',', '.') }}</td>
                            </tr>
                        @endforeach

                        @php
                            foreach ($bulanMonths as $b) {
                                $subTotalPerBulan[$b] += $items->sum(fn($it) => $it->realisasi_per_bulan[$b] ?? 0);
                            }
                            $subTotalAll += $items->sum('total_realisasi');
                        @endphp

                        <tr style="background:#f8fafc;">
                            <td colspan="3" class="text-right font-semibold text-sm text-slate-600">SUBTOTAL {{ strtoupper($jenisBelanja) }}</td>
                            @foreach($bulanMonths as $b)
                                <td class="text-right font-semibold">Rp {{ number_format($subTotalPerBulan[$b], 0, ',', '.') }}</td>
                            @endforeach
                            <td class="text-right font-semibold">Rp {{ number_format($subTotalAll, 0, ',', '.') }}</td>
                        </tr>

                        @php
                            foreach ($bulanMonths as $b) {
                                $grandTotalPerBulan[$b] += $subTotalPerBulan[$b];
                            }
                            $grandTotalAll += $subTotalAll;
                        @endphp
                    @empty
                        <tr>
                            <td colspan="{{ 3 + count($bulanNames) + 1 }}" class="text-center py-12 text-slate-400">
                                <svg class="w-10 h-10 mx-auto mb-2 opacity-30" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                Belum ada data anggaran.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                @if($grouped->count() > 0)
                    <tfoot>
                        <tr style="background:linear-gradient(135deg,#78350f 0%,#92400e 100%);">
                            <td colspan="3" class="text-right font-bold text-white">TOTAL KESELURUHAN</td>
                            @foreach($bulanMonths as $b)
                                <td class="text-right font-bold text-white">Rp {{ number_format($grandTotalPerBulan[$b], 0, ',', '.') }}</td>
                            @endforeach
                            <td class="text-right font-bold text-white">Rp {{ number_format($grandTotalAll, 0, ',', '.') }}</td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </div>

    <script>
    function cetakPdf() {
        var tgl = document.getElementById('tanggal-cetak').value;
        var bulan = {{ $bulan }};
        @if($isAdmin)
            var url = '{{ route("admin.laporan.rekap-kuartal", ["sekolah" => $sid]) }}?bulan=' + bulan + '&cetak=pdf&tanggal_cetak=' + tgl;
        @else
            var url = '{{ route("laporan.rekap-kuartal") }}?bulan=' + bulan + '&cetak=pdf&tanggal_cetak=' + tgl;
        @endif
        window.open(url, '_blank');
    }
    </script>
</x-app-layout>
