<?php

namespace App\Http\Controllers;

use App\Exports\BkuExport;
use App\Exports\RekapRekeningExport;
use App\Exports\RekapKuartalExport;
use App\Exports\RekapSiplahExport;
use App\Models\ProfilSekolah;
use App\Models\TransaksiBku;
use App\Models\RkasItem;
use App\Models\TahunAnggaran;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;

class LaporanController extends Controller
{
    /**
     * Laporan BKU per bulan — tampilan web & cetak PDF
     */
    public function bku(Request $request)
    {
        $bulan   = (int) $request->get('bulan', date('n'));
        $rawTanggal = $request->get('tanggal_cetak', '');
        $tanggalCetak = $rawTanggal && \Carbon\Carbon::hasFormat($rawTanggal, 'Y-m-d')
            ? \Carbon\Carbon::parse($rawTanggal)->translatedFormat('d F Y')
            : ($rawTanggal ?: \Carbon\Carbon::now()->translatedFormat('d F Y'));
        $tahunAnggaranAktif = TahunAnggaran::where('status', true)->first();
        $profil  = auth()->user()->profilSekolah;

        $transaksis = TransaksiBku::with('rkasItem.program', 'rkasItem.kodeRekening.jenisBelanja')
                                   ->where('bulan', $bulan)
                                   ->orderBy('tanggal')
                                   ->orderBy('id')
                                   ->get();

        // Hitung saldo awal (kumulatif bulan sebelumnya)
        $saldoAwal = TransaksiBku::where('bulan', '<', $bulan)->get()
                        ->reduce(fn($carry, $t) =>
                            $carry + (strtolower($t->jenis) == 'penerimaan' ? $t->jumlah : -$t->jumlah), 0);

        $saldo = $saldoAwal;
        foreach ($transaksis as $t) {
            $saldo += strtolower($t->jenis) == 'penerimaan' ? $t->jumlah : -$t->jumlah;
            $t->saldo_berjalan = $saldo;
        }

        $totalPenerimaan  = $transaksis->where('jenis', 'penerimaan')->sum('jumlah');
        $totalPengeluaran = $transaksis->where('jenis', 'pengeluaran')->sum('jumlah');
        $saldoAkhir       = $saldoAwal + $totalPenerimaan - $totalPengeluaran;

        if ($request->get('cetak') == 'pdf') {
            $namaSekolah = $profil ? preg_replace('/[^a-zA-Z0-9]/', '_', $profil->nama) : 'sekolah';
            $bulanLabel = $bulan ? \Carbon\Carbon::create()->month($bulan)->translatedFormat('F') : '';
            $tahunLabel = $tahunAnggaranAktif?->tahun ?? date('Y');
            $pdf = Pdf::loadView('laporan.bku', compact(
                'transaksis', 'profil', 'bulan', 'tahunAnggaranAktif',
                'saldoAwal', 'totalPenerimaan', 'totalPengeluaran', 'saldoAkhir', 'tanggalCetak'
            ))->setPaper('a4', 'landscape');

            return $pdf->stream('BKU-' . $namaSekolah . '-' . $bulanLabel . '_' . $tahunLabel . '.pdf');
        }

        return view('laporan.bku', compact(
            'transaksis', 'profil', 'bulan', 'tahunAnggaranAktif',
            'saldoAwal', 'totalPenerimaan', 'totalPengeluaran', 'saldoAkhir', 'tanggalCetak'
        ));
    }

