<?php

namespace App\Http\Controllers;

use App\Models\Kwitansi;
use App\Models\ProfilSekolah;
use App\Models\RkasItem;
use App\Models\TransaksiBku;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;

class TransaksiBkuController extends Controller
{
    public function index(Request $request)
    {
        $bulan = $request->input('bulan', ''); // '' = semua bulan
        
        $query = TransaksiBku::with('rkasItem.program', 'rkasItem.kodeRekening.jenisBelanja')
                              ->orderBy('tanggal')
                              ->orderBy('id');

        if ($bulan !== '') {
            $query->where('bulan', (int) $bulan);
        }

        $transaksis = $query->get();

        // Hitung saldo berjalan dari awal (semua data, lalu potong ke filter)
        $saldoAwal = 0;
        if ($bulan !== '') {
            // Hitung saldo kumulatif sebelum bulan yang difilter
            $sebelumnya = TransaksiBku::where('bulan', '<', (int) $bulan)
                                       ->orderBy('tanggal')->orderBy('id')->get();
            foreach ($sebelumnya as $t) {
                $saldoAwal += strtolower($t->jenis) == 'penerimaan' ? $t->jumlah : -$t->jumlah;
            }
        }

        $saldo = $saldoAwal;
        foreach ($transaksis as $transaksi) {
            $saldo += strtolower($transaksi->jenis) == 'penerimaan' ? $transaksi->jumlah : -$transaksi->jumlah;
            $transaksi->saldo_berjalan = $saldo;
        }

        $belumMetodePengadaan = TransaksiBku::where('jenis', 'pengeluaran')
            ->whereNull('metode_pengadaan')
            ->count();

        $belumCetakKwitansi = TransaksiBku::where('jenis', 'pengeluaran')
            ->whereDoesntHave('kwitansi')
            ->count();

        return view('transaksi-bku.index', compact('transaksis', 'bulan', 'belumMetodePengadaan', 'belumCetakKwitansi'));
    }

    public function create()
    {
        $rkasItems = RkasItem::with('bulanRencana')->get();
        $profil = auth()->user()->profilSekolah;
        $npsn = $profil ? $profil->npsn : '00000000';
        
        $countPenerimaan = TransaksiBku::where('jenis', 'penerimaan')->count() + 1;
        $countPengeluaran = TransaksiBku::where('jenis', 'pengeluaran')->count() + 1;
        
        return view('transaksi-bku.create', compact('rkasItems', 'npsn', 'countPenerimaan', 'countPengeluaran'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'rkas_item_id' => 'nullable|exists:rkas_item,id',
            'tanggal' => 'required|date',
            'no_bukti' => 'required|unique:transaksi_bku,no_bukti',
            'jenis' => 'required|in:penerimaan,pengeluaran',
            'jumlah' => 'required|numeric',
            'toko_penerima' => 'nullable|string|max:255',
            'metode_pengadaan' => 'nullable|string|in:siplah,non_siplah',
            'uraian' => 'nullable|string',
        ]);

        $validated['created_by'] = auth()->id();
        $validated['sekolah_id'] = auth()->user()->sekolah_id;
        $validated['bulan'] = (int) date('n', strtotime($validated['tanggal']));

        // Validasi: Sisa anggaran bulan berjalan (uang cair kumulatif sampai bulan ini - pengeluaran kumulatif)
        if ($validated['jenis'] == 'pengeluaran' && $validated['rkas_item_id']) {
            $rkasItem = RkasItem::with('bulanRencana')->findOrFail($validated['rkas_item_id']);
            
            $rencanaKumulatif = $rkasItem->bulanRencana->where('bulan', '<=', $validated['bulan'])->sum('rencana');
            $realisasiKumulatif = $rkasItem->transaksiBkus()
                                           ->where('jenis', 'pengeluaran')
                                           ->where('bulan', '<=', $validated['bulan'])
                                           ->sum('jumlah');
            
            $sisaBulanBerjalan = $rencanaKumulatif - $realisasiKumulatif;

            if ($validated['jumlah'] > $sisaBulanBerjalan) {
                return back()->with('error', 'Gagal: Nominal Rp ' . number_format($validated['jumlah'], 0, ',', '.') . 
                                             ' melebihi sisa anggaran bulan berjalan (Rp ' . number_format($sisaBulanBerjalan, 0, ',', '.') . ').');
            }
        }

        TransaksiBku::create($validated);

        return redirect()->route('transaksi-bku.index')->with('success', 'Transaksi berhasil ditambahkan.');
    }

