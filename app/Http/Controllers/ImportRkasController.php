<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\ImportLog;
use App\Models\TahunAnggaran;
use App\Jobs\ProcessRkasImport;

class ImportRkasController extends Controller
{
    public function index()
    {
        $tahunAnggaranAktif = TahunAnggaran::getActive();
        $logs = collect();
        if (auth()->check() && auth()->user()->sekolah_id) {
            $logs = ImportLog::where('sekolah_id', auth()->user()->sekolah_id)
                ->with('uploader')
                ->orderBy('created_at', 'desc')
                ->get();
        }
        
        return view('import-rkas.index', compact('tahunAnggaranAktif', 'logs'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'files' => 'required|array',
            'files.*' => 'nullable|file|mimes:xlsx,xls,csv',
            'sumber_dana_id' => 'required|exists:sumber_dana,id',
        ]);

        $tahunAnggaranAktif = TahunAnggaran::getActive();
        if (!$tahunAnggaranAktif) {
            return back()->with('error', 'Tahun anggaran aktif tidak ditemukan.');
        }

        if (!auth()->user()->sekolah_id) {
            return back()->with('error', 'Akun anda belum direlasikan dengan sekolah manapun.');
        }

        $uploadedCount = 0;
        $skippedFiles = [];

        foreach ($request->file('files', []) as $bulan => $file) {
            if ($file && $file->isValid()) {
                if ($file->getSize() > 5 * 1024 * 1024) {
                    $skippedFiles[] = $file->getClientOriginalName() . ' (max 5MB)';
                    continue;
                }

                $fileName = time() . '_' . $bulan . '_' . $file->getClientOriginalName();
                $filePath = $file->storeAs('import_rkas', $fileName);

                $log = ImportLog::create([
                    'sekolah_id' => auth()->user()->sekolah_id,
                    'tahun_anggaran_id' => $tahunAnggaranAktif->id,
                    'bulan' => $bulan,
                    'sumber_dana_id' => $request->sumber_dana_id,
                    'file_name' => $file->getClientOriginalName(),
                    'file_path' => $filePath,
                    'status' => 'pending',
                    'uploaded_by' => auth()->id(),
                ]);

                ProcessRkasImport::dispatch($log->id, Storage::disk('local')->path($filePath));
                $uploadedCount++;
            }
        }

        $message = $uploadedCount . ' file RKAS berhasil diupload dan sedang diproses.';
        if (!empty($skippedFiles)) {
            $message .= ' File berikut dilewati karena terlalu besar: ' . implode(', ', $skippedFiles) . '.';
        }

        if ($uploadedCount == 0) {
            return back()->with('error', 'Tidak ada file yang diproses. ' . (!empty($skippedFiles) ? 'Semua file terlalu besar (max 5MB).' : 'Tidak ada file yang dipilih/diupload.'));
        }

        return back()->with('success', $message);
    }

    public function status()
    {
        if (!auth()->user()->sekolah_id) {
            return response()->json([]);
        }

        $logs = ImportLog::where('sekolah_id', auth()->user()->sekolah_id)
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        return response()->json($logs);
    }
}
