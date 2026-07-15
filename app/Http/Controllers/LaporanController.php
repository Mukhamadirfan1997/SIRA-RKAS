<?php

namespace App\Http\Controllers;

use App\Exports\BkuExport;
use App\Exports\RekapRekeningExport;
use App\Exports\RekapKuartalExport;
use App\Exports\RekapSiplahExport;
use App\Models\ProfilSekolah;
use App\Models\SumberDana;
use App\Models\TransaksiBku;
use App\Models\RkasItem;
use App\Models\RkasItemBulan;
use App\Models\TahunAnggaran;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;

class LaporanController extends Controller
{
    public function bku(Request $request)
    {
        $bulan = (int) $request->get('bulan', date('n'));
        $rawTanggal = $request->get('tanggal_cetak', '');
        $tanggalCetak = $rawTanggal && \Carbon\Carbon::hasFormat($rawTanggal, 'Y-m-d')
            ? \Carbon\Carbon::parse($rawTanggal)->translatedFormat('d F Y')
            : ($rawTanggal ?: \Carbon\Carbon::now()->translatedFormat('d F Y'));
        $tahunAnggaranAktif = TahunAnggaran::where('status', true)->first();
        $tahunInput = $request->get('tahun');
        if ($tahunInput) {
            $tahunRecord = TahunAnggaran::where('tahun', $tahunInput)->first();
            if ($tahunRecord) {
                $tahunAnggaranAktif = $tahunRecord;
            }
        }
        $sumberDanaId = $request->input('sumber_dana_id');
        $profil = auth()->user()->profilSekolah;

        $transaksis = TransaksiBku::with('rkasItem.program', 'rkasItem.kodeRekening.jenisBelanja')
            ->where('tahun_anggaran_id', $tahunAnggaranAktif?->id)
            ->where('bulan', $bulan)
            ->when($sumberDanaId, fn($q) => $q->where('sumber_dana_id', $sumberDanaId))
            ->orderBy('tanggal')
            ->orderBy('id')
            ->get();

        $saldoAwal = (float) TransaksiBku::where('tahun_anggaran_id', $tahunAnggaranAktif?->id)
            ->where('bulan', '<', $bulan)
            ->when($sumberDanaId, fn($q) => $q->where('sumber_dana_id', $sumberDanaId))
            ->selectRaw("SUM(CASE WHEN LOWER(jenis) = 'penerimaan' THEN jumlah ELSE -jumlah END) as saldo")
            ->value('saldo') ?? 0;

        $saldo = $saldoAwal;
        foreach ($transaksis as $t) {
            $saldo += strtolower($t->jenis) == 'penerimaan' ? $t->jumlah : -$t->jumlah;
            $t->saldo_berjalan = $saldo;
        }

        $totalPenerimaan = (float) TransaksiBku::where('tahun_anggaran_id', $tahunAnggaranAktif?->id)
            ->where('bulan', $bulan)
            ->when($sumberDanaId, fn($q) => $q->where('sumber_dana_id', $sumberDanaId))
            ->where('jenis', 'penerimaan')->sum('jumlah');
        $totalPengeluaran = (float) TransaksiBku::where('tahun_anggaran_id', $tahunAnggaranAktif?->id)
            ->where('bulan', $bulan)
            ->when($sumberDanaId, fn($q) => $q->where('sumber_dana_id', $sumberDanaId))
            ->where('jenis', 'pengeluaran')->sum('jumlah');
        $saldoAkhir = $saldoAwal + $totalPenerimaan - $totalPengeluaran;

        if ($request->get('cetak') == 'pdf') {
            $namaSekolah = $profil ? preg_replace('/[^a-zA-Z0-9]/', '_', $profil->nama) : 'sekolah';
            $bulanLabel = $bulan ? \Carbon\Carbon::create()->month($bulan)->translatedFormat('F') : '';
            $tahunLabel = $tahunAnggaranAktif?->tahun ?? date('Y');
            $pdf = Pdf::loadView('laporan.bku', compact(
                'transaksis', 'profil', 'bulan', 'tahunAnggaranAktif',
                'saldoAwal', 'totalPenerimaan', 'totalPengeluaran', 'saldoAkhir', 'tanggalCetak', 'sumberDanaId'
            ))->setPaper('a4', 'landscape');

            return $pdf->stream('BKU-' . $namaSekolah . '-' . $bulanLabel . '_' . $tahunLabel . '.pdf');
        }

        return view('laporan.bku', compact(
            'transaksis', 'profil', 'bulan', 'tahunAnggaranAktif',
            'saldoAwal', 'totalPenerimaan', 'totalPengeluaran', 'saldoAkhir', 'tanggalCetak', 'sumberDanaId'
        ));
    }

