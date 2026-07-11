<x-app-layout>
    <x-slot name="header">
        <div class="page-title">Buku Kas Umum (BKU)</div>
    </x-slot>

    @if(session('success'))
        <div class="alert-success mb-6">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="alert-error mb-6">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            {{ session('error') }}
        </div>
    @endif

    @if($belumMetodePengadaan > 0)
        <div class="alert-warning mb-6">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            <span>Ada <strong>{{ $belumMetodePengadaan }}</strong> transaksi pengeluaran yang belum ditandai metode pengadaannya (SIPLAH/Non-SIPLAH). Silakan lengkapi pada data yang belum terisi.</span>
        </div>
    @endif

    @if($belumCetakKwitansi > 0)
        <div class="alert-warning mb-6">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
            <span>Ada <strong>{{ $belumCetakKwitansi }}</strong> transaksi pengeluaran yang belum dicetak kwitansinya. Silakan cetak kwitansi untuk setiap transaksi pengeluaran.</span>
        </div>
    @endif

    <form method="GET" action="{{ route('transaksi-bku.index') }}">
    <div class="card">
        <div class="card-header">
            <div>
                <span class="card-title">Daftar Transaksi BKU</span>
                <p class="text-xs text-slate-400 mt-0.5">Realisasi Anggaran Harian</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <div class="flex items-center gap-2">
                    <select name="bulan" onchange="this.form.submit()" class="form-select py-1.5 text-sm">
                        <option value="">Semua Bulan</option>
                        @foreach(range(1,12) as $m)
                            <option value="{{ $m }}" {{ $bulan == $m ? 'selected' : '' }}>
                                {{ \Carbon\Carbon::create()->month($m)->translatedFormat('F') }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <a href="{{ route('laporan.index') }}"
                   class="btn btn-success btn-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    Lihat Laporan
                </a>
                <a href="{{ route('transaksi-bku.create') }}" class="btn-primary">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Tambah Transaksi
                </a>
            </div>
        </div>

        @php
            $totalPenerimaan = $transaksis->filter(function($t) { return strtolower($t->jenis) == 'penerimaan'; })->sum('jumlah');
            $totalPengeluaran = $transaksis->filter(function($t) { return strtolower($t->jenis) == 'pengeluaran'; })->sum('jumlah');
            $saldoAkhir = $totalPenerimaan - $totalPengeluaran;
        @endphp

        <div class="grid grid-cols-3 divide-x divide-slate-100 border-b border-slate-100">
            <div class="px-6 py-3.5 text-center">
                <div class="text-xs text-slate-500 uppercase tracking-wide font-medium">Total Penerimaan</div>
                <div class="text-lg font-bold text-emerald-700 mt-1">Rp {{ number_format($totalPenerimaan, 0, ',', '.') }}</div>
            </div>
            <div class="px-6 py-3.5 text-center">
                <div class="text-xs text-slate-500 uppercase tracking-wide font-medium">Total Pengeluaran</div>
                <div class="text-lg font-bold text-red-700 mt-1">Rp {{ number_format($totalPengeluaran, 0, ',', '.') }}</div>
            </div>
            <div class="px-6 py-3.5 text-center">
                <div class="text-xs text-slate-500 uppercase tracking-wide font-medium">Saldo Akhir</div>
                <div class="text-lg font-bold {{ $saldoAkhir >= 0 ? 'text-blue-700' : 'text-red-700' }} mt-1">
                    Rp {{ number_format($saldoAkhir, 0, ',', '.') }}
                </div>
            </div>
        </div>

        <div class="overflow-x-auto">
    </form>

    <form id="batch-kwitansi-form" method="POST" action="{{ route('transaksi-bku.cetak-kwitansi-batch') }}" target="_blank">
        @csrf
        <div class="px-4 py-2 bg-slate-50 border-b border-slate-200">
            <button type="button" id="btn-cetak-terpilih" class="btn btn-info btn-sm" onclick="cetakTerpilih()" disabled>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                Cetak Terpilih
            </button>
        </div>

            <table class="data-table">
                <thead>
                    <tr>
                        <th class="text-center w-10">
                            <input type="checkbox" id="check-all" onchange="toggleCheckAll(this)" class="rounded border-slate-300">
                        </th>
                        <th class="text-center">No</th>
                        <th>Tanggal</th>
                        <th>No Bukti</th>
                        <th>Kode Kegiatan</th>
                        <th>Kode Rekening</th>
                        <th>Jenis Belanja</th>
                        <th>Uraian</th>
                        <th>Toko/Penerima</th>
                        <th class="text-right whitespace-nowrap" style="min-width:130px">Penerimaan</th>
                        <th class="text-right whitespace-nowrap" style="min-width:130px">Pengeluaran</th>
                        <th class="text-right whitespace-nowrap" style="min-width:130px">Saldo</th>
                        <th class="text-center">Status</th>
                        <th class="text-center">Pengadaan</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($transaksis as $no => $transaksi)
                        <tr>
                            <td class="text-center">
                                <input type="checkbox" name="ids[]" value="{{ $transaksi->id }}" class="check-item rounded border-slate-300" onchange="updateCetakTerpilih()">
                            </td>
                            <td class="text-center text-slate-500">{{ $no + 1 }}</td>
                            <td class="font-medium text-slate-700 whitespace-nowrap">
                                {{ \Carbon\Carbon::parse($transaksi->tanggal)->format('d/m/Y') }}
                            </td>
                            <td class="font-mono text-xs text-slate-800 whitespace-nowrap">{{ $transaksi->no_bukti }}</td>
                            <td class="text-xs text-slate-600 whitespace-nowrap">
                                {{ $transaksi->rkasItem && $transaksi->rkasItem->program ? $transaksi->rkasItem->program->kode : '-' }}
                            </td>
                            <td class="text-xs font-mono text-slate-600 whitespace-nowrap">
                                {{ $transaksi->rkasItem && $transaksi->rkasItem->kodeRekening ? $transaksi->rkasItem->kodeRekening->kode : '-' }}
                            </td>
                            <td class="whitespace-nowrap">
                                @php
                                    $jenisBelanja = $transaksi->rkasItem && $transaksi->rkasItem->kodeRekening
                                        ? optional($transaksi->rkasItem->kodeRekening->jenisBelanja)->nama
                                        : null;
                                @endphp
                                @if($jenisBelanja)
                                    <span class="badge badge-blue">{{ $jenisBelanja }}</span>
                                @else
                                    <span class="text-slate-300 text-xs">&mdash;</span>
                                @endif
                            </td>
                            <td class="max-w-[200px]">
                                <div class="truncate text-slate-700" title="{{ $transaksi->uraian }}">{{ $transaksi->uraian ?? '-' }}</div>
                                @if($transaksi->rkasItem)
                                    <div class="text-xs text-blue-500 mt-0.5 truncate" title="{{ $transaksi->rkasItem->uraian }}">{{ $transaksi->rkasItem->uraian }}</div>
                                @endif
                            </td>
                            <td class="text-slate-600 whitespace-nowrap">{{ $transaksi->toko_penerima ?? '-' }}</td>
                            <td class="text-right text-emerald-700 font-medium whitespace-nowrap">
                                @if(strtolower($transaksi->jenis) == 'penerimaan')
                                    Rp {{ number_format($transaksi->jumlah, 0, ',', '.') }}
                                @else
                                    <span class="text-slate-300">&mdash;</span>
                                @endif
                            </td>
                            <td class="text-right text-red-700 font-medium whitespace-nowrap">
                                @if(strtolower($transaksi->jenis) == 'pengeluaran')
                                    Rp {{ number_format($transaksi->jumlah, 0, ',', '.') }}
                                @else
                                    <span class="text-slate-300">&mdash;</span>
                                @endif
                            </td>
                            <td class="text-right font-semibold whitespace-nowrap {{ ($transaksi->saldo_berjalan ?? 0) >= 0 ? 'text-blue-700' : 'text-red-600' }}">
                                Rp {{ number_format($transaksi->saldo_berjalan ?? 0, 0, ',', '.') }}
                            </td>
                            <td class="text-center">
                                @if($transaksi->status_lunas)
                                    <span class="badge badge-green">Lunas</span>
                                @else
                                    <span class="badge badge-yellow">Proses</span>
                                @endif
                            </td>
                            <td class="text-center whitespace-nowrap">
                                @if($transaksi->metode_pengadaan === 'siplah')
                                    <span class="badge badge-blue">SIPLAH</span>
                                @elseif($transaksi->metode_pengadaan === 'non_siplah')
                                    <span class="badge badge-gray">Non-SIPLAH</span>
                                @else
                                    <span class="text-slate-300 text-xs">&mdash;</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <div class="flex items-center justify-center gap-1">
                                    <a href="{{ route('transaksi-bku.cetak-kwitansi', $transaksi) }}" target="_blank" class="btn btn-success btn-sm" title="Cetak Kwitansi">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                                        Kwitansi
                                    </a>
                                    <a href="{{ route('transaksi-bku.edit', $transaksi) }}" class="btn btn-secondary btn-sm" title="Edit">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                        Edit
                                    </a>
                                    <form action="{{ route('transaksi-bku.destroy', $transaksi) }}" method="POST" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Yakin ingin menghapus transaksi ini?')">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="15" class="py-12 text-center text-slate-400">
                                <svg class="w-12 h-12 mx-auto mb-3 opacity-30" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                <p class="font-medium">Belum ada transaksi</p>
                                <p class="text-xs mt-1">Klik "Tambah Transaksi" untuk memulai input BKU</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                @if($transaksis->count() > 0)
                <tfoot>
                    <tr class="bg-slate-50 font-semibold text-sm">
                        <td colspan="8" class="text-right text-slate-700">Total</td>
                        <td class="text-right text-emerald-700 whitespace-nowrap">Rp {{ number_format($totalPenerimaan, 0, ',', '.') }}</td>
                        <td class="text-right text-red-700 whitespace-nowrap">Rp {{ number_format($totalPengeluaran, 0, ',', '.') }}</td>
                        <td class="text-right whitespace-nowrap {{ $saldoAkhir >= 0 ? 'text-blue-700' : 'text-red-700' }}">Rp {{ number_format($saldoAkhir, 0, ',', '.') }}</td>
                        <td colspan="3"></td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </form>
    </div>

    <script>
        function toggleCheckAll(source) {
            document.querySelectorAll('.check-item').forEach(cb => cb.checked = source.checked);
            updateCetakTerpilih();
        }
        function updateCetakTerpilih() {
            const checked = document.querySelectorAll('.check-item:checked').length;
            const btn = document.getElementById('btn-cetak-terpilih');
            btn.disabled = checked === 0;
        }
        function cetakTerpilih() {
            const checked = document.querySelectorAll('.check-item:checked');
            if (checked.length === 0) return;
            document.getElementById('batch-kwitansi-form').submit();
        }
    </script>
</x-app-layout>
