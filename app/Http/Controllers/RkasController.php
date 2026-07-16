<?php

namespace App\Http\Controllers;

use App\Imports\RkasImport;
use App\Models\ProfilSekolah;
use App\Models\RkasItem;
use App\Models\TahunAnggaran;
use App\Models\SumberDana;
use App\Models\MasterProgram;
use App\Models\MasterKodeRekening;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\DB;

class RkasController extends Controller
{
    public function index(Request $request)
    {
        $bulan = $request->get('bulan', date('n'));
        $programId = $request->get('program_id');
        $tahunAnggaranAktif = TahunAnggaran::getActive();
        $tahunInput = $request->get('tahun');
        if ($tahunInput) {
            $tahunRecord = TahunAnggaran::where('tahun', $tahunInput)->first();
            if ($tahunRecord) {
                $tahunAnggaranAktif = $tahunRecord;
            }
        }
        $tahunList = TahunAnggaran::orderBy('tahun', 'desc')->get();
        $sumberDanaList = SumberDana::orderBy('kode')->get();
        $sumberDanaId = request('sumber_dana_id');
        $programs = MasterProgram::whereNull('parent_id')->orderBy('kode')->get();
        $isAdmin = auth()->user()->isAdminKecamatan();
        $sekolahs = collect();
        $sekolahId = null;
        if ($isAdmin) {
            $sekolahs = ProfilSekolah::orderBy('nama')->get();
            $sekolahId = $request->input('sekolah_id');
        }

        $totalJumlah = 0;
        $totalRealisasi = 0;
        $belumLengkapCount = 0;
        $rkasItems = collect();

        if ($tahunAnggaranAktif) {
            $baseQuery = TahunAnggaran::find($tahunAnggaranAktif->id)->rkasItems();

            if ($programId) {
                $baseQuery->where('program_id', $programId);
            }

            if ($search = $request->get('search')) {
                $baseQuery->where('uraian', 'LIKE', "%{$search}%");
            }

            if ($sumberDanaId) {
                $baseQuery->where('sumber_dana_id', $sumberDanaId);
            }

            if ($sekolahId) {
                $baseQuery->withoutGlobalScope('sekolah')->where('sekolah_id', $sekolahId);
            }

            $filteredIds = fn() => (clone $baseQuery)->select('id');

            $totalJumlah = RkasItem::whereIn('id', $filteredIds())->sum('jumlah');

            $totalRealisasi = \App\Models\TransaksiBku::joinSub($filteredIds(), 'ri_filtered', fn($j) => $j->on('transaksi_bku.rkas_item_id', '=', 'ri_filtered.id'))
                ->where('transaksi_bku.jenis', 'pengeluaran')
                ->sum('transaksi_bku.jumlah');

            $belumLengkapCount = RkasItem::whereIn('id', $filteredIds())
                ->where(function ($q) {
                    $q->whereNull('program_id')
                      ->orWhereNull('kode_rekening_id');
                })
                ->count();

            $rkasItems = $baseQuery
                ->with(['program', 'kodeRekening', 'sumberDana', 'sekolah', 'bulanRencana' => function ($q) use ($bulan) {
                    $q->where('bulan', $bulan);
                }, 'transaksiBkus' => function ($q) {
                    $q->where('jenis', 'pengeluaran');
                }])
                ->orderBy('no_urut')
                ->paginate(50);
        }

        return view('rkas.index', compact(
            'rkasItems', 'tahunAnggaranAktif', 'tahunList', 'bulan', 'programs', 'programId',
            'totalJumlah', 'totalRealisasi', 'belumLengkapCount',
            'sumberDanaList', 'sumberDanaId', 'isAdmin', 'sekolahs', 'sekolahId'
        ));
    }

    public function edit(RkasItem $rkasItem)
    {
        $tahunAnggaranAktif = TahunAnggaran::getActive();
        $masterPrograms = MasterProgram::orderBy('kode')->get();
        $masterKodeRekenings = MasterKodeRekening::orderBy('kode')->get();
        $sumberDanas = SumberDana::orderBy('kode')->get();

        return view('rkas.edit', compact('rkasItem', 'masterPrograms', 'masterKodeRekenings', 'sumberDanas'));
    }

    public function update(Request $request, RkasItem $rkasItem)
    {
        $validated = $request->validate([
            'no_urut' => 'required|integer',
            'uraian' => 'required|string',
            'program_id' => 'nullable|exists:master_program,id',
            'kode_rekening_id' => 'nullable|exists:master_kode_rekening,id',
            'sumber_dana_id' => 'nullable|exists:sumber_dana,id',
            'volume' => 'nullable|numeric',
            'satuan' => 'nullable|string|max:255',
            'tarif' => 'nullable|numeric',
            'jumlah' => 'required|numeric',
        ]);

        $rkasItem->update($validated);

        Cache::increment('dash_ver_' . auth()->id());

        return redirect()->route('rkas.index')->with('success', 'Item RKAS berhasil diupdate.');
    }

    public function destroy(RkasItem $rkasItem)
    {
        $rkasItem->delete();

        Cache::increment('dash_ver_' . auth()->id());

        return back()->with('success', 'Item RKAS berhasil dihapus.');
    }
}