    /**
     * Rekap Realisasi per Kode Rekening per Bulan
     */
    public function rekapRekening(Request $request)
    {
        $bulan = (int) $request->get('bulan', date('n'));
        $rawTanggal = $request->get('tanggal_cetak', '');
        $tanggalCetak = $rawTanggal && \Carbon\Carbon::hasFormat($rawTanggal, 'Y-m-d')
            ? \Carbon\Carbon::parse($rawTanggal)->translatedFormat('d F Y')
            : ($rawTanggal ?: \Carbon\Carbon::now()->translatedFormat('d F Y'));
        $tahunAnggaranAktif = TahunAnggaran::where('status', true)->first();
        $profil = auth()->user()->profilSekolah;

        // Ambil semua rkas_item milik sekolah ini + tahun aktif
        $rkasItems = collect();
        if ($tahunAnggaranAktif) {
            $rkasItems = RkasItem::with(['kodeRekening.jenisBelanja', 'program', 'bulanRencana', 'transaksiBkus'])
                                  ->where('tahun_anggaran_id', $tahunAnggaranAktif->id)
                                  ->get()
                                  ->map(function ($item) use ($bulan) {
                                      $rencana    = $item->bulanRencana->where('bulan', $bulan)->sum('rencana');
                                      $realisasi  = $item->transaksiBkus->where('jenis', 'pengeluaran')
                                                                         ->where('bulan', $bulan)->sum('jumlah');
                                      $item->rencana_bulan   = $rencana;
                                      $item->realisasi_bulan = $realisasi;
                                      $item->sisa_bulan      = $rencana - $realisasi;
                                      $item->persen          = $rencana > 0 ? round(($realisasi / $rencana) * 100, 1) : 0;
                                      return $item;
                                  });
        }

        // Kelompokkan berdasarkan Jenis Belanja
        $grouped = $rkasItems->groupBy(fn($item) => $item->kodeRekening?->jenisBelanja?->nama ?? 'Tidak Terkategori');

        if ($request->get('cetak') == 'pdf') {
            $namaSekolah = $profil ? preg_replace('/[^a-zA-Z0-9]/', '_', $profil->nama) : 'sekolah';
            $bulanLabel = $bulan ? \Carbon\Carbon::create()->month($bulan)->translatedFormat('F') : '';
            $tahunLabel = $tahunAnggaranAktif?->tahun ?? date('Y');
            $pdf = Pdf::loadView('laporan.rekap-rekening', compact(
                'grouped', 'profil', 'bulan', 'tahunAnggaranAktif', 'rkasItems', 'tanggalCetak'
            ))->setPaper('a4', 'landscape');

            return $pdf->stream('Rekap_Rekening-' . $namaSekolah . '-' . $bulanLabel . '_' . $tahunLabel . '.pdf');
        }

        return view('laporan.rekap-rekening', compact(
            'grouped', 'profil', 'bulan', 'tahunAnggaranAktif', 'rkasItems', 'tanggalCetak'
        ));
    }

    public function bkuExportExcel(Request $request)
    {
        $bulan = (int) $request->get('bulan', date('n'));
        $profil = auth()->user()->profilSekolah;
        $namaSekolah = $profil ? preg_replace('/[^a-zA-Z0-9]/', '_', $profil->nama) : 'sekolah';
        return Excel::download(
            new BkuExport($bulan, $namaSekolah),
            'bku-bulan-' . $bulan . '-' . $namaSekolah . '.xlsx'
        );
    }

    public function rekapRekeningExportExcel(Request $request)
    {
        $bulan = (int) $request->get('bulan', date('n'));
        $profil = auth()->user()->profilSekolah;
        $namaSekolah = $profil ? preg_replace('/[^a-zA-Z0-9]/', '_', $profil->nama) : 'sekolah';
        return Excel::download(
            new RekapRekeningExport($bulan),
            'rekap-rekening-bulan-' . $bulan . '-' . $namaSekolah . '.xlsx'
        );
    }

