<?php

namespace App\Http\Controllers;

use App\Models\RkasItem;
use App\Models\RkasItemBulan;
use App\Models\TahunAnggaran;
use App\Models\MasterProgram;
use App\Models\MasterKodeRekening;
use App\Models\JenisBelanja;
use App\Models\ProfilSekolah;
use App\Models\SumberDana;
use App\Models\TransaksiBku;
use App\Models\ImportLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        if (auth()->user()->isAdminKecamatan()) {
            return redirect()->route('dashboard.kecamatan');
        }

        $pageTitle = 'Dashboard Sekolah';
        $tahunAnggaranAktif = TahunAnggaran::getActive();
        $tahunInput = $request->get('tahun');
        if ($tahunInput) {
            $tahunRecord = TahunAnggaran::where('tahun', $tahunInput)->first();
            if ($tahunRecord) {
                $tahunAnggaranAktif = $tahunRecord;
            }
        }
        $tahunList = TahunAnggaran::orderBy('tahun', 'desc')->get();

        $sumberDanas = SumberDana::orderBy('kode')->get();
        $sumberDanaId = $request->input('sumber_dana_id');

        $programs = Cache::remember('master_programs', 86400, fn() => MasterProgram::all());
        $kodeRekenings = Cache::remember('master_kode_rekenings', 86400, fn() => MasterKodeRekening::all());
        $jenisBelanjas = Cache::remember('jenis_belanjas', 86400, fn() => JenisBelanja::all());

        $bulan = $request->filled('bulan') ? (int) $request->bulan : null;
        $totalRencana = 0;
        $totalRealisasi = 0;
        $chartLabels = [];
        $chartValues = [];
        $rkasItems = collect();

        if ($tahunAnggaranAktif) {
            $baseQuery = $tahunAnggaranAktif->rkasItems();

            if ($request->filled('program_id')) {
                $baseQuery->where('program_id', $request->program_id);
            }
            if ($request->filled('kode_rekening_id')) {
                $baseQuery->where('kode_rekening_id', $request->kode_rekening_id);
            }
            if ($request->filled('jenis_belanja_id')) {
                $baseQuery->whereHas('kodeRekening', function ($q) use ($request) {
                    $q->where('jenis_belanja_id', $request->jenis_belanja_id);
                });
            }
            if ($sumberDanaId) {
                $baseQuery->where('sumber_dana_id', $sumberDanaId);
            }

            $filteredIds = $baseQuery->pluck('id');

            if ($bulan) {
                $totalRencana = RkasItemBulan::whereIn('rkas_item_id', $filteredIds)
                    ->where('bulan', $bulan)
                    ->sum('rencana');

                $totalRealisasi = TransaksiBku::whereIn('rkas_item_id', $filteredIds)
                    ->where('jenis', 'pengeluaran')
                    ->where('bulan', $bulan)
                    ->sum('jumlah');
            } else {
                $totalRencana = RkasItem::whereIn('id', $filteredIds)->sum('jumlah');

                $totalRealisasi = TransaksiBku::whereIn('rkas_item_id', $filteredIds)
                    ->where('jenis', 'pengeluaran')
                    ->sum('jumlah');
            }

            $chartData = TransaksiBku::whereIn('rkas_item_id', $filteredIds)
                ->where('jenis', 'pengeluaran')
                ->join('rkas_item', 'transaksi_bku.rkas_item_id', '=', 'rkas_item.id')
                ->join('master_kode_rekening', 'rkas_item.kode_rekening_id', '=', 'master_kode_rekening.id')
                ->join('jenis_belanja', 'master_kode_rekening.jenis_belanja_id', '=', 'jenis_belanja.id')
                ->selectRaw('jenis_belanja.nama as label, sum(transaksi_bku.jumlah) as total')
                ->groupBy('jenis_belanja.nama')
                ->get();

            $chartLabels = [];
            $chartValues = [];
            foreach ($chartData as $d) {
                $chartLabels[] = $d->label;
                $chartValues[] = (float) $d->total;
            }

            $rkasItems = (clone $baseQuery)
                ->with(['program', 'kodeRekening.jenisBelanja', 'transaksiBkus' => function ($q) use ($bulan) {
                    $q->where('jenis', 'pengeluaran');
                    if ($bulan) {
                        $q->where('bulan', $bulan);
                    }
                }, 'bulanRencana' => function ($q) use ($bulan) {
                    if ($bulan) {
                        $q->where('bulan', $bulan);
                    }
                }])
                ->orderBy('no_urut')
                ->paginate(50);

            foreach ($rkasItems as $item) {
                $item->dynamic_realisasi = $item->transaksiBkus->sum('jumlah');

                if ($bulan) {
                    $rencanaItem = $item->bulanRencana->first();
                    $item->dynamic_rencana = $rencanaItem ? $rencanaItem->rencana : 0;
                } else {
                    $item->dynamic_rencana = $item->jumlah;
                }

                $item->dynamic_sisa = $item->dynamic_rencana - $item->dynamic_realisasi;
                $item->persentase = $item->dynamic_rencana > 0 ? ($item->dynamic_realisasi / $item->dynamic_rencana) * 100 : 0;

                $item->dynamic_rencana_volume = $item->tarif > 0 ? round($item->dynamic_rencana / $item->tarif, 2) : 0;
                $item->dynamic_realisasi_volume = $item->tarif > 0 ? round($item->dynamic_realisasi / $item->tarif, 2) : 0;
                $item->dynamic_sisa_volume = $item->dynamic_rencana_volume - $item->dynamic_realisasi_volume;
            }
        }

        $totalSisa = $totalRencana - $totalRealisasi;
        $persentaseCapaian = $totalRencana > 0 ? round(($totalRealisasi / $totalRencana) * 100, 1) : 0;

        $realisasiPerBulan = array_fill(1, 12, 0);
        if ($tahunAnggaranAktif) {
            $byBulan = TransaksiBku::whereIn('rkas_item_id', $filteredIds)
                ->where('jenis', 'pengeluaran')
                ->selectRaw('transaksi_bku.bulan, sum(transaksi_bku.jumlah) as total')
                ->groupBy('transaksi_bku.bulan')
                ->pluck('total', 'bulan');
            foreach ($byBulan as $b => $t) {
                if (isset($realisasiPerBulan[$b])) {
                    $realisasiPerBulan[$b] = (float) $t;
                }
            }
        }
        $bulanLabels = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
        $trenBulanLabels = $bulanLabels;
        $trenBulanValues = array_values($realisasiPerBulan);

        $recentTransaksi = collect();
        $transaksiBulanIni = 0;
        if ($tahunAnggaranAktif) {
            $recentTransaksi = TransaksiBku::with(['rkasItem.program', 'rkasItem.kodeRekening.jenisBelanja'])
                ->whereIn('rkas_item_id', $filteredIds)
                ->where('jenis', 'pengeluaran')
                ->orderByDesc('created_at')
                ->limit(5)
                ->get();

            $transaksiBulanIni = TransaksiBku::whereIn('rkas_item_id', $filteredIds)
                ->where('bulan', (int) Carbon::now()->month)
                ->count();
        }

        $importStatus = collect();
        if ($tahunAnggaranAktif) {
            $logs = ImportLog::where('tahun_anggaran_id', $tahunAnggaranAktif->id)
                ->get()
                ->groupBy('bulan')
                ->map(fn($group) => $group->sortByDesc('created_at')->first());

            $importStatus = collect(range(1, 12))->map(fn($m) => (object) [
                'bulan' => $m,
                'nama' => Carbon::create()->month($m)->translatedFormat('F'),
                'status' => $logs->has($m) ? $logs[$m]->status : null,
                'baris_berhasil' => $logs->has($m) ? $logs[$m]->baris_berhasil : null,
            ]);
        }

        return view('dashboard', compact(
            'pageTitle', 'totalRencana', 'totalRealisasi', 'totalSisa', 'rkasItems',
            'programs', 'kodeRekenings', 'jenisBelanjas',
            'chartLabels', 'chartValues', 'persentaseCapaian',
            'trenBulanLabels', 'trenBulanValues', 'recentTransaksi',
            'transaksiBulanIni', 'tahunAnggaranAktif', 'tahunList', 'sumberDanas', 'sumberDanaId', 'importStatus'
        ));
    }

    public function kecamatan(Request $request)
    {
        $pageTitle = 'Dashboard Kecamatan';
        $bulan = $request->get('bulan', date('n'));
        $statusFilter = $request->get('status', '');
        $tahunAnggaranAktif = TahunAnggaran::getActive();
        $tahunInput = $request->get('tahun');
        if ($tahunInput) {
            $tahunRecord = TahunAnggaran::where('tahun', $tahunInput)->first();
            if ($tahunRecord) {
                $tahunAnggaranAktif = $tahunRecord;
            }
        }
        $tahunList = TahunAnggaran::orderBy('tahun', 'desc')->get();

        $sekolahs = ProfilSekolah::orderBy('nama')->get();

        if ($tahunAnggaranAktif && $sekolahs->isNotEmpty()) {
            $cacheKey = 'dashboard_kecamatan_' . $tahunAnggaranAktif->id . '_' . $bulan;
            $cached = Cache::get($cacheKey);

            if ($cached && $cached['sekolah_ids'] === $sekolahs->pluck('id')->toArray()) {
                $grandRencana = $cached['grandRencana'];
                $grandRealisasi = $cached['grandRealisasi'];
                $grandSisa = $cached['grandSisa'];
                $avgCapaian = $cached['avgCapaian'];
                $belumUploadCount = $cached['belumUploadCount'];
                $chartLabels = $cached['chartLabels'];
                $chartRealisasi = $cached['chartRealisasi'];
                $chartRencana = $cached['chartRencana'];
                $rencanaMap = $cached['rencanaMap'];
                $realisasiMap = $cached['realisasiMap'];
                $statusMap = $cached['statusMap'];
            } else {
                $sekolahIds = $sekolahs->pluck('id')->toArray();

                $rencanaPerSekolah = RkasItemBulan::where('bulan', $bulan)
                    ->join('rkas_item', 'rkas_item.id', '=', 'rkas_item_bulan.rkas_item_id')
                    ->where('rkas_item.tahun_anggaran_id', $tahunAnggaranAktif->id)
                    ->whereIn('rkas_item.sekolah_id', $sekolahIds)
                    ->selectRaw('rkas_item.sekolah_id, SUM(rkas_item_bulan.rencana) as total')
                    ->groupBy('rkas_item.sekolah_id')
                    ->pluck('total', 'sekolah_id');

                $realisasiPerSekolah = TransaksiBku::withoutGlobalScope('sekolah')
                    ->where('tahun_anggaran_id', $tahunAnggaranAktif->id)
                    ->where('jenis', 'pengeluaran')
                    ->where('bulan', $bulan)
                    ->whereIn('sekolah_id', $sekolahIds)
                    ->selectRaw('sekolah_id, SUM(jumlah) as total')
                    ->groupBy('sekolah_id')
                    ->pluck('total', 'sekolah_id');

                $allLogs = ImportLog::whereIn('sekolah_id', $sekolahIds)
                    ->where('bulan', $bulan)
                    ->where('tahun_anggaran_id', $tahunAnggaranAktif->id)
                    ->orderBy('created_at', 'desc')
                    ->get(['sekolah_id', 'status']);
                $statusPerSekolah = $allLogs->groupBy('sekolah_id')->map(fn($g) => $g->first()->status);

                $grandRencana = 0;
                $grandRealisasi = 0;
                $belumUploadCount = 0;
                $chartLabels = [];
                $chartRealisasi = [];
                $chartRencana = [];
                $rencanaMap = [];
                $realisasiMap = [];
                $statusMap = [];

                foreach ($sekolahs as $sekolah) {
                    $rencana = (float) ($rencanaPerSekolah[$sekolah->id] ?? 0);
                    $realisasi = (float) ($realisasiPerSekolah[$sekolah->id] ?? 0);
                    $status = $statusPerSekolah[$sekolah->id] ?? 'Belum Upload';

                    $rencanaMap[$sekolah->id] = $rencana;
                    $realisasiMap[$sekolah->id] = $realisasi;
                    $statusMap[$sekolah->id] = $status;

                    $grandRencana += $rencana;
                    $grandRealisasi += $realisasi;

                    if ($status === 'Belum Upload') {
                        $belumUploadCount++;
                    }

                    $chartLabels[] = $sekolah->nama;
                    $chartRealisasi[] = $realisasi;
                    $chartRencana[] = $rencana;
                }

                $grandSisa = $grandRencana - $grandRealisasi;
                $avgCapaian = $grandRencana > 0 ? round(($grandRealisasi / $grandRencana) * 100, 1) : 0;

                Cache::put($cacheKey, [
                    'sekolah_ids' => $sekolahIds,
                    'grandRencana' => $grandRencana,
                    'grandRealisasi' => $grandRealisasi,
                    'grandSisa' => $grandSisa,
                    'avgCapaian' => $avgCapaian,
                    'belumUploadCount' => $belumUploadCount,
                    'chartLabels' => $chartLabels,
                    'chartRealisasi' => $chartRealisasi,
                    'chartRencana' => $chartRencana,
                    'rencanaMap' => $rencanaMap,
                    'realisasiMap' => $realisasiMap,
                    'statusMap' => $statusMap,
                ], now()->addMinutes(15));
            }

            foreach ($sekolahs as $sekolah) {
                $sekolah->total_rencana = $rencanaMap[$sekolah->id] ?? 0;
                $sekolah->total_realisasi = $realisasiMap[$sekolah->id] ?? 0;
                $sekolah->status_import = $statusMap[$sekolah->id] ?? 'Belum Upload';
                $sekolah->sisa = $sekolah->total_rencana - $sekolah->total_realisasi;
                $sekolah->persentase = $sekolah->total_rencana > 0 ? ($sekolah->total_realisasi / $sekolah->total_rencana) * 100 : 0;
            }
        } else {
            $grandRencana = 0;
            $grandRealisasi = 0;
            $grandSisa = 0;
            $avgCapaian = 0;
            $belumUploadCount = 0;
            $chartLabels = [];
            $chartRealisasi = [];
            $chartRencana = [];

            foreach ($sekolahs as $sekolah) {
                $sekolah->total_rencana = 0;
                $sekolah->total_realisasi = 0;
                $sekolah->status_import = 'Belum Upload';
                $sekolah->sisa = 0;
                $sekolah->persentase = 0;
            }
        }

        if ($statusFilter === 'belum_upload') {
            $sekolahs = $sekolahs->filter(fn($s) => $s->status_import === 'Belum Upload');
        } elseif ($statusFilter === 'telah_import') {
            $sekolahs = $sekolahs->filter(fn($s) => $s->status_import !== 'Belum Upload');
        }

        return view('dashboard-kecamatan', compact(
            'pageTitle', 'sekolahs', 'bulan', 'tahunAnggaranAktif', 'tahunList',
            'grandRencana', 'grandRealisasi', 'grandSisa',
            'avgCapaian', 'belumUploadCount',
            'chartLabels', 'chartRealisasi', 'chartRencana'
        ));
    }
}
