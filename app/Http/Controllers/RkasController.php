<?php

namespace App\Http\Controllers;

use App\Imports\RkasImport;
use App\Models\RkasItem;
use App\Models\TahunAnggaran;
use App\Models\MasterProgram;
use App\Models\MasterKodeRekening;
use App\Models\SumberDana;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class RkasController extends Controller
{
    public function index(Request $request)
    {
        $bulan = $request->get('bulan', date('n'));
        $tahunAnggaranAktif = TahunAnggaran::where('status', true)->first();
        
        $rkasItems = collect();
        if ($tahunAnggaranAktif) {
            $rkasItems = TahunAnggaran::find($tahunAnggaranAktif->id)
                ->rkasItems()
                ->with(['program', 'kodeRekening', 'sumberDana', 'bulanRencana' => function($q) use ($bulan) {
                    $q->where('bulan', $bulan);
                }, 'transaksiBkus' => function($q) {
                    $q->where('jenis', 'pengeluaran');
                }])
                ->orderBy('no_urut')
                ->get();
        }

        return view('rkas.index', compact('rkasItems', 'tahunAnggaranAktif', 'bulan'));
    }



    public function edit(RkasItem $rkasItem)
    {
        $tahunAnggaranAktif = TahunAnggaran::where('status', true)->first();
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

        return redirect()->route('rkas.index')->with('success', 'Item RKAS berhasil diupdate.');
    }

    public function destroy(RkasItem $rkasItem)
    {
        $rkasItem->delete();
        return back()->with('success', 'Item RKAS berhasil dihapus.');
    }
}