    public function rekapKuartal(Request $request)
    {
        $bulan = (int) $request->get('bulan', date('n'));
        $rawTanggal = $request->get('tanggal_cetak', '');
        $tanggalCetak = $rawTanggal && \Carbon\Carbon::hasFormat($rawTanggal, 'Y-m-d')
            ? \Carbon\Carbon::parse($rawTanggal)->translatedFormat('d F Y')
            : ($rawTanggal ?: \Carbon\Carbon::now()->translatedFormat('d F Y'));
        $kuartal = (int) ceil($bulan / 3);
        $startMonth = ($kuartal - 1) * 3 + 1;
        $bulanMonths = [$startMonth, $startMonth + 1, $startMonth + 2];
        $bulanNames = array_map(
            fn($m) => \Carbon\Carbon::create()->month($m)->translatedFormat('F'),
            $bulanMonths
        );
        $qLabel = 'Q' . $kuartal;
        $periodeLabel = implode(' s.d. ', $bulanNames);

        $tahunAnggaranAktif = TahunAnggaran::where('status', true)->first();
        $profil = auth()->user()->profilSekolah;

        $quarterlyItems = collect();
        if ($tahunAnggaranAktif) {
            $quarterlyItems = RkasItem::with(['kodeRekening.jenisBelanja', 'program', 'bulanRencana', 'transaksiBkus'])
                ->where('tahun_anggaran_id', $tahunAnggaranAktif->id)
                ->get()
                ->map(function ($item) use ($bulanMonths) {
                    $realisasiPerBulan = [];
                    $totalRealisasi = 0;
                    foreach ($bulanMonths as $b) {
                        $r = $item->transaksiBkus->where('jenis', 'pengeluaran')
                            ->where('bulan', $b)->sum('jumlah');
                        $realisasiPerBulan[$b] = $r;
                        $totalRealisasi += $r;
                    }
                    $item->realisasi_per_bulan = $realisasiPerBulan;
                    $item->total_realisasi = $totalRealisasi;
                    return $item;
                });
        }

        $grouped = $quarterlyItems->groupBy(
            fn($item) => $item->kodeRekening?->jenisBelanja?->nama ?? 'Tidak Terkategori'
        );

        if ($request->get('cetak') == 'pdf') {
            $namaSekolah = $profil ? preg_replace('/[^a-zA-Z0-9]/', '_', $profil->nama) : 'sekolah';
            $tahunLabel = $tahunAnggaranAktif?->tahun ?? date('Y');
            $pdf = Pdf::loadView('laporan.rekap-rekening-kuartal', compact(
                'grouped', 'profil', 'tahunAnggaranAktif',
                'qLabel', 'periodeLabel', 'bulanMonths', 'bulanNames', 'tanggalCetak'
            ))->setPaper('a4', 'landscape');

            return $pdf->stream('Rekap_Kuartal-' . $namaSekolah . '-' . $qLabel . '_' . $tahunLabel . '.pdf');
        }

        return view('laporan.rekap-kuartal', compact(
            'grouped', 'profil', 'tahunAnggaranAktif',
            'qLabel', 'periodeLabel', 'bulanMonths', 'bulanNames', 'kuartal', 'bulan', 'tanggalCetak'
        ));
    }

    public function rekapKuartalExportExcel(Request $request)
    {
        $bulan = (int) $request->get('bulan', date('n'));
        $kuartal = (int) ceil($bulan / 3);
        $profil = auth()->user()->profilSekolah;
        $namaSekolah = $profil ? preg_replace('/[^a-zA-Z0-9]/', '_', $profil->nama) : 'sekolah';

        return Excel::download(
            new RekapKuartalExport($kuartal, $namaSekolah),
            'rekap-kuartal-q' . $kuartal . '-' . $namaSekolah . '.xlsx'
        );
    }

    /**
     * Rekap SIPLAH per bulan — web & PDF
     */
    public function rekapSiplah(Request $request)
    {
        $data = $this->prepareRekapSiplahData($request);

        if ($request->get('cetak') == 'pdf') {
            $namaSekolah = $data['profil'] ? preg_replace('/[^a-zA-Z0-9]/', '_', $data['profil']->nama) : 'sekolah';
            $bulanLabel = $data['bulan'] ? \Carbon\Carbon::create()->month($data['bulan'])->translatedFormat('F') : '';
            $tahunLabel = $data['tahunAnggaranAktif']?->tahun ?? date('Y');
            $pdf = Pdf::loadView('laporan.rekap-siplah', $data)->setPaper('a4', 'landscape');
            return $pdf->stream('Rekap_Siplah-' . $namaSekolah . '-' . $bulanLabel . '_' . $tahunLabel . '.pdf');
        }

        return view('laporan.rekap-siplah', $data);
    }

    public function rekapSiplahWeb(Request $request)
    {
        $data = $this->prepareRekapSiplahData($request);
        return view('laporan.rekap-siplah-web', $data);
    }

    public function rekapSiplahExportExcel(Request $request)
    {
        $bulan = (int) $request->get('bulan', date('n'));
        $profil = auth()->user()->profilSekolah;
        $namaSekolah = $profil ? preg_replace('/[^a-zA-Z0-9]/', '_', $profil->nama) : 'sekolah';
        return Excel::download(
            new RekapSiplahExport($bulan),
            'rekap-siplah-bulan-' . $bulan . '-' . $namaSekolah . '.xlsx'
        );
    }