    public function rekapRekening(Request $request)
    {
        $bulan = (int) $request->get('bulan', date('n'));
        $rawTanggal = $request->get('tanggal_cetak', '');
        $tanggalCetak = $rawTanggal && \Carbon\Carbon::hasFormat($rawTanggal, 'Y-m-d')
            ? \Carbon\Carbon::parse($rawTanggal)->translatedFormat('d F Y')
            : ($rawTanggal ?: \Carbon\Carbon::now()->translatedFormat('d F Y'));
        $tahunAnggaranAktif = TahunAnggaran::where('status', true)->first();
        $tahunInput = $request->get('tahun');
        if ($tahunInput) {
            $tahunRecord = TahunAnggaran::where('tahun', $tahunInput)->first();
            if ($tahunRecord) {
                $tahunAnggaranAktif = $tahunRecord;
            }
        }
        $sumberDanaId = $request->input('sumber_dana_id');
        $profil = auth()->user()->profilSekolah;

        $rkasItems = $this->loadRekapRekeningItems($tahunAnggaranAktif, $bulan);
        $grouped = $rkasItems->groupBy(fn($item) => $item->kodeRekening?->jenisBelanja?->nama ?? 'Tidak Terkategori');

        if ($request->get('cetak') == 'pdf') {
            $namaSekolah = $profil ? preg_replace('/[^a-zA-Z0-9]/', '_', $profil->nama) : 'sekolah';
            $bulanLabel = $bulan ? \Carbon\Carbon::create()->month($bulan)->translatedFormat('F') : '';
            $tahunLabel = $tahunAnggaranAktif?->tahun ?? date('Y');
            $pdf = Pdf::loadView('laporan.rekap-rekening', compact(
                'grouped', 'profil', 'bulan', 'tahunAnggaranAktif', 'rkasItems', 'tanggalCetak', 'sumberDanaId'
            ))->setPaper('a4', 'landscape');

            return $pdf->stream('Rekap_Rekening-' . $namaSekolah . '-' . $bulanLabel . '_' . $tahunLabel . '.pdf');
        }

        return view('laporan.rekap-rekening', compact(
            'grouped', 'profil', 'bulan', 'tahunAnggaranAktif', 'rkasItems', 'tanggalCetak', 'sumberDanaId'
        ));
    }

    public function bkuExportExcel(Request $request)
    {
        $bulan = (int) $request->get('bulan', date('n'));
        $profil = auth()->user()->profilSekolah;
        $namaSekolah = $profil ? preg_replace('/[^a-zA-Z0-9]/', '_', $profil->nama) : 'sekolah';
        $tahunAnggaranAktif = TahunAnggaran::where('status', true)->first();
        $tahunInput = $request->get('tahun');
        if ($tahunInput) {
            $tahunRecord = TahunAnggaran::where('tahun', $tahunInput)->first();
            if ($tahunRecord) {
                $tahunAnggaranAktif = $tahunRecord;
            }
        }
        $sumberDanaId = $request->input('sumber_dana_id');
        return Excel::download(
            new BkuExport($bulan, $namaSekolah, null, $tahunAnggaranAktif?->id, $sumberDanaId),
            'bku-bulan-' . $bulan . '-' . $namaSekolah . '.xlsx'
        );
    }

