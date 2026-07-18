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
    /** @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse */
    public function index(Request $request)
    {
        $user = auth()->user();
        if ($user === null) {
            abort(403, 'Unauthenticated');
        }
        if ($user->isAdminKecamatan()) {
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

        $bulanParam = $request->input('bulan');
        $bulan = $request->filled('bulan') && (is_numeric($bulanParam) || is_string($bulanParam)) ? (int) $bulanParam : null;
        $totalRencana = 0;
        $totalRealisasi = 0;
        $chartLabels = [];
        $chartValues = [];
        $rkasItems = collect();
        $trenBulanLabels = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
        $trenBulanValues = array_fill(0, 12, 0);
        $transaksiBulanIni = 0;
        $importStatus = collect();
        $filteredIds = collect();

        if ($tahunAnggaranAktif) {
            $baseQuery = $tahunAnggaranAktif->rkasItems()->withoutGlobalScope('sekolah');
            $sekolahId = $user->sekolah_id;
            if ($sekolahId) {
                $baseQuery->where('rkas_item.sekolah_id', $sekolahId);
            }

            $programId = $request->input('program_id');
            $kodeRekeningId = $request->input('kode_rekening_id');
            $jenisBelanjaId = $request->input('jenis_belanja_id');

            if ($programId) {
                $baseQuery->where('program_id', $programId);
            }
            if ($kodeRekeningId) {
                $baseQuery->where('kode_rekening_id', $kodeRekeningId);
            }
            if ($jenisBelanjaId) {
                $baseQuery->whereHas('kodeRekening', function ($q) use ($jenisBelanjaId) {
                    $q->where('jenis_belanja_id', $jenisBelanjaId);
                });
            }
            if ($sumberDanaId) {
                $baseQuery->where('sumber_dana_id', $sumberDanaId);
            }

            $dashVerRaw = Cache::get('dash_ver_' . $user->id, 0);
            $dashVer = is_int($dashVerRaw) ? $dashVerRaw : 0;
            $cacheKey = 'dashboard_user_' . $user->id . '_v' . $dashVer . '_' . md5(serialize([$tahunAnggaranAktif->id, $bulan, $programId, $kodeRekeningId, $jenisBelanjaId, $sumberDanaId]));

            $cached = Cache::get($cacheKey);

            if (is_array($cached)) {
                $_rencana = $cached['totalRencana'] ?? 0;
                $totalRencana = is_numeric($_rencana) ? (int) $_rencana : 0;
                $_realisasi = $cached['totalRealisasi'] ?? 0;
                $totalRealisasi = is_numeric($_realisasi) ? (int) $_realisasi : 0;
                $chartLabels = isset($cached['chartLabels']) && is_array($cached['chartLabels']) ? $cached['chartLabels'] : [];
                $chartValues = isset($cached['chartValues']) && is_array($cached['chartValues']) ? $cached['chartValues'] : [];
                $trenBulanLabels = isset($cached['trenBulanLabels']) && is_array($cached['trenBulanLabels']) ? $cached['trenBulanLabels'] : [];
                $trenBulanValues = isset($cached['trenBulanValues']) && is_array($cached['trenBulanValues']) ? $cached['trenBulanValues'] : [];
                $_trx = $cached['transaksiBulanIni'] ?? 0;
                $transaksiBulanIni = is_numeric($_trx) ? (int) $_trx : 0;
                $importStatus = $cached['importStatus'];
            } else {
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

                $chartData = TransaksiBku::withoutGlobalScope('sekolah')->whereIn('rkas_item_id', $filteredIds)
                    ->where('jenis', 'pengeluaran')
                    ->where('transaksi_bku.sekolah_id', $sekolahId)
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

                $realisasiPerBulan = array_fill(1, 12, 0);
                $byBulan = TransaksiBku::whereIn('rkas_item_id', $filteredIds)
                    ->where('jenis', 'pengeluaran')
                    ->selectRaw('transaksi_bku.bulan, sum(transaksi_bku.jumlah) as total')
                    ->groupBy('transaksi_bku.bulan')
                    ->pluck('total', 'bulan');
                foreach ($byBulan as $b => $t) {
                    if (isset($realisasiPerBulan[$b]) && is_numeric($t)) {
                        $realisasiPerBulan[$b] = (float) $t;
                    }
                }
                $bulanLabels = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
                $trenBulanLabels = $bulanLabels;
                $trenBulanValues = array_values($realisasiPerBulan);

                $transaksiBulanIni = TransaksiBku::whereIn('rkas_item_id', $filteredIds)
                    ->where('bulan', (int) Carbon::now()->month)
                    ->count();

                $importLogs = ImportLog::where('tahun_anggaran_id', $tahunAnggaranAktif->id)->get();

                $importStatus = collect(range(1, 12))->map(function ($m) use ($importLogs) {
                    $latest = $importLogs->where('bulan', $m)->sortByDesc('created_at')->first();
                    return (object) [
                        'bulan' => $m,
                        'nama' => Carbon::createFromDate(null, $m, 1)->translatedFormat('F'),
                        'status' => $latest ? $latest->status : null,
                        'baris_berhasil' => $latest ? $latest->baris_berhasil : null,
                    ];
                });

                Cache::put($cacheKey, [
                    'totalRencana' => $totalRencana,
                    'totalRealisasi' => $totalRealisasi,
                    'chartLabels' => $chartLabels,
                    'chartValues' => $chartValues,
                    'trenBulanLabels' => $trenBulanLabels,
                    'trenBulanValues' => $trenBulanValues,
                    'transaksiBulanIni' => $transaksiBulanIni,
                    'importStatus' => $importStatus,
                ], now()->addMinutes(5));
            }

            $rkasItems = (clone $baseQuery)
                ->with(['program', 'kodeRekening.jenisBelanja', 'transaksiBkus' => function (\Illuminate\Database\Eloquent\Relations\Relation $q) use ($bulan) {
                    $q->where('jenis', 'pengeluaran');
                    if ($bulan) {
                        $q->where('bulan', $bulan);
                    }
                }, 'bulanRencana' => function (\Illuminate\Database\Eloquent\Relations\Relation $q) use ($bulan) {
                    if ($bulan) {
                        $q->where('bulan', $bulan);
                    }
                }])
                ->orderBy('no_urut')
                ->paginate(50);

            foreach ($rkasItems as $item) {
                $_sum = $item->transaksiBkus->sum('jumlah');
                $item->dynamic_realisasi = is_numeric($_sum) ? (float) $_sum : 0.0;

                if ($bulan) {
                    $rencanaItem = $item->bulanRencana->first();
                    $item->dynamic_rencana = $rencanaItem ? (float) $rencanaItem->rencana : 0.0;
                } else {
                    $item->dynamic_rencana = (float) $item->jumlah;
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

        $recentTransaksi = collect();
        if ($tahunAnggaranAktif && $filteredIds->isNotEmpty()) {
            $recentTransaksi = TransaksiBku::with(['rkasItem.program', 'rkasItem.kodeRekening.jenisBelanja'])
                ->whereIn('rkas_item_id', $filteredIds)
                ->where('jenis', 'pengeluaran')
                ->orderByDesc('created_at')
                ->limit(5)
                ->get();
        }

        return view('dashboard', compact(
            'pageTitle', 'totalRencana', 'totalRealisasi', 'totalSisa', 'rkasItems',
            'programs', 'kodeRekenings', 'jenisBelanjas',
            'chartLabels', 'chartValues', 'persentaseCapaian',
            'trenBulanLabels', 'trenBulanValues', 'recentTransaksi',
            'transaksiBulanIni', 'tahunAnggaranAktif', 'tahunList', 'sumberDanas', 'sumberDanaId', 'importStatus'
        ));
    }

    public function kecamatan(Request $request): \Illuminate\View\View
    {
        $pageTitle = 'Dashboard Kecamatan';
        $bulanVal = $request->input('bulan', date('n'));
        $bulan = is_numeric($bulanVal) ? (int) $bulanVal : (int) date('n');
        $statusFilterVal = $request->input('status', '');
        $statusFilter = is_string($statusFilterVal) ? $statusFilterVal : '';
        $tahunAnggaranAktif = TahunAnggaran::getActive();
        $tahunInput = $request->input('tahun');
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
            $sekolahIds = $sekolahs->pluck('id')->toArray();

            if (is_array($cached) && isset($cached['sekolah_ids']) && $cached['sekolah_ids'] === $sekolahIds) {
                $_gr = $cached['grandRencana'] ?? 0;
                $grandRencana = is_numeric($_gr) ? (float) $_gr : 0.0;
                $_grea = $cached['grandRealisasi'] ?? 0;
                $grandRealisasi = is_numeric($_grea) ? (float) $_grea : 0.0;
                $_gs = $cached['grandSisa'] ?? 0;
                $grandSisa = is_numeric($_gs) ? (float) $_gs : 0.0;
                $_avg = $cached['avgCapaian'] ?? 0;
                $avgCapaian = is_numeric($_avg) ? (float) $_avg : 0.0;
                $_buc = $cached['belumUploadCount'] ?? 0;
                $belumUploadCount = is_numeric($_buc) ? (int) $_buc : 0;
                $chartLabels = isset($cached['chartLabels']) && is_array($cached['chartLabels']) ? $cached['chartLabels'] : [];
                $chartRealisasi = isset($cached['chartRealisasi']) && is_array($cached['chartRealisasi']) ? $cached['chartRealisasi'] : [];
                $chartRencana = isset($cached['chartRencana']) && is_array($cached['chartRencana']) ? $cached['chartRencana'] : [];
                $rencanaMap = isset($cached['rencanaMap']) && is_array($cached['rencanaMap']) ? $cached['rencanaMap'] : [];
                $realisasiMap = isset($cached['realisasiMap']) && is_array($cached['realisasiMap']) ? $cached['realisasiMap'] : [];
                $statusMap = isset($cached['statusMap']) && is_array($cached['statusMap']) ? $cached['statusMap'] : [];
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
                $statusPerSekolah = $allLogs->groupBy('sekolah_id')->map(fn($g) => $g->first()?->status);

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
                    $_sid = $sekolah->id;
                    $_rencanaRaw = $rencanaPerSekolah->get($_sid) ?? 0;
                    $rencana = is_numeric($_rencanaRaw) ? (float) $_rencanaRaw : 0.0;
                    $_realisasiRaw = $realisasiPerSekolah->get($_sid) ?? 0;
                    $realisasi = is_numeric($_realisasiRaw) ? (float) $_realisasiRaw : 0.0;
                    $_statusRaw = $statusPerSekolah->get($_sid);
                    $status = is_string($_statusRaw) ? $_statusRaw : 'Belum Upload';

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
                $_sid = $sekolah->id;
                $_r = isset($rencanaMap[$_sid]) && is_numeric($rencanaMap[$_sid]) ? (float) $rencanaMap[$_sid] : 0.0;
                $sekolah->total_rencana = $_r;
                $_rr = isset($realisasiMap[$_sid]) && is_numeric($realisasiMap[$_sid]) ? (float) $realisasiMap[$_sid] : 0.0;
                $sekolah->total_realisasi = $_rr;
                $sekolah->status_import = isset($statusMap[$_sid]) && is_string($statusMap[$_sid]) ? $statusMap[$_sid] : 'Belum Upload';
                $sekolah->sisa = $_r - $_rr;
                $sekolah->persentase = $_r > 0 ? ($_rr / $_r) * 100 : 0;
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