    public function adminRekapSiplah(Request $request, ProfilSekolah $sekolah)
    {
        $data = $this->prepareRekapSiplahData($request, $sekolah);
        $data['adminSekolahId'] = $sekolah->id;
        $data['isAdmin'] = true;

        if ($request->get('cetak') == 'pdf') {
            $namaSekolah = preg_replace('/[^a-zA-Z0-9]/', '_', $sekolah->nama);
            $rawTanggal = $request->get('tanggal_cetak', '');
            $data['tanggalCetak'] = $rawTanggal && \Carbon\Carbon::hasFormat($rawTanggal, 'Y-m-d')
                ? \Carbon\Carbon::parse($rawTanggal)->translatedFormat('d F Y')
                : ($rawTanggal ?: \Carbon\Carbon::now()->translatedFormat('d F Y'));
            $bulanLabel = $data['bulan'] ? \Carbon\Carbon::create()->month($data['bulan'])->translatedFormat('F') : '';
            $tahunLabel = $data['tahunAnggaranAktif']?->tahun ?? date('Y');
            $pdf = Pdf::loadView('laporan.rekap-siplah', $data)->setPaper('a4', 'landscape');
            return $pdf->stream('Rekap_Siplah-' . $namaSekolah . '-' . $bulanLabel . '_' . $tahunLabel . '.pdf');
        }

        return view('laporan.rekap-siplah-web', $data);
    }

    public function adminRekapSiplahExportExcel(Request $request, ProfilSekolah $sekolah)
    {
        $bulan = (int) $request->get('bulan', date('n'));
        $namaSekolah = preg_replace('/[^a-zA-Z0-9]/', '_', $sekolah->nama);
        return Excel::download(
            new RekapSiplahExport($bulan, $sekolah->id),
            'rekap-siplah-bulan-' . $bulan . '-' . $namaSekolah . '.xlsx'
        );
    }

    private function prepareRekapSiplahData(Request $request, ?ProfilSekolah $profilOverride = null): array
    {
        $bulan = (int) $request->get('bulan', date('n'));
        $rawTanggal = $request->get('tanggal_cetak', '');
        $tanggalCetak = $rawTanggal && \Carbon\Carbon::hasFormat($rawTanggal, 'Y-m-d')
            ? \Carbon\Carbon::parse($rawTanggal)->translatedFormat('d F Y')
            : ($rawTanggal ?: \Carbon\Carbon::now()->translatedFormat('d F Y'));
        $tahunAnggaranAktif = TahunAnggaran::where('status', true)->first();
        $profil = $profilOverride ?? auth()->user()->profilSekolah;

        $query = TransaksiBku::with('rkasItem.kodeRekening.jenisBelanja')
            ->where('jenis', 'pengeluaran')
            ->where('bulan', $bulan);

        if ($profilOverride) {
            $query->withoutGlobalScope('sekolah')->where('sekolah_id', $profilOverride->id);
        }

        $transaksis = $query->get();

        $totalPengeluaran = $transaksis->sum('jumlah');
        $totalSiplah = $transaksis->where('metode_pengadaan', 'siplah')->sum('jumlah');
        $totalNonSiplah = $transaksis->where('metode_pengadaan', 'non_siplah')->sum('jumlah');
        $totalBelumDiisi = $totalPengeluaran - $totalSiplah - $totalNonSiplah;
        $persenSiplah = $totalPengeluaran > 0 ? round(($totalSiplah / $totalPengeluaran) * 100, 1) : 0;
        $persenNonSiplah = $totalPengeluaran > 0 ? round(($totalNonSiplah / $totalPengeluaran) * 100, 1) : 0;

        $grouped = $transaksis->groupBy(function ($t) {
            return $t->rkasItem?->kodeRekening?->jenisBelanja?->nama ?? 'Tidak Terkategori';
        });

        $breakdown = $grouped->map(function ($items, $jenisBelanja) {
            $total = $items->sum('jumlah');
            $siplah = $items->where('metode_pengadaan', 'siplah')->sum('jumlah');
            $nonSiplah = $items->where('metode_pengadaan', 'non_siplah')->sum('jumlah');
            return (object) [
                'jenis_belanja' => $jenisBelanja,
                'total' => $total,
                'siplah' => $siplah,
                'non_siplah' => $nonSiplah,
                'persen_siplah' => $total > 0 ? round(($siplah / $total) * 100, 1) : 0,
                'persen_non_siplah' => $total > 0 ? round(($nonSiplah / $total) * 100, 1) : 0,
            ];
        });

        return compact(
            'bulan', 'profil', 'tahunAnggaranAktif', 'tanggalCetak',
            'totalPengeluaran', 'totalSiplah', 'totalNonSiplah', 'totalBelumDiisi',
            'persenSiplah', 'persenNonSiplah', 'breakdown'
        );
    }