    public function edit(TransaksiBku $transaksiBku)
    {
        $rkasItems = RkasItem::all();
        return view('transaksi-bku.edit', compact('transaksiBku', 'rkasItems'));
    }

    public function update(Request $request, TransaksiBku $transaksiBku)
    {
        $validated = $request->validate([
            'rkas_item_id' => 'nullable|exists:rkas_item,id',
            'tanggal' => 'required|date',
            'no_bukti' => 'required|unique:transaksi_bku,no_bukti,' . $transaksiBku->id,
            'jenis' => 'required|in:penerimaan,pengeluaran',
            'jumlah' => 'required|numeric',
            'toko_penerima' => 'nullable|string|max:255',
            'metode_pengadaan' => 'nullable|string|in:siplah,non_siplah',
            'uraian' => 'nullable|string',
        ]);
        
        $validated['bulan'] = (int) date('n', strtotime($validated['tanggal']));

        // Validasi: Sisa anggaran bulan berjalan
        if ($validated['jenis'] == 'pengeluaran' && $validated['rkas_item_id']) {
            $rkasItem = RkasItem::with('bulanRencana')->findOrFail($validated['rkas_item_id']);
            
            $rencanaKumulatif = $rkasItem->bulanRencana->where('bulan', '<=', $validated['bulan'])->sum('rencana');
            $realisasiKumulatif = $rkasItem->transaksiBkus()
                                           ->where('id', '!=', $transaksiBku->id)
                                           ->where('jenis', 'pengeluaran')
                                           ->where('bulan', '<=', $validated['bulan'])
                                           ->sum('jumlah');
            
            $sisaBulanBerjalan = $rencanaKumulatif - $realisasiKumulatif;

            if ($validated['jumlah'] > $sisaBulanBerjalan) {
                return back()->with('error', 'Gagal Update: Nominal Rp ' . number_format($validated['jumlah'], 0, ',', '.') . 
                                             ' melebihi sisa anggaran bulan berjalan (Rp ' . number_format($sisaBulanBerjalan, 0, ',', '.') . ').');
            }
        }

        $transaksiBku->update($validated);

        return redirect()->route('transaksi-bku.index')->with('success', 'Transaksi berhasil diupdate.');
    }

    public function destroy(TransaksiBku $transaksiBku)
    {
        $transaksiBku->delete();
        return back()->with('success', 'Transaksi berhasil dihapus.');
    }

    public function cetakKwitansi(TransaksiBku $transaksiBku)
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

    public function cetakKwitansiBatch(Request $request)
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
            $safeNo = str_replace(['/', '\\'], '-', $transaksi->no_bukti);
            $filePath = 'kwitansi/kwitansi-' . $safeNo . '.pdf';

            $kwitansiPdf = Pdf::loadView('transaksi-bku.kwitansi', ['transaksiBku' => $transaksi, 'profil' => $profil])
                ->setPaper([0, 0, 609.4488, 935.433], 'portrait');
            Storage::disk('public')->put($filePath, $kwitansiPdf->output());

            $kwitansi = $transaksi->kwitansi()->firstOrNew([]);
            $kwitansi->sekolah_id = $transaksi->sekolah_id;
            $kwitansi->nomor = $transaksi->no_bukti;
            $kwitansi->dicetak_pada = now();
            $kwitansi->file_pdf_path = $filePath;
            $kwitansi->save();
        }

        return $pdf->stream($fileName);
    }
}
