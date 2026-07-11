<?php

namespace App\Http\Controllers;

use App\Models\MasterKodeRekening;
use App\Models\JenisBelanja;
use App\Imports\MasterKodeRekeningImport;
use App\Exports\MasterKodeRekeningTemplateExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;

class MasterKodeRekeningController extends Controller
{
    public function index()
    {
        $masterKodeRekenings = MasterKodeRekening::with('jenisBelanja')->get();
        return view('master-kode-rekening.index', compact('masterKodeRekenings'));
    }

    public function downloadTemplate()
    {
        return Excel::download(new MasterKodeRekeningTemplateExport, 'template_master_kode_rekening.xlsx');
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv',
        ]);

        Excel::import(new MasterKodeRekeningImport, $request->file('file'));

        return back()->with('success', 'Master Kode Rekening berhasil diimport!');
    }

    public function create()
    {
        $jenisBelanjas = JenisBelanja::all();
        return view('master-kode-rekening.create', compact('jenisBelanjas'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'kode' => 'required|unique:master_kode_rekening,kode',
            'nama' => 'required',
            'jenis_belanja_id' => 'required|exists:jenis_belanja,id',
        ]);

        MasterKodeRekening::create($validated);

        return redirect()->route('master-kode-rekening.index')->with('success', 'Master Kode Rekening berhasil ditambahkan.');
    }

    public function edit(MasterKodeRekening $masterKodeRekening)
    {
        $jenisBelanjas = JenisBelanja::all();
        return view('master-kode-rekening.edit', compact('masterKodeRekening', 'jenisBelanjas'));
    }

    public function update(Request $request, MasterKodeRekening $masterKodeRekening)
    {
        $validated = $request->validate([
            'kode' => 'required|unique:master_kode_rekening,kode,' . $masterKodeRekening->id,
            'nama' => 'required',
            'jenis_belanja_id' => 'required|exists:jenis_belanja,id',
        ]);

        $masterKodeRekening->update($validated);

        return redirect()->route('master-kode-rekening.index')->with('success', 'Master Kode Rekening berhasil diupdate.');
    }

    public function destroy(MasterKodeRekening $masterKodeRekening)
    {
        $masterKodeRekening->delete();
        return back()->with('success', 'Master Kode Rekening berhasil dihapus.');
    }
}
