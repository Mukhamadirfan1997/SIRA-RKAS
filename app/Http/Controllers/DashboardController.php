<?php

namespace App\Http\Controllers;

use App\Models\RkasItem;
use App\Models\TahunAnggaran;
use App\Models\MasterProgram;
use App\Models\MasterKodeRekening;
use App\Models\JenisBelanja;
use App\Models\ProfilSekolah;
use App\Models\TransaksiBku;
use App\Models\ImportLog;
use Illuminate\Http\Request;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        if (auth()->user()->isAdminKecamatan()) {
            return redirect()->route('dashboard.kecamatan');
        }

        $tahunAnggaranAktif = TahunAnggaran::where('status', true)->first();
        
        // Fetch masters for filter dropdowns
        $programs = MasterProgram::all();
        $kodeRekenings = MasterKodeRekening::all();
        $jenisBelanjas = JenisBelanja::all();
        
        $rkasItemsQuery = $tahunAnggaranAktif ? $tahunAnggaranAktif->rkasItems()->with(['program', 'kodeRekening.jenisBelanja']) : collect();
        
        if ($tahunAnggaranAktif) {
            // Apply filtering before getting collection
            if ($request->filled('program_id')) {
                $rkasItemsQuery->where('program_id', $request->program_id);
            }
            if ($request->filled('kode_rekening_id')) {
                $rkasItemsQuery->where('kode_rekening_id', $request->kode_rekening_id);
            }
            if ($request->filled('jenis_belanja_id')) {
                $rkasItemsQuery->whereHas('kodeRekening', function($q) use ($request) {
                    $q->where('jenis_belanja_id', $request->jenis_belanja_id);
                });
            }
            
            // Eager load related expenses transactions with month constraint
            $rkasItemsQuery->with(['transaksiBkus' => function($q) use ($request) {
                $q->where('jenis', 'pengeluaran');
                if ($request->filled('bulan')) {
                    $q->where('bulan', $request->bulan);
                }
            }, 'bulanRencana']);
            
            $rkasItems = $rkasItemsQuery->get();
        } else {
            $rkasItems = collect();
        }
        
        $totalRencana = 0;
        $totalRealisasi = 0;
        $chartData = [];
        
        // Calculate dynamic properties
        foreach ($rkasItems as $item) {
            // dynamic_realisasi calculation from loaded memory
            $item->dynamic_realisasi = $item->transaksiBkus->sum('jumlah');
            
            // dynamic_rencana calculation based on bulan
            if ($request->filled('bulan')) {
                $rencanaItem = $item->bulanRencana->where('bulan', $request->bulan)->first();
                $item->dynamic_rencana = $rencanaItem ? $rencanaItem->rencana : 0;
            } else {
                $item->dynamic_rencana = $item->jumlah;
            }
            
            $item->dynamic_sisa = $item->dynamic_rencana - $item->dynamic_realisasi;
            $item->persentase = $item->dynamic_rencana > 0 ? ($item->dynamic_realisasi / $item->dynamic_rencana) * 100 : 0;
            
            // Calculate dynamic volumes
            $item->dynamic_rencana_volume = $item->tarif > 0 ? round($item->dynamic_rencana / $item->tarif, 2) : 0;
            $item->dynamic_realisasi_volume = $item->tarif > 0 ? round($item->dynamic_realisasi / $item->tarif, 2) : 0;
            $item->dynamic_sisa_volume = $item->dynamic_rencana_volume - $item->dynamic_realisasi_volume;
            
            $totalRencana += $item->dynamic_rencana;
            $totalRealisasi += $item->dynamic_realisasi;
            
            // Prepare Chart.js data
            if ($item->dynamic_realisasi > 0) {
                $jenisBelanjaName = $item->kodeRekening->jenisBelanja->nama ?? 'Tidak Diketahui';
                if (!isset($chartData[$jenisBelanjaName])) {
                    $chartData[$jenisBelanjaName] = 0;
                }
                $chartData[$jenisBelanjaName] += $item->dynamic_realisasi;
            }
        }
        
        $totalSisa = $totalRencana - $totalRealisasi;
        
        $chartLabels = array_keys($chartData);
        $chartValues = array_values($chartData);
        
        $persentaseCapaian = $totalRencana > 0 ? round(($totalRealisasi / $totalRencana) * 100, 1) : 0;
        
        $realisasiPerBulan = [];
        for ($i = 1; $i <= 12; $i++) {
            $realisasiPerBulan[$i] = 0;
        }
        if ($tahunAnggaranAktif) {
            $rkasItemIds = RkasItem::where('tahun_anggaran_id', $tahunAnggaranAktif->id)->pluck('id');
            $transaksiUser = TransaksiBku::whereIn('rkas_item_id', $rkasItemIds)->where('jenis', 'pengeluaran');
            if ($request->filled('bulan')) {
                $transaksiUser->where('bulan', $request->bulan);
            }
            $byBulan = $transaksiUser->selectRaw('bulan, sum(jumlah) as total')
                        ->groupBy('bulan')
                        ->pluck('total', 'bulan');
            foreach ($byBulan as $b => $t) {
                if (isset($realisasiPerBulan[$b])) {
                    $realisasiPerBulan[$b] = $t;
                }
            }
        }
        $bulanLabels = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
        $trenBulanLabels = $bulanLabels;
        $trenBulanValues = array_values($realisasiPerBulan);
        
        $recentTransaksi = collect();
        $transaksiBulanIni = 0;
        if ($tahunAnggaranAktif) {
            $rkasItemIds = RkasItem::where('tahun_anggaran_id', $tahunAnggaranAktif->id)->pluck('id');
            $recentTransaksi = TransaksiBku::with(['rkasItem.program', 'rkasItem.kodeRekening'])
                ->whereIn('rkas_item_id', $rkasItemIds)
                ->where('jenis', 'pengeluaran')
                ->orderByDesc('created_at')
                ->limit(5)
                ->get();

            $transaksiBulanIni = TransaksiBku::whereIn('rkas_item_id', $rkasItemIds)
                ->where('bulan', (int) date('n'))
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
            'totalRencana', 'totalRealisasi', 'totalSisa', 'rkasItems', 
            'programs', 'kodeRekenings', 'jenisBelanjas',
            'chartLabels', 'chartValues', 'persentaseCapaian',
            'trenBulanLabels', 'trenBulanValues', 'recentTransaksi',
            'transaksiBulanIni', 'tahunAnggaranAktif', 'importStatus'
        ));
    }

    public function kecamatan(Request $request)
    {
        $bulan = $request->get('bulan', date('n'));
        $statusFilter = $request->get('status', '');
        $tahunAnggaranAktif = TahunAnggaran::where('status', true)->first();
        
        $sekolahs = ProfilSekolah::all();
        
        $grandRencana = 0;
        $grandRealisasi = 0;
        $belumUploadCount = 0;
        $chartLabels = [];
        $chartRealisasi = [];
        $chartRencana = [];
        
        foreach ($sekolahs as $sekolah) {
            $sekolah->total_rencana = 0;
            $sekolah->total_realisasi = 0;
            $sekolah->status_import = 'Belum Upload';
            
            if ($tahunAnggaranAktif) {
                $rkasIds = RkasItem::withoutGlobalScope('sekolah')
                                ->where('sekolah_id', $sekolah->id)
                                ->where('tahun_anggaran_id', $tahunAnggaranAktif->id)
                                ->pluck('id');
                                
                $sekolah->total_rencana = \App\Models\RkasItemBulan::whereIn('rkas_item_id', $rkasIds)
                                            ->where('bulan', $bulan)
                                            ->sum('rencana');
                                            
                $sekolah->total_realisasi = TransaksiBku::withoutGlobalScope('sekolah')
                                            ->where('sekolah_id', $sekolah->id)
                                            ->where('jenis', 'pengeluaran')
                                            ->where('bulan', $bulan)
                                            ->sum('jumlah');
                                            
                $importLog = \App\Models\ImportLog::where('sekolah_id', $sekolah->id)
                                            ->where('bulan', $bulan)
                                            ->where('tahun_anggaran_id', $tahunAnggaranAktif->id)
                                            ->orderBy('created_at', 'desc')
                                            ->first();
                $sekolah->status_import = $importLog ? $importLog->status : 'Belum Upload';
            }
            $sekolah->sisa = $sekolah->total_rencana - $sekolah->total_realisasi;
            $sekolah->persentase = $sekolah->total_rencana > 0 ? ($sekolah->total_realisasi / $sekolah->total_rencana) * 100 : 0;
            
            $grandRencana += $sekolah->total_rencana;
            $grandRealisasi += $sekolah->total_realisasi;
            
            if ($sekolah->status_import === 'Belum Upload') {
                $belumUploadCount++;
            }
            
            $chartLabels[] = $sekolah->nama_sekolah;
            $chartRealisasi[] = $sekolah->total_realisasi;
            $chartRencana[] = $sekolah->total_rencana;
        }
        
        $grandSisa = $grandRencana - $grandRealisasi;
        $avgCapaian = $grandRencana > 0 ? round(($grandRealisasi / $grandRencana) * 100, 1) : 0;
        
        if ($statusFilter === 'belum_upload') {
            $sekolahs = $sekolahs->filter(fn($s) => $s->status_import === 'Belum Upload');
        } elseif ($statusFilter === 'telah_import') {
            $sekolahs = $sekolahs->filter(fn($s) => $s->status_import !== 'Belum Upload');
        }

        return view('dashboard-kecamatan', compact(
            'sekolahs', 'bulan', 'tahunAnggaranAktif',
            'grandRencana', 'grandRealisasi', 'grandSisa',
            'avgCapaian', 'belumUploadCount',
            'chartLabels', 'chartRealisasi', 'chartRencana'
        ));
    }
}