    public function index()
    {
        $sekolahs = collect();
        if (auth()->user()->isAdminKecamatan()) {
            $sekolahs = ProfilSekolah::orderBy('nama')->get();
        }
        $tahunAnggaranAktif = TahunAnggaran::where('status', true)->first();
        return view('laporan.index', compact('sekolahs', 'tahunAnggaranAktif'));
    }

    public function bkuWeb(Request $request)
    {
        $data = $this->prepareBkuData($request);
        return view('laporan.bku-web', $data);
    }

    public function rekapRekeningWeb(Request $request)
    {
        $data = $this->prepareRekapRekeningData($request);
        return view('laporan.rekap-rekening-web', $data);
    }

    public function rekapKuartalWeb(Request $request)
    {
        $data = $this->prepareRekapKuartalData($request);
        return view('laporan.rekap-kuartal-web', $data);
    }

    public function adminBku(Request $request, ProfilSekolah $sekolah)
    {
        $data = $this->prepareBkuData($request, $sekolah);
        $data['adminSekolahId'] = $sekolah->id;
        $data['isAdmin'] = true;

        if ($request->get('cetak') == 'pdf') {
            $namaSekolah = preg_replace('/[^a-zA-Z0-9]/', '_', $sekolah->nama);
            $rawTanggal = $request->get('tanggal_cetak', '');
            $data['tanggalCetak'] = $rawTanggal && \Carbon\Carbon::hasFormat($rawTanggal, 'Y-m-d')
                ? \Carbon\Carbon::parse($rawTanggal)->translatedFormat('d F Y')
                : ($rawTanggal ?: \Carbon\Carbon::now()->translatedFormat('d F Y'));
            $bulanLabel = $data['bulan'] ? \Carbon\Carbon::create()->month($data['bulan'])->translatedFormat('F') : '';
            $tahunLabel = $data['tahunAnggaranAktif']?->tahun ?? date('Y');
            $pdf = Pdf::loadView('laporan.bku', $data)->setPaper('a4', 'landscape');
            return $pdf->stream('BKU-' . $namaSekolah . '-' . $bulanLabel . '_' . $tahunLabel . '.pdf');
        }

        return view('laporan.bku-web', $data);
    }

    public function adminRekapRekening(Request $request, ProfilSekolah $sekolah)
    {
        $data = $this->prepareRekapRekeningData($request, $sekolah);
        $data['adminSekolahId'] = $sekolah->id;
        $data['isAdmin'] = true;

        if ($request->get('cetak') == 'pdf') {
            $namaSekolah = preg_replace('/[^a-zA-Z0-9]/', '_', $sekolah->nama);
            $rawTanggal = $request->get('tanggal_cetak', '');
            $data['tanggalCetak'] = $rawTanggal && \Carbon\Carbon::hasFormat($rawTanggal, 'Y-m-d')
                ? \Carbon\Carbon::parse($rawTanggal)->translatedFormat('d F Y')
                : ($rawTanggal ?: \Carbon\Carbon::now()->translatedFormat('d F Y'));
            $bulanLabel = $data['bulan'] ? \Carbon\Carbon::create()->month($data['bulan'])->translatedFormat('F') : '';
            $tahunLabel = $data['tahunAnggaranAktif']?->tahun ?? date('Y');
            $pdf = Pdf::loadView('laporan.rekap-rekening', $data)->setPaper('a4', 'landscape');
            return $pdf->stream('Rekap_Rekening-' . $namaSekolah . '-' . $bulanLabel . '_' . $tahunLabel . '.pdf');
        }

        return view('laporan.rekap-rekening-web', $data);
    }