    public function rekapRekeningExportExcel(Request $request)
    {
        $bulan = (int) $request->get('bulan', date('n'));
        $profil = auth()->user()->profilSekolah;
        $namaSekolah = $profil ? preg_replace('/[^a-zA-Z0-9]/', '_', $profil->nama) : 'sekolah';
        $tahunAnggaranAktif = TahunAnggaran::where('status', true)->first();
        $tahunInput = $request->get('tahun');
        if ($tahunInput) {
            $tahunRecord = TahunAnggaran::where('tahun', $tahunInput)->first();
            if ($tahunRecord) {
                $tahunAnggaranAktif = $tahunRecord;
            }
        }
        $sumberDanaId = $request->input('sumber_dana_id');
        return Excel::download(
            new RekapRekeningExport($bulan, null, $tahunAnggaranAktif?->id, $sumberDanaId),
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
        $tahunInput = $request->get('tahun');
        if ($tahunInput) {
            $tahunRecord = TahunAnggaran::where('tahun', $tahunInput)->first();
            if ($tahunRecord) {
                $tahunAnggaranAktif = $tahunRecord;
            }
        }
        $sumberDanaId = $request->input('sumber_dana_id');
        $profil = auth()->user()->profilSekolah;

        $quarterlyItems = $this->loadKuartalItems($tahunAnggaranAktif, $bulanMonths);
        $grouped = $quarterlyItems->groupBy(
            fn($item) => $item->kodeRekening?->jenisBelanja?->nama ?? 'Tidak Terkategori'
        );

        if ($request->get('cetak') == 'pdf') {
            $namaSekolah = $profil ? preg_replace('/[^a-zA-Z0-9]/', '_', $profil->nama) : 'sekolah';
            $tahunLabel = $tahunAnggaranAktif?->tahun ?? date('Y');
            $pdf = Pdf::loadView('laporan.rekap-rekening-kuartal', compact(
                'grouped', 'profil', 'tahunAnggaranAktif',
                'qLabel', 'periodeLabel', 'bulanMonths', 'bulanNames', 'tanggalCetak', 'sumberDanaId'
            ))->setPaper('a4', 'landscape');

            return $pdf->stream('Rekap_Kuartal-' . $namaSekolah . '-' . $qLabel . '_' . $tahunLabel . '.pdf');
        }

        return view('laporan.rekap-kuartal', compact(
            'grouped', 'profil', 'tahunAnggaranAktif',
            'qLabel', 'periodeLabel', 'bulanMonths', 'bulanNames', 'kuartal', 'bulan', 'tanggalCetak', 'sumberDanaId'
        ));
    }

    public function rekapKuartalExportExcel(Request $request)
    {
        $bulan = (int) $request->get('bulan', date('n'));
        $kuartal = (int) ceil($bulan / 3);
        $profil = auth()->user()->profilSekolah;
        $namaSekolah = $profil ? preg_replace('/[^a-zA-Z0-9]/', '_', $profil->nama) : 'sekolah';
        $tahunAnggaranAktif = TahunAnggaran::where('status', true)->first();
        $tahunInput = $request->get('tahun');
        if ($tahunInput) {
            $tahunRecord = TahunAnggaran::where('tahun', $tahunInput)->first();
            if ($tahunRecord) {
                $tahunAnggaranAktif = $tahunRecord;
            }
        }
        $sumberDanaId = $request->input('sumber_dana_id');

        return Excel::download(
            new RekapKuartalExport($kuartal, $namaSekolah, null, $tahunAnggaranAktif?->id, $sumberDanaId),
            'rekap-kuartal-q' . $kuartal . '-' . $namaSekolah . '.xlsx'
        );
    }

    public function rekapSiplah(Request $request)
    {
        $data = $this->prepareRekapSiplahData($request);

        if ($request->get('cetak') == 'pdf') {
            $namaSekolah = $data['profil'] ? preg_replace('/[^a-zA-Z0-9]/', '_', $data['profil']->nama) : 'sekolah';
            $slug = str_replace([' ', '–'], ['_', '-'], $data['periodeLabel']);
            $tahunLabel = $data['tahunAnggaranAktif']?->tahun ?? date('Y');
            $pdf = Pdf::loadView('laporan.rekap-siplah', $data)->setPaper('a4', 'landscape');
            return $pdf->stream('Rekap_Siplah-' . $namaSekolah . '-' . $slug . '_' . $tahunLabel . '.pdf');
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
        $resolved = $this->resolveSiplahPeriode($request);
        $profil = auth()->user()->profilSekolah;
        $namaSekolah = $profil ? preg_replace('/[^a-zA-Z0-9]/', '_', $profil->nama) : 'sekolah';
        $slug = str_replace([' ', '–'], ['_', '-'], $resolved['label']);
        $tahunAnggaranAktif = TahunAnggaran::where('status', true)->first();
        $tahunInput = $request->get('tahun');
        if ($tahunInput) {
            $tahunRecord = TahunAnggaran::where('tahun', $tahunInput)->first();
            if ($tahunRecord) {
                $tahunAnggaranAktif = $tahunRecord;
            }
        }
        $sumberDanaId = $request->input('sumber_dana_id');
        return Excel::download(
            new RekapSiplahExport($resolved['months'], null, $resolved['label'], $tahunAnggaranAktif?->id, $sumberDanaId),
            'rekap-siplah-' . $slug . '-' . $namaSekolah . '.xlsx'
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
            $slug = str_replace([' ', '–'], ['_', '-'], $data['periodeLabel']);
            $tahunLabel = $data['tahunAnggaranAktif']?->tahun ?? date('Y');
            $pdf = Pdf::loadView('laporan.rekap-siplah', $data)->setPaper('a4', 'landscape');
            return $pdf->stream('Rekap_Siplah-' . $namaSekolah . '-' . $slug . '_' . $tahunLabel . '.pdf');
        }

        return view('laporan.rekap-siplah-web', $data);
    }

    public function adminRekapSiplahExportExcel(Request $request, ProfilSekolah $sekolah)
    {
        $resolved = $this->resolveSiplahPeriode($request);
        $namaSekolah = preg_replace('/[^a-zA-Z0-9]/', '_', $sekolah->nama);
        $slug = str_replace([' ', '–'], ['_', '-'], $resolved['label']);
        $tahunAnggaranAktif = TahunAnggaran::where('status', true)->first();
        $tahunInput = $request->get('tahun');
        if ($tahunInput) {
            $tahunRecord = TahunAnggaran::where('tahun', $tahunInput)->first();
            if ($tahunRecord) {
                $tahunAnggaranAktif = $tahunRecord;
            }
        }
        $sumberDanaId = $request->input('sumber_dana_id');
        return Excel::download(
            new RekapSiplahExport($resolved['months'], $sekolah->id, $resolved['label'], $tahunAnggaranAktif?->id, $sumberDanaId),
            'rekap-siplah-' . $slug . '-' . $namaSekolah . '.xlsx'
        );
    }

    private function resolveSiplahPeriode(Request $request): array
    {
        $periode = $request->get('periode', '');
        $bulanParam = (int) $request->get('bulan', 0);

        if ($periode === 'h1') {
            return ['months' => [1, 2, 3, 4, 5, 6], 'label' => 'Januari – Juni'];
        } elseif ($periode === 'h2') {
            return ['months' => [7, 8, 9, 10, 11, 12], 'label' => 'Juli – Desember'];
        } elseif ($periode === 'all') {
            return ['months' => range(1, 12), 'label' => 'Seluruh Tahun'];
        } elseif ($bulanParam >= 1 && $bulanParam <= 12) {
            $label = \Carbon\Carbon::create()->month($bulanParam)->translatedFormat('F');
            return ['months' => [$bulanParam], 'label' => $label];
        }
        $currentMonth = (int) date('n');
        return ['months' => [$currentMonth], 'label' => \Carbon\Carbon::create()->month($currentMonth)->translatedFormat('F')];
    }

    private function prepareRekapSiplahData(Request $request, ?ProfilSekolah $profilOverride = null): array
    {
        $resolved = $this->resolveSiplahPeriode($request);
        $months = $resolved['months'];
        $periodeLabel = $resolved['label'];

        $bulan = $months[0];
        $rawTanggal = $request->get('tanggal_cetak', '');
        $tanggalCetak = $rawTanggal && \Carbon\Carbon::hasFormat($rawTanggal, 'Y-m-d')
            ? \Carbon\Carbon::parse($rawTanggal)->translatedFormat('d F Y')
            : ($rawTanggal ?: \Carbon\Carbon::now()->translatedFormat('d F Y'));
        $tahunAnggaranAktif = TahunAnggaran::where('status', true)->first();
        $tahunInput = $request->get('tahun');
        if ($tahunInput) {
            $tahunRecord = TahunAnggaran::where('tahun', $tahunInput)->first();
            if ($tahunRecord) {
                $tahunAnggaranAktif = $tahunRecord;
            }
        }
        $tahunList = TahunAnggaran::orderBy('tahun', 'desc')->get();
        $sumberDanaList = SumberDana::orderBy('kode')->get();
        $sumberDanaId = $request->input('sumber_dana_id');
        $profil = $profilOverride ?? auth()->user()->profilSekolah;

        $totalsQuery = TransaksiBku::where('tahun_anggaran_id', $tahunAnggaranAktif?->id)
            ->where('jenis', 'pengeluaran')
            ->whereIn('bulan', $months)
            ->when($sumberDanaId, fn($q) => $q->where('sumber_dana_id', $sumberDanaId));

        if ($profilOverride) {
            $totalsQuery->withoutGlobalScope('sekolah')->where('sekolah_id', $profilOverride->id);
        }

        $totals = (clone $totalsQuery)->selectRaw("
            COALESCE(SUM(jumlah), 0) as total,
            COALESCE(SUM(CASE WHEN metode_pengadaan = 'siplah' THEN jumlah ELSE 0 END), 0) as siplah,
            COALESCE(SUM(CASE WHEN metode_pengadaan = 'non_siplah' THEN jumlah ELSE 0 END), 0) as non_siplah
        ")->first();

        $totalPengeluaran = (float) $totals->total;
        $totalSiplah = (float) $totals->siplah;
        $totalNonSiplah = (float) $totals->non_siplah;
        $totalBelumDiisi = $totalPengeluaran - $totalSiplah - $totalNonSiplah;
        $persenSiplah = $totalPengeluaran > 0 ? round(($totalSiplah / $totalPengeluaran) * 100, 1) : 0;
        $persenNonSiplah = $totalPengeluaran > 0 ? round(($totalNonSiplah / $totalPengeluaran) * 100, 1) : 0;

        $breakdownRows = \DB::table('transaksi_bku as tb')
            ->leftJoin('rkas_item as ri', 'ri.id', '=', 'tb.rkas_item_id')
            ->leftJoin('master_kode_rekening as mkr', 'mkr.id', '=', 'ri.kode_rekening_id')
            ->leftJoin('jenis_belanja as jb', 'jb.id', '=', 'mkr.jenis_belanja_id')
            ->where('tb.jenis', 'pengeluaran')
            ->whereIn('tb.bulan', $months)
            ->when($sumberDanaId, fn($q) => $q->where('tb.sumber_dana_id', $sumberDanaId));

        if ($tahunAnggaranAktif) {
            $breakdownRows->where('tb.tahun_anggaran_id', $tahunAnggaranAktif->id);
        }

        if ($profilOverride) {
            $breakdownRows->where('tb.sekolah_id', $profilOverride->id);
        }

        $breakdownRows = $breakdownRows
            ->selectRaw("
                COALESCE(jb.nama, 'Tidak Terkategori') as jenis_belanja,
                COALESCE(SUM(tb.jumlah), 0) as total,
                COALESCE(SUM(CASE WHEN tb.metode_pengadaan = 'siplah' THEN tb.jumlah ELSE 0 END), 0) as siplah,
                COALESCE(SUM(CASE WHEN tb.metode_pengadaan = 'non_siplah' THEN tb.jumlah ELSE 0 END), 0) as non_siplah
            ")
            ->groupBy('jb.nama')
            ->orderBy('jb.nama')
            ->get();

        $breakdown = $breakdownRows->map(function ($row) {
            $total = (float) $row->total;
            $siplah = (float) $row->siplah;
            $non_siplah = (float) $row->non_siplah;
            return (object) [
                'jenis_belanja' => $row->jenis_belanja,
                'total' => $total,
                'siplah' => $siplah,
                'non_siplah' => $non_siplah,
                'persen_siplah' => $total > 0 ? round(($siplah / $total) * 100, 1) : 0,
                'persen_non_siplah' => $total > 0 ? round(($non_siplah / $total) * 100, 1) : 0,
            ];
        });

        return compact(
            'bulan', 'profil', 'tahunAnggaranAktif', 'tanggalCetak',
            'totalPengeluaran', 'totalSiplah', 'totalNonSiplah', 'totalBelumDiisi',
            'persenSiplah', 'persenNonSiplah', 'breakdown', 'periodeLabel', 'months',
            'tahunList', 'sumberDanaList', 'sumberDanaId'
        );
    }

    public function index()
    {
        $sekolahs = collect();
        if (auth()->user()->isAdminKecamatan()) {
            $sekolahs = ProfilSekolah::orderBy('nama')->get();
        }
        $tahunAnggaranAktif = TahunAnggaran::where('status', true)->first();
        $sumberDanas = SumberDana::orderBy('kode')->get();
        return view('laporan.index', compact('sekolahs', 'tahunAnggaranAktif', 'sumberDanas'));
    }

    public function bkuWeb(Request $request)
    {
        $data = $this->prepareBkuData($request);
        return view('laporan.bku-web', $data);
    }

    public function rekapRekeningWeb(Request $request)
    {
        $data = $this->prepareRekapRekeningData($request, null, 50);
        return view('laporan.rekap-rekening-web', $data);
    }

    public function rekapKuartalWeb(Request $request)
    {
        $data = $this->prepareRekapKuartalData($request, null, 50);
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
        $isPdf = $request->get('cetak') == 'pdf';
        $data = $this->prepareRekapRekeningData($request, $sekolah, $isPdf ? null : 50);
        $data['adminSekolahId'] = $sekolah->id;
        $data['isAdmin'] = true;

        if ($isPdf) {
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
        $isPdf = $request->get('cetak') == 'pdf';
        $data = $this->prepareRekapKuartalData($request, $sekolah, $isPdf ? null : 50);
        $data['adminSekolahId'] = $sekolah->id;
        $data['isAdmin'] = true;

        if ($isPdf) {
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
        $tahunAnggaranAktif = TahunAnggaran::where('status', true)->first();
        $tahunInput = $request->get('tahun');
        if ($tahunInput) {
            $tahunRecord = TahunAnggaran::where('tahun', $tahunInput)->first();
            if ($tahunRecord) {
                $tahunAnggaranAktif = $tahunRecord;
            }
        }
        $sumberDanaId = $request->input('sumber_dana_id');
        return Excel::download(
            new BkuExport($bulan, $namaSekolah, $sekolah->id, $tahunAnggaranAktif?->id, $sumberDanaId),
            'bku-bulan-' . $bulan . '-' . $namaSekolah . '.xlsx'
        );
    }

    public function adminRekapRekeningExportExcel(Request $request, ProfilSekolah $sekolah)
    {
        $bulan = (int) $request->get('bulan', date('n'));
        $namaSekolah = preg_replace('/[^a-zA-Z0-9]/', '_', $sekolah->nama);
        $tahunAnggaranAktif = TahunAnggaran::where('status', true)->first();
        $tahunInput = $request->get('tahun');
        if ($tahunInput) {
            $tahunRecord = TahunAnggaran::where('tahun', $tahunInput)->first();
            if ($tahunRecord) {
                $tahunAnggaranAktif = $tahunRecord;
            }
        }
        $sumberDanaId = $request->input('sumber_dana_id');
        return Excel::download(
            new RekapRekeningExport($bulan, $sekolah->id, $tahunAnggaranAktif?->id, $sumberDanaId),
            'rekap-rekening-bulan-' . $bulan . '-' . $namaSekolah . '.xlsx'
        );
    }

    public function adminRekapKuartalExportExcel(Request $request, ProfilSekolah $sekolah)
    {
        $bulan = (int) $request->get('bulan', date('n'));
        $kuartal = (int) ceil($bulan / 3);
        $namaSekolah = preg_replace('/[^a-zA-Z0-9]/', '_', $sekolah->nama);
        $tahunAnggaranAktif = TahunAnggaran::where('status', true)->first();
        $tahunInput = $request->get('tahun');
        if ($tahunInput) {
            $tahunRecord = TahunAnggaran::where('tahun', $tahunInput)->first();
            if ($tahunRecord) {
                $tahunAnggaranAktif = $tahunRecord;
            }
        }
        $sumberDanaId = $request->input('sumber_dana_id');
        return Excel::download(
            new RekapKuartalExport($kuartal, $namaSekolah, $sekolah->id, $tahunAnggaranAktif?->id, $sumberDanaId),
            'rekap-kuartal-q' . $kuartal . '-' . $namaSekolah . '.xlsx'
        );
    }

    private function prepareBkuData(Request $request, ?ProfilSekolah $profilOverride = null): array
    {
        $bulan = (int) $request->get('bulan', date('n'));
        $tahunAnggaranAktif = TahunAnggaran::where('status', true)->first();
        $tahunInput = $request->get('tahun');
        if ($tahunInput) {
            $tahunRecord = TahunAnggaran::where('tahun', $tahunInput)->first();
            if ($tahunRecord) {
                $tahunAnggaranAktif = $tahunRecord;
            }
        }
        $tahunList = TahunAnggaran::orderBy('tahun', 'desc')->get();
        $sumberDanaList = SumberDana::orderBy('kode')->get();
        $sumberDanaId = $request->input('sumber_dana_id');
        $profil = $profilOverride ?? auth()->user()->profilSekolah;

        $query = TransaksiBku::with('rkasItem.program', 'rkasItem.kodeRekening.jenisBelanja')
            ->where('tahun_anggaran_id', $tahunAnggaranAktif?->id)
            ->where('bulan', $bulan)
            ->when($sumberDanaId, fn($q) => $q->where('sumber_dana_id', $sumberDanaId))
            ->orderBy('tanggal')
            ->orderBy('id');

        if ($profilOverride) {
            $query->withoutGlobalScope('sekolah')->where('sekolah_id', $profilOverride->id);
        }

        $transaksis = $query->get();

        $baseQuery = TransaksiBku::where('tahun_anggaran_id', $tahunAnggaranAktif?->id)
            ->where('bulan', '<', $bulan)
            ->when($sumberDanaId, fn($q) => $q->where('sumber_dana_id', $sumberDanaId));
        if ($profilOverride) {
            $baseQuery->withoutGlobalScope('sekolah')->where('sekolah_id', $profilOverride->id);
        }
        $saldoAwal = (float) $baseQuery
            ->selectRaw("SUM(CASE WHEN LOWER(jenis) = 'penerimaan' THEN jumlah ELSE -jumlah END) as saldo")
            ->value('saldo') ?? 0;

        $saldo = $saldoAwal;
        foreach ($transaksis as $t) {
            $saldo += strtolower($t->jenis) == 'penerimaan' ? $t->jumlah : -$t->jumlah;
            $t->saldo_berjalan = $saldo;
        }

        $totalPenerimaan = (float) TransaksiBku::where('tahun_anggaran_id', $tahunAnggaranAktif?->id)
            ->where('bulan', $bulan)
            ->when($sumberDanaId, fn($q) => $q->where('sumber_dana_id', $sumberDanaId))
            ->where('jenis', 'penerimaan')->sum('jumlah');
        $totalPengeluaran = (float) TransaksiBku::where('tahun_anggaran_id', $tahunAnggaranAktif?->id)
            ->where('bulan', $bulan)
            ->when($sumberDanaId, fn($q) => $q->where('sumber_dana_id', $sumberDanaId))
            ->where('jenis', 'pengeluaran')->sum('jumlah');
        $saldoAkhir = $saldoAwal + $totalPenerimaan - $totalPengeluaran;

        return compact('transaksis', 'profil', 'bulan', 'tahunAnggaranAktif',
            'saldoAwal', 'totalPenerimaan', 'totalPengeluaran', 'saldoAkhir', 'tahunList',
            'sumberDanaList', 'sumberDanaId');
    }

    private function loadRekapRekeningItems(?TahunAnggaran $tahunAnggaranAktif, int $bulan, ?int $sekolahId = null, ?int $perPage = null): \Illuminate\Support\Collection|\Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $query = RkasItem::with('kodeRekening.jenisBelanja', 'program')
            ->where('tahun_anggaran_id', $tahunAnggaranAktif->id);

        if ($sekolahId) {
            $query->withoutGlobalScope('sekolah')->where('sekolah_id', $sekolahId);
        }

        $search = request('search');
        if ($search) {
            $query->where('uraian', 'like', "%{$search}%");
        }

        $sumberDanaId = request('sumber_dana_id');
        if ($sumberDanaId) {
            $query->where('sumber_dana_id', $sumberDanaId);
        }

        $filteredSub = fn() => (clone $query)->select('id');

        $rencanaPerItem = RkasItemBulan::joinSub($filteredSub(), 'ri_filtered', fn($j) => $j->on('rkas_item_bulan.rkas_item_id', '=', 'ri_filtered.id'))
            ->where('rkas_item_bulan.bulan', $bulan)
            ->selectRaw('rkas_item_bulan.rkas_item_id, sum(rkas_item_bulan.rencana) as total')
            ->groupBy('rkas_item_bulan.rkas_item_id')
            ->pluck('total', 'rkas_item_id');

        $realisasiPerItem = TransaksiBku::joinSub($filteredSub(), 'ri_filtered', fn($j) => $j->on('transaksi_bku.rkas_item_id', '=', 'ri_filtered.id'))
            ->where('transaksi_bku.jenis', 'pengeluaran')
            ->where('transaksi_bku.bulan', $bulan)
            ->selectRaw('transaksi_bku.rkas_item_id, sum(transaksi_bku.jumlah) as total')
            ->groupBy('transaksi_bku.rkas_item_id')
            ->pluck('total', 'rkas_item_id');

        $mapFn = function ($item) use ($rencanaPerItem, $realisasiPerItem) {
            $rencana = (float) ($rencanaPerItem[$item->id] ?? 0);
            $realisasi = (float) ($realisasiPerItem[$item->id] ?? 0);
            $item->rencana_bulan = $rencana;
            $item->realisasi_bulan = $realisasi;
            $item->sisa_bulan = $rencana - $realisasi;
            $item->persen = $rencana > 0 ? round(($realisasi / $rencana) * 100, 1) : 0;
            return $item;
        };

        if ($perPage) {
            return $query->paginate($perPage)->through($mapFn);
        }

        return $query->get()->map($mapFn);
    }

    private function loadKuartalItems(?TahunAnggaran $tahunAnggaranAktif, array $bulanMonths, ?int $sekolahId = null, ?int $perPage = null): \Illuminate\Support\Collection|\Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $query = RkasItem::with('kodeRekening.jenisBelanja', 'program')
            ->where('tahun_anggaran_id', $tahunAnggaranAktif->id);

        if ($sekolahId) {
            $query->withoutGlobalScope('sekolah')->where('sekolah_id', $sekolahId);
        }

        $search = request('search');
        if ($search) {
            $query->where('uraian', 'like', "%{$search}%");
        }

        $sumberDanaId = request('sumber_dana_id');
        if ($sumberDanaId) {
            $query->where('sumber_dana_id', $sumberDanaId);
        }

        $filteredSub = fn() => (clone $query)->select('id');

        $realisasiPerItem = TransaksiBku::joinSub($filteredSub(), 'ri_filtered', fn($j) => $j->on('transaksi_bku.rkas_item_id', '=', 'ri_filtered.id'))
            ->where('transaksi_bku.jenis', 'pengeluaran')
            ->whereIn('transaksi_bku.bulan', $bulanMonths)
            ->selectRaw('transaksi_bku.rkas_item_id, transaksi_bku.bulan, sum(transaksi_bku.jumlah) as total')
            ->groupBy('transaksi_bku.rkas_item_id', 'transaksi_bku.bulan')
            ->get()
            ->groupBy('transaksi_bku.rkas_item_id');

        $mapFn = function ($item) use ($realisasiPerItem, $bulanMonths) {
            $itemRealisasi = $realisasiPerItem[$item->id] ?? collect();
            $realisasiPerBulan = [];
            $totalRealisasi = 0;
            foreach ($bulanMonths as $b) {
                $r = (float) $itemRealisasi->where('bulan', $b)->sum('total');
                $realisasiPerBulan[$b] = $r;
                $totalRealisasi += $r;
            }
            $item->realisasi_per_bulan = $realisasiPerBulan;
            $item->total_realisasi = $totalRealisasi;
            return $item;
        };

        if ($perPage) {
            return $query->paginate($perPage)->through($mapFn);
        }

        return $query->get()->map($mapFn);
    }

    private function prepareRekapRekeningData(Request $request, ?ProfilSekolah $profilOverride = null, ?int $perPage = null): array
    {
        $bulan = (int) $request->get('bulan', date('n'));
        $tahunAnggaranAktif = TahunAnggaran::where('status', true)->first();
        $tahunInput = $request->get('tahun');
        if ($tahunInput) {
            $tahunRecord = TahunAnggaran::where('tahun', $tahunInput)->first();
            if ($tahunRecord) {
                $tahunAnggaranAktif = $tahunRecord;
            }
        }
        $tahunList = TahunAnggaran::orderBy('tahun', 'desc')->get();
        $sumberDanaList = SumberDana::orderBy('kode')->get();
        $sumberDanaId = $request->input('sumber_dana_id');
        $profil = $profilOverride ?? auth()->user()->profilSekolah;

        $rkasItems = $tahunAnggaranAktif
            ? $this->loadRekapRekeningItems($tahunAnggaranAktif, $bulan, $profilOverride?->id, $perPage)
            : ($perPage ? new \Illuminate\Pagination\LengthAwarePaginator([], 0, $perPage) : collect());

        $subtotals = collect();
        $grandTotalRencana = 0;
        $grandTotalRealisasi = 0;

        if ($tahunAnggaranAktif && $perPage) {
            $search = $request->get('search');
            $sekolahId = $profilOverride?->id ?? auth()->user()->sekolah_id;

            $rows = RkasItem::withoutGlobalScope('sekolah')->from('rkas_item as ri')
                ->join('master_kode_rekening as mkr', 'mkr.id', '=', 'ri.kode_rekening_id')
                ->join('jenis_belanja as jb', 'jb.id', '=', 'mkr.jenis_belanja_id')
                ->leftJoin('rkas_item_bulan as rib', fn($j) => $j->on('rib.rkas_item_id', '=', 'ri.id')->where('rib.bulan', $bulan))
                ->leftJoin('transaksi_bku as tb', fn($j) => $j->on('tb.rkas_item_id', '=', 'ri.id')->where('tb.jenis', 'pengeluaran')->where('tb.bulan', $bulan))
                ->selectRaw('jb.nama, COALESCE(SUM(rib.rencana), 0) as total_rencana, COALESCE(SUM(tb.jumlah), 0) as total_realisasi')
                ->where('ri.tahun_anggaran_id', $tahunAnggaranAktif->id)
                ->where('ri.sekolah_id', $sekolahId)
                ->when($search, fn($q) => $q->where('ri.uraian', 'like', "%{$search}%"))
                ->when($sumberDanaId, fn($q) => $q->where('ri.sumber_dana_id', $sumberDanaId))
                ->groupBy('jb.nama')
                ->orderBy('jb.nama')
                ->get();

            foreach ($rows as $row) {
                $ren = (float) $row->total_rencana;
                $rea = (float) $row->total_realisasi;
                $subtotals[$row->nama] = [
                    'rencana' => $ren,
                    'realisasi' => $rea,
                    'sisa' => $ren - $rea,
                    'persen' => $ren > 0 ? round(($rea / $ren) * 100, 1) : 0,
                ];
                $grandTotalRencana += $ren;
                $grandTotalRealisasi += $rea;
            }
        }

        $grandTotalSisa = $grandTotalRencana - $grandTotalRealisasi;
        $grandTotalPersen = $grandTotalRencana > 0 ? round(($grandTotalRealisasi / $grandTotalRencana) * 100, 1) : 0;

        if ($perPage) {
            return compact('rkasItems', 'profil', 'bulan', 'tahunAnggaranAktif',
                'subtotals', 'grandTotalRencana', 'grandTotalRealisasi', 'grandTotalSisa', 'grandTotalPersen', 'tahunList',
                'sumberDanaList', 'sumberDanaId');
        }

        $grouped = $rkasItems->groupBy(fn($item) => $item->kodeRekening?->jenisBelanja?->nama ?? 'Tidak Terkategori');
        return compact('grouped', 'rkasItems', 'profil', 'bulan', 'tahunAnggaranAktif', 'tahunList', 'sumberDanaList', 'sumberDanaId');
    }

    private function prepareRekapKuartalData(Request $request, ?ProfilSekolah $profilOverride = null, ?int $perPage = null): array
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
        $tahunInput = $request->get('tahun');
        if ($tahunInput) {
            $tahunRecord = TahunAnggaran::where('tahun', $tahunInput)->first();
            if ($tahunRecord) {
                $tahunAnggaranAktif = $tahunRecord;
            }
        }
        $tahunList = TahunAnggaran::orderBy('tahun', 'desc')->get();
        $sumberDanaList = SumberDana::orderBy('kode')->get();
        $sumberDanaId = $request->input('sumber_dana_id');
        $profil = $profilOverride ?? auth()->user()->profilSekolah;

        $quarterlyItems = $tahunAnggaranAktif
            ? $this->loadKuartalItems($tahunAnggaranAktif, $bulanMonths, $profilOverride?->id, $perPage)
            : ($perPage ? new \Illuminate\Pagination\LengthAwarePaginator([], 0, $perPage) : collect());

        $subtotals = collect();
        $grandTotalPerBulan = array_fill_keys($bulanMonths, 0);
        $grandTotalAll = 0;

        if ($tahunAnggaranAktif && $perPage) {
            $search = $request->get('search');
            $sekolahId = $profilOverride?->id ?? auth()->user()->sekolah_id;

            $rows = RkasItem::withoutGlobalScope('sekolah')->from('rkas_item as ri')
                ->join('master_kode_rekening as mkr', 'mkr.id', '=', 'ri.kode_rekening_id')
                ->join('jenis_belanja as jb', 'jb.id', '=', 'mkr.jenis_belanja_id')
                ->where('ri.tahun_anggaran_id', $tahunAnggaranAktif->id)
                ->where('ri.sekolah_id', $sekolahId)
                ->when($search, fn($q) => $q->where('ri.uraian', 'like', "%{$search}%"))
                ->when($sumberDanaId, fn($q) => $q->where('ri.sumber_dana_id', $sumberDanaId))
                ->selectRaw('jb.nama')
                ->selectRaw("
                    COALESCE(SUM(CASE WHEN tb.bulan = {$bulanMonths[0]} THEN tb.jumlah ELSE 0 END), 0) as m0,
                    COALESCE(SUM(CASE WHEN tb.bulan = {$bulanMonths[1]} THEN tb.jumlah ELSE 0 END), 0) as m1,
                    COALESCE(SUM(CASE WHEN tb.bulan = {$bulanMonths[2]} THEN tb.jumlah ELSE 0 END), 0) as m2,
                    COALESCE(SUM(tb.jumlah), 0) as total
                ")
                ->leftJoin('transaksi_bku as tb', function ($join) use ($bulanMonths) {
                    $join->on('tb.rkas_item_id', '=', 'ri.id')
                         ->where('tb.jenis', '=', 'pengeluaran')
                         ->whereIn('tb.bulan', $bulanMonths);
                })
                ->groupBy('jb.nama')
                ->orderBy('jb.nama')
                ->get();

            foreach ($rows as $row) {
                $perBulan = [(float) $row->m0, (float) $row->m1, (float) $row->m2];
                $total = (float) $row->total;
                $subtotals[$row->nama] = [
                    'per_bulan' => array_combine($bulanMonths, $perBulan),
                    'total' => $total,
                ];
                foreach ($bulanMonths as $i => $b) {
                    $grandTotalPerBulan[$b] += $perBulan[$i];
                }
                $grandTotalAll += $total;
            }
        }

        if ($perPage) {
            return compact('quarterlyItems', 'profil', 'tahunAnggaranAktif',
                'qLabel', 'periodeLabel', 'bulanMonths', 'bulanNames', 'kuartal', 'bulan',
                'subtotals', 'grandTotalPerBulan', 'grandTotalAll', 'tahunList',
                'sumberDanaList', 'sumberDanaId');
        }

        $grouped = $quarterlyItems->groupBy(
            fn($item) => $item->kodeRekening?->jenisBelanja?->nama ?? 'Tidak Terkategori'
        );
        return compact('grouped', 'profil', 'tahunAnggaranAktif',
            'qLabel', 'periodeLabel', 'bulanMonths', 'bulanNames', 'kuartal', 'bulan', 'tahunList',
            'sumberDanaList', 'sumberDanaId');
    }
}
