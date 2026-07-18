<?php

namespace App\Http\Controllers;

use App\Models\MasterKodeRekening;
use App\Models\JenisBelanja;
use App\Imports\MasterKodeRekeningImport;
use App\Exports\MasterKodeRekeningTemplateExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class MasterKodeRekeningController extends Controller
{
    public function index(Request $request): \Illuminate\View\View
    {
        $query = MasterKodeRekening::with('jenisBelanja');

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('nama', 'LIKE', "%{$search}%")
                  ->orWhere('kode', 'LIKE', "%{$search}%");
            });
        }

        $masterKodeRekenings = $query->paginate(50);
        return view('master-kode-rekening.index', compact('masterKodeRekenings'));
    }

    public function downloadTemplate(): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        return Excel::download(new MasterKodeRekeningTemplateExport, 'template_master_kode_rekening.xlsx');
    }

    public function import(Request $request): \Illuminate\Http\RedirectResponse
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv',
        ]);

        try {
            Excel::import(new MasterKodeRekeningImport, $request->file('file'));
        } catch (\Throwable $e) {
            Log::error('Gagal import master kode rekening: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            return back()->with('error', 'Gagal membaca file. Pastikan file yang diupload adalah file Excel yang valid.');
        }

        Cache::forget('master_kode_rekenings');

        return back()->with('success', 'Master Kode Rekening berhasil diimport!');
    }

    public function create(): \Illuminate\View\View
    {
        $jenisBelanjas = JenisBelanja::all();
        return view('master-kode-rekening.create', compact('jenisBelanjas'));
    }

    public function store(Request $request): \Illuminate\Http\RedirectResponse
    {
        $validated = $request->validate([
            'kode' => 'required|unique:master_kode_rekening,kode',
            'nama' => 'required',
            'jenis_belanja_id' => 'required|exists:jenis_belanja,id',
        ]);

        MasterKodeRekening::create($validated);

        Cache::forget('master_kode_rekenings');

        return redirect()->route('master-kode-rekening.index')->with('success', 'Master Kode Rekening berhasil ditambahkan.');
    }

    public function edit(MasterKodeRekening $masterKodeRekening): \Illuminate\View\View
    {
        $jenisBelanjas = JenisBelanja::all();
        return view('master-kode-rekening.edit', compact('masterKodeRekening', 'jenisBelanjas'));
    }

    public function update(Request $request, MasterKodeRekening $masterKodeRekening): \Illuminate\Http\RedirectResponse
    {
        $validated = $request->validate([
            'kode' => 'required|unique:master_kode_rekening,kode,' . $masterKodeRekening->id,
            'nama' => 'required',
            'jenis_belanja_id' => 'required|exists:jenis_belanja,id',
        ]);

        $masterKodeRekening->update($validated);

        Cache::forget('master_kode_rekenings');

        return redirect()->route('master-kode-rekening.index')->with('success', 'Master Kode Rekening berhasil diupdate.');
    }

    public function destroy(MasterKodeRekening $masterKodeRekening): \Illuminate\Http\RedirectResponse
    {
        $masterKodeRekening->delete();
        Cache::forget('master_kode_rekenings');
        return back()->with('success', 'Master Kode Rekening berhasil dihapus.');
    }
}