    public function adminRekapKuartal(Request $request, ProfilSekolah $sekolah)
    {
        $data = $this->prepareRekapKuartalData($request, $sekolah);
        $data['adminSekolahId'] = $sekolah->id;
        $data['isAdmin'] = true;

        if ($request->get('cetak') == 'pdf') {
            $namaSekolah = preg_replace('/[^a-zA-Z0-9]/', '_', $sekolah->nama);
            $rawTanggal = $request->get('tanggal_cetak', '');
            $data['tanggalCetak'] = $rawTanggal && \Carbon\Carbon::hasFormat($rawTanggal, 'Y-m-d')
                ? \Carbon\Carbon::parse($rawTanggal)->translatedFormat('d F Y')
                : ($rawTanggal ?: \Carbon\Carbon::now()->translatedFormat('d F Y'));
            $tahunLabel = $data['tahunAnggaranAktif']?->tahun ?? date('Y');
            $pdf = Pdf::loadView('laporan.rekap-rekening-kuartal', $data)->setPaper('a4', 'landscape');
            return $pdf->stream('Rekap_Kuartal-' . $namaSekolah . '-' . $data['qLabel'] . '_' . $tahunLabel . '.pdf');
        }

        return view('laporan.rekap-kuartal-web', $data);
    }

    public function adminBkuExportExcel(Request $request, ProfilSekolah $sekolah)
    {
        $bulan = (int) $request->get('bulan', date('n'));
        $namaSekolah = preg_replace('/[^a-zA-Z0-9]/', '_', $sekolah->nama);
        return Excel::download(
            new BkuExport($bulan, $namaSekolah, $sekolah->id),
            'bku-bulan-' . $bulan . '-' . $namaSekolah . '.xlsx'
        );
    }

    public function adminRekapRekeningExportExcel(Request $request, ProfilSekolah $sekolah)
    {
        $bulan = (int) $request->get('bulan', date('n'));
        $namaSekolah = preg_replace('/[^a-zA-Z0-9]/', '_', $sekolah->nama);
        return Excel::download(
            new RekapRekeningExport($bulan, $sekolah->id),
            'rekap-rekening-bulan-' . $bulan . '-' . $namaSekolah . '.xlsx'
        );
    }

    public function adminRekapKuartalExportExcel(Request $request, ProfilSekolah $sekolah)
    {
        $bulan = (int) $request->get('bulan', date('n'));
        $kuartal = (int) ceil($bulan / 3);
        $namaSekolah = preg_replace('/[^a-zA-Z0-9]/', '_', $sekolah->nama);
        return Excel::download(
            new RekapKuartalExport($kuartal, $namaSekolah, $sekolah->id),
            'rekap-kuartal-q' . $kuartal . '-' . $namaSekolah . '.xlsx'
        );
    }

    private function prepareBkuData(Request $request, ?ProfilSekolah $profilOverride = null): array
    {
        $bulan = (int) $request->get('bulan', date('n'));
        $tahunAnggaranAktif = TahunAnggaran::where('status', true)->first();
        $profil = $profilOverride ?? auth()->user()->profilSekolah;

        $query = TransaksiBku::with('rkasItem.program', 'rkasItem.kodeRekening.jenisBelanja')
                             ->where('bulan', $bulan)
                             ->orderBy('tanggal')
                             ->orderBy('id');

        if ($profilOverride) {
            $query->withoutGlobalScope('sekolah')->where('sekolah_id', $profilOverride->id);
        }

        $transaksis = $query->get();

        $baseQuery = TransaksiBku::where('bulan', '<', $bulan);
        if ($profilOverride) {
            $baseQuery->withoutGlobalScope('sekolah')->where('sekolah_id', $profilOverride->id);
        }
        $saldoAwal = $baseQuery->get()
            ->reduce(fn($carry, $t) =>
                $carry + (strtolower($t->jenis) == 'penerimaan' ? $t->jumlah : -$t->jumlah), 0);

        $saldo = $saldoAwal;
        foreach ($transaksis as $t) {
            $saldo += strtolower($t->jenis) == 'penerimaan' ? $t->jumlah : -$t->jumlah;
            $t->saldo_berjalan = $saldo;
        }

        $totalPenerimaan  = $transaksis->where('jenis', 'penerimaan')->sum('jumlah');
        $totalPengeluaran = $transaksis->where('jenis', 'pengeluaran')->sum('jumlah');
        $saldoAkhir       = $saldoAwal + $totalPenerimaan - $totalPengeluaran;

        return compact('transaksis', 'profil', 'bulan', 'tahunAnggaranAktif',
            'saldoAwal', 'totalPenerimaan', 'totalPengeluaran', 'saldoAkhir');
    }

