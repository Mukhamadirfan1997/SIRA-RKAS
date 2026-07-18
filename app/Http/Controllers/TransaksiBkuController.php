<?php

namespace App\Http\Controllers;

use App\Models\Kwitansi;
use App\Models\ProfilSekolah;
use App\Models\RkasItem;
use App\Models\SumberDana;
use App\Models\TahunAnggaran;
use App\Models\TransaksiBku;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;

class TransaksiBkuController extends Controller
{
    public function index(Request $request): \Illuminate\View\View
    {
        $bulanRaw = $request->input('bulan', date('n'));
        $bulan = is_string($bulanRaw) || is_numeric($bulanRaw) ? $bulanRaw : '';
        $tahunAnggaranAktif = TahunAnggaran::getActive();
        $tahunList = TahunAnggaran::orderBy('tahun', 'desc')->get();

        $tahunInput = $request->input('tahun');
        if ($tahunInput) {
            $tahunRecord = TahunAnggaran::where('tahun', $tahunInput)->first();
            if ($tahunRecord) {
                $tahunAnggaranAktif = $tahunRecord;
            }
        }
        $sumberDanas = SumberDana::orderBy('kode')->get();
        $sumberDanaId = $request->input('sumber_dana_id');

        $query = TransaksiBku::with('rkasItem.program', 'rkasItem.kodeRekening.jenisBelanja')
            ->where('tahun_anggaran_id', $tahunAnggaranAktif?->id)
            ->orderBy('tanggal')
            ->orderBy('id');

        if ($bulan !== '') {
            $query->where('bulan', is_numeric($bulan) ? (int) $bulan : 0);
        }

        if ($sumberDanaId) {
            $query->where('sumber_dana_id', $sumberDanaId);
        }

        $searchRaw = $request->input('search');
        $search = is_string($searchRaw) ? $searchRaw : '';
        if ($search !== '') {
            $query->where(function (\Illuminate\Database\Eloquent\Builder $q) use ($search) {
                $q->where('no_bukti', 'LIKE', "%{$search}%")
                  ->orWhere('uraian', 'LIKE', "%{$search}%")
                  ->orWhere('toko_penerima', 'LIKE', "%{$search}%");
            });
        }

        $transaksis = $query->paginate(50);

        $saldoAwal = 0;
        if ($bulan !== '') {
            $saldoRecord = TransaksiBku::where('tahun_anggaran_id', $tahunAnggaranAktif?->id)
                ->where('bulan', '<', is_numeric($bulan) ? (int) $bulan : 0)
                ->when($sumberDanaId, fn($q) => $q->where('sumber_dana_id', $sumberDanaId))
                ->selectRaw("SUM(CASE WHEN LOWER(jenis) = 'penerimaan' THEN jumlah ELSE -jumlah END) as saldo")
                ->first();
            $saldoAwal = $saldoRecord ? (float) $saldoRecord->saldo : 0.0;
        } else {
            $saldoRecord = TransaksiBku::where('tahun_anggaran_id', $tahunAnggaranAktif?->id)
                ->when($sumberDanaId, fn($q) => $q->where('sumber_dana_id', $sumberDanaId))
                ->selectRaw("SUM(CASE WHEN LOWER(jenis) = 'penerimaan' THEN jumlah ELSE -jumlah END) as saldo")
                ->first();
            $saldoAwal = $saldoRecord ? (float) $saldoRecord->saldo : 0.0;
        }

        $saldo = $saldoAwal;
        foreach ($transaksis as $transaksi) {
            $saldo += strtolower($transaksi->jenis) == 'penerimaan' ? $transaksi->jumlah : -$transaksi->jumlah;
            $transaksi->saldo_berjalan = $saldo;
        }

        $bulanQuery = $bulan !== '' ? (int) $bulan : null;
        $totals = TransaksiBku::where('tahun_anggaran_id', $tahunAnggaranAktif?->id)
            ->when($bulanQuery, fn($q) => $q->where('bulan', $bulanQuery))
            ->when($sumberDanaId, fn($q) => $q->where('sumber_dana_id', $sumberDanaId))
            ->selectRaw("
                COALESCE(SUM(CASE WHEN LOWER(jenis) = 'penerimaan' THEN jumlah ELSE 0 END), 0) as total_penerimaan,
                COALESCE(SUM(CASE WHEN LOWER(jenis) = 'pengeluaran' THEN jumlah ELSE 0 END), 0) as total_pengeluaran
            ")->firstOrFail();
        $totalPenerimaan = (float) $totals->total_penerimaan;
        $totalPengeluaran = (float) $totals->total_pengeluaran;
        $saldoAkhir = $totalPenerimaan - $totalPengeluaran;

        $belumMetodePengadaan = TransaksiBku::where('tahun_anggaran_id', $tahunAnggaranAktif?->id)
            ->when($sumberDanaId, fn($q) => $q->where('sumber_dana_id', $sumberDanaId))
            ->where('jenis', 'pengeluaran')
            ->whereNull('metode_pengadaan')
            ->count();

        $belumCetakKwitansi = TransaksiBku::where('tahun_anggaran_id', $tahunAnggaranAktif?->id)
            ->when($sumberDanaId, fn($q) => $q->where('sumber_dana_id', $sumberDanaId))
            ->where('jenis', 'pengeluaran')
            ->whereDoesntHave('kwitansi')
            ->count();

        return view('transaksi-bku.index', compact(
            'transaksis', 'bulan', 'totalPenerimaan', 'totalPengeluaran', 'saldoAkhir',
            'belumMetodePengadaan', 'belumCetakKwitansi', 'tahunAnggaranAktif', 'tahunList',
            'sumberDanas', 'sumberDanaId'
        ));
    }

    public function create(): \Illuminate\View\View
    {
        $authUser = auth()->user();
        $profil = $authUser ? $authUser->profilSekolah : null;
        $npsn = $profil ? $profil->npsn : '00000000';
        $tahunAnggaranRecord = TahunAnggaran::where('status', true)->first(['id']);
        $tahunAnggaranId = $tahunAnggaranRecord ? $tahunAnggaranRecord->id : 0;

        $countPenerimaan = TransaksiBku::where('tahun_anggaran_id', $tahunAnggaranId)
            ->where('jenis', 'penerimaan')->count() + 1;
        $countPengeluaran = TransaksiBku::where('tahun_anggaran_id', $tahunAnggaranId)
            ->where('jenis', 'pengeluaran')->count() + 1;

        return view('transaksi-bku.create', compact('npsn', 'countPenerimaan', 'countPengeluaran'));
    }

    public function store(Request $request): \Illuminate\Http\RedirectResponse
    {
        $validated = $request->validate([
            'rkas_item_id' => 'nullable|exists:rkas_item,id',
            'tanggal' => 'required|date',
            'no_bukti' => 'required|unique:transaksi_bku,no_bukti',
            'jenis' => 'required|in:penerimaan,pengeluaran',
            'jumlah' => 'required|numeric',
            'toko_penerima' => 'nullable|string|max:255',
            'metode_pengadaan' => 'nullable|string|in:siplah,non_siplah',
            'volume' => 'nullable|numeric|min:0',
            'satuan' => 'nullable|string|max:50',
            'uraian' => 'nullable|string',
            'override_anggaran' => 'nullable|in:1,on,true',
            'override_note' => 'nullable|string|max:500',
        ]);

        $tanggal = (string) $validated['tanggal'];
        $jenis = (string) $validated['jenis'];
        $jumlah = (float) $validated['jumlah'];
        $rkasItemId = $validated['rkas_item_id'] ?? null;
        $noBukti = (string) $validated['no_bukti'];
        $overrideNote = $validated['override_note'] ?? null;

        $user = auth()->user();
        if ($user === null) {
            abort(403);
        }

        $validated['created_by'] = $user->id;
        $validated['sekolah_id'] = $user->sekolah_id;
        $validated['bulan'] = (int) Carbon::parse($tanggal)->month;
        $taRec = TahunAnggaran::where('status', true)->first(['id']);
        $validated['tahun_anggaran_id'] = $taRec ? $taRec->id : 0;

        if (!empty($rkasItemId)) {
            $rkasItem = RkasItem::find($rkasItemId);
            $validated['sumber_dana_id'] = $rkasItem?->sumber_dana_id;
            if ($rkasItem && empty($request->input('satuan'))) {
                $validated['satuan'] = $rkasItem->satuan;
            }
        }

        if ($jenis == 'pengeluaran' && !empty($rkasItemId)) {
            $rkasItem = RkasItem::with('bulanRencana')->findOrFail($rkasItemId);

            $rencanaKumulatif = $rkasItem->bulanRencana->where('bulan', '<=', $validated['bulan'])->sum('rencana');
            $realisasiKumulatif = $rkasItem->transaksiBkus()
                                           ->where('jenis', 'pengeluaran')
                                           ->where('bulan', '<=', $validated['bulan'])
                                           ->sum('jumlah');

            $sisaBulanBerjalan = $rencanaKumulatif - $realisasiKumulatif;

            $isOverriding = $request->boolean('override_anggaran') && !empty($overrideNote);

            if ($jumlah > $sisaBulanBerjalan && !$isOverriding) {
                return back()->with('error', 'Gagal: Nominal Rp ' . number_format($jumlah, 0, ',', '.') .
                                             ' melebihi sisa anggaran bulan berjalan (Rp ' . number_format($sisaBulanBerjalan, 0, ',', '.') . '). Gunakan opsi override jika ingin melanjutkan.');
            }

            if ($isOverriding) {
                \App\Models\AuditLog::create([
                    'user_id' => $user->id,
                    'sekolah_id' => $user->sekolah_id,
                    'tabel' => 'transaksi_bku',
                    'aksi' => 'override_anggaran',
                    'data_baru' => [
                        'no_bukti' => $noBukti,
                        'jumlah' => $jumlah,
                        'sisa_anggaran' => $sisaBulanBerjalan,
                        'catatan' => $overrideNote,
                    ],
                ]);
            }
        }

        unset($validated['override_anggaran'], $validated['override_note']);

        TransaksiBku::create($validated);

        Cache::increment('dash_ver_' . $user->id);

        return redirect()->route('transaksi-bku.index')->with('success', 'Transaksi berhasil ditambahkan.');
    }

    public function edit(TransaksiBku $transaksiBku): \Illuminate\View\View
    {
        $transaksiBku->load('rkasItem.program', 'rkasItem.kodeRekening');
        $selectedRkas = null;
        if ($transaksiBku->rkasItem) {
            $item = $transaksiBku->rkasItem;
            $selectedRkas = [
                'id' => $item->id,
                'text' => $item->no_urut . '. ' . $item->uraian,
                'program' => $item->program?->nama,
                'kode' => $item->kodeRekening?->kode,
                'tarif' => (float) $item->tarif,
                'satuan' => $item->satuan,
                'sisa' => (float) ($item->jumlah - $item->transaksiBkus()->where('jenis', 'pengeluaran')->sum('jumlah')),
            ];
        }
        return view('transaksi-bku.edit', compact('transaksiBku', 'selectedRkas'));
    }

    public function update(Request $request, TransaksiBku $transaksiBku): \Illuminate\Http\RedirectResponse
    {
        $validated = $request->validate([
            'rkas_item_id' => 'nullable|exists:rkas_item,id',
            'tanggal' => 'required|date',
            'no_bukti' => 'required|unique:transaksi_bku,no_bukti,' . $transaksiBku->id,
            'jenis' => 'required|in:penerimaan,pengeluaran',
            'jumlah' => 'required|numeric',
            'toko_penerima' => 'nullable|string|max:255',
            'metode_pengadaan' => 'nullable|string|in:siplah,non_siplah',
            'volume' => 'nullable|numeric|min:0',
            'satuan' => 'nullable|string|max:50',
            'uraian' => 'nullable|string',
        ]);

        $tanggal = (string) $validated['tanggal'];
        $jenis = (string) $validated['jenis'];
        $jumlah = (float) $validated['jumlah'];
        $rkasItemId = $validated['rkas_item_id'] ?? null;

        $validated['bulan'] = (int) Carbon::parse($tanggal)->month;
        $taRec2 = TahunAnggaran::where('status', true)->first(['id']);
        $validated['tahun_anggaran_id'] = $taRec2 ? $taRec2->id : 0;

        if (!empty($rkasItemId)) {
            $rkasItem = RkasItem::find($rkasItemId);
            $validated['sumber_dana_id'] = $rkasItem?->sumber_dana_id;
            if ($rkasItem && empty($request->input('satuan'))) {
                $validated['satuan'] = $rkasItem->satuan;
            }
        }

        if ($jenis == 'pengeluaran' && !empty($rkasItemId)) {
            $rkasItem = RkasItem::with('bulanRencana')->findOrFail($rkasItemId);

            $rencanaKumulatif = $rkasItem->bulanRencana->where('bulan', '<=', $validated['bulan'])->sum('rencana');
            $realisasiKumulatif = $rkasItem->transaksiBkus()
                                           ->where('id', '!=', $transaksiBku->id)
                                           ->where('jenis', 'pengeluaran')
                                           ->where('bulan', '<=', $validated['bulan'])
                                           ->sum('jumlah');

            $sisaBulanBerjalan = $rencanaKumulatif - $realisasiKumulatif;

            if ($jumlah > $sisaBulanBerjalan) {
                return back()->with('error', 'Gagal Update: Nominal Rp ' . number_format($jumlah, 0, ',', '.') .
                                             ' melebihi sisa anggaran bulan berjalan (Rp ' . number_format($sisaBulanBerjalan, 0, ',', '.') . ').');
            }
        }

        $transaksiBku->update($validated);

        Cache::increment('dash_ver_' . auth()->id());

        return redirect()->route('transaksi-bku.index')->with('success', 'Transaksi berhasil diupdate.');
    }

    public function destroy(TransaksiBku $transaksiBku): \Illuminate\Http\RedirectResponse
    {
        $transaksiBku->delete();

        Cache::increment('dash_ver_' . auth()->id());

        return back()->with('success', 'Transaksi berhasil dihapus.');
    }

    public function cetakKwitansi(TransaksiBku $transaksiBku): \Illuminate\Http\Response
    {
        $transaksiBku->load('rkasItem.program.parent.parent', 'rkasItem.kodeRekening', 'sekolah');
        $profil = $transaksiBku->sekolah;

        $pdf = Pdf::loadView('transaksi-bku.kwitansi', compact('transaksiBku', 'profil'))
            ->setPaper([0, 0, 609.4488, 935.433], 'portrait');

        $safeFileName = str_replace(['/', '\\'], '-', $transaksiBku->no_bukti);
        $fileName = 'kwitansi-' . $safeFileName . '.pdf';
        $filePath = 'kwitansi/' . $fileName;

        Storage::disk('public')->put($filePath, $pdf->output());

        $kwitansi = $transaksiBku->kwitansi()->firstOrNew([]);
        $kwitansi->sekolah_id = $transaksiBku->sekolah_id;
        $kwitansi->nomor = $transaksiBku->no_bukti;
        $kwitansi->dicetak_pada = now();
        $kwitansi->file_pdf_path = $filePath;
        $kwitansi->save();

        return $pdf->stream($fileName);
    }

    /** @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse */
    public function cetakKwitansiBatch(Request $request): \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
    {
        $ids = $request->input('ids', []);

        if (empty($ids)) {
            return back()->with('error', 'Pilih minimal satu transaksi untuk dicetak.');
        }

        $transaksis = TransaksiBku::with('rkasItem.program.parent.parent', 'rkasItem.kodeRekening', 'sekolah')
            ->whereIn('id', $ids)
            ->orderBy('tanggal')
            ->orderBy('id')
            ->get();

        if ($transaksis->isEmpty()) {
            return back()->with('error', 'Data transaksi tidak ditemukan.');
        }

        $profil = $transaksis->first()->sekolah;

        $pdf = Pdf::loadView('transaksi-bku.kwitansi-batch', compact('transaksis', 'profil'))
            ->setPaper([0, 0, 609.4488, 935.433], 'portrait');

        $fileName = 'kwitansi-batch-' . now()->format('YmdHis') . '.pdf';

        foreach ($transaksis as $transaksi) {
            $kwitansi = $transaksi->kwitansi()->firstOrNew([]);
            $kwitansi->sekolah_id = $transaksi->sekolah_id;
            $kwitansi->nomor = $transaksi->no_bukti;
            $kwitansi->dicetak_pada = now();
            $kwitansi->file_pdf_path = 'kwitansi/' . $fileName;
            $kwitansi->save();
        }

        return $pdf->stream($fileName);
    }
}