    private function prepareRekapRekeningData(Request $request, ?ProfilSekolah $profilOverride = null): array
    {
        $bulan = (int) $request->get('bulan', date('n'));
        $tahunAnggaranAktif = TahunAnggaran::where('status', true)->first();
        $profil = $profilOverride ?? auth()->user()->profilSekolah;

        $rkasItems = collect();
        if ($tahunAnggaranAktif) {
            $query = RkasItem::with(['kodeRekening.jenisBelanja', 'program', 'bulanRencana', 'transaksiBkus'])
                             ->where('tahun_anggaran_id', $tahunAnggaranAktif->id);

            if ($profilOverride) {
                $query->withoutGlobalScope('sekolah')->where('sekolah_id', $profilOverride->id);
            }

            $rkasItems = $query->get()->map(function ($item) use ($bulan) {
                $rencana    = $item->bulanRencana->where('bulan', $bulan)->sum('rencana');
                $realisasi  = $item->transaksiBkus->where('jenis', 'pengeluaran')
                                                   ->where('bulan', $bulan)->sum('jumlah');
                $item->rencana_bulan   = $rencana;
                $item->realisasi_bulan = $realisasi;
                $item->sisa_bulan      = $rencana - $realisasi;
                $item->persen          = $rencana > 0 ? round(($realisasi / $rencana) * 100, 1) : 0;
                return $item;
            });
        }

        $grouped = $rkasItems->groupBy(fn($item) => $item->kodeRekening?->jenisBelanja?->nama ?? 'Tidak Terkategori');

        return compact('grouped', 'profil', 'bulan', 'tahunAnggaranAktif', 'rkasItems');
    }

    private function prepareRekapKuartalData(Request $request, ?ProfilSekolah $profilOverride = null): array
    {
        $bulan = (int) $request->get('bulan', date('n'));
        $kuartal = (int) ceil($bulan / 3);
        $startMonth = ($kuartal - 1) * 3 + 1;
        $bulanMonths = [$startMonth, $startMonth + 1, $startMonth + 2];
        $bulanNames = array_map(
            fn($m) => \Carbon\Carbon::create()->month($m)->translatedFormat('F'),
            $bulanMonths
        );
        $qLabel = 'Q' . $kuartal;
        $periodeLabel = implode(' s.d. ', $bulanNames);

        $tahunAnggaranAktif = TahunAnggaran::where('status', true)->first();
        $profil = $profilOverride ?? auth()->user()->profilSekolah;

        $quarterlyItems = collect();
        if ($tahunAnggaranAktif) {
            $query = RkasItem::with(['kodeRekening.jenisBelanja', 'program', 'bulanRencana', 'transaksiBkus'])
                             ->where('tahun_anggaran_id', $tahunAnggaranAktif->id);

            if ($profilOverride) {
                $query->withoutGlobalScope('sekolah')->where('sekolah_id', $profilOverride->id);
            }

            $quarterlyItems = $query->get()->map(function ($item) use ($bulanMonths) {
                $realisasiPerBulan = [];
                $totalRealisasi = 0;
                foreach ($bulanMonths as $b) {
                    $r = $item->transaksiBkus->where('jenis', 'pengeluaran')
                        ->where('bulan', $b)->sum('jumlah');
                    $realisasiPerBulan[$b] = $r;
                    $totalRealisasi += $r;
                }
                $item->realisasi_per_bulan = $realisasiPerBulan;
                $item->total_realisasi = $totalRealisasi;
                return $item;
            });
        }

        $grouped = $quarterlyItems->groupBy(
            fn($item) => $item->kodeRekening?->jenisBelanja?->nama ?? 'Tidak Terkategori'
        );

        return compact('grouped', 'profil', 'tahunAnggaranAktif',
            'qLabel', 'periodeLabel', 'bulanMonths', 'bulanNames', 'kuartal', 'bulan');
    }
}
