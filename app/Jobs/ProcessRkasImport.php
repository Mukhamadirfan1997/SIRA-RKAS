<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\ImportLog;
use App\Imports\RkasImport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Models\RkasItem;
use Illuminate\Support\Facades\Log;

class ProcessRkasImport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $importLogId;
    public $filePath;

    /**
     * Create a new job instance.
     */
    public function __construct($importLogId, $filePath)
    {
        $this->importLogId = $importLogId;
        $this->filePath = $filePath;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $log = ImportLog::find($this->importLogId);
        if (!$log) return;

        $log->update(['status' => 'processing']);

        try {
            // ===== IDEMPOTENSI: Hapus data lama untuk sekolah + tahun + bulan ini =====
            // Ini memastikan re-import bulan yang sama tidak menumpuk duplikat
            DB::transaction(function () use ($log) {
                // Cari rkas_item milik sekolah + tahun ini
                $rkasIds = RkasItem::where('sekolah_id', $log->sekolah_id)
                                    ->where('tahun_anggaran_id', $log->tahun_anggaran_id)
                                    ->pluck('id');

                if ($rkasIds->isNotEmpty()) {
                    // Hapus rencana bulan lama untuk bulan yang diimpor
                    \App\Models\RkasItemBulan::whereIn('rkas_item_id', $rkasIds)
                                             ->where('bulan', $log->bulan)
                                             ->delete();

                    // Hapus rkas_item yang tidak lagi punya rencana di bulan MANAPUN
                    // (artinya item ini hanya eksis karena import bulan ini sebelumnya)
                    $itemsToDelete = RkasItem::whereIn('id', $rkasIds)
                                             ->whereDoesntHave('bulanRencana')
                                             ->pluck('id');
                    if ($itemsToDelete->isNotEmpty()) {
                        RkasItem::whereIn('id', $itemsToDelete)->delete();
                    }
                }
            });

            // Mulai import
            Excel::import(new RkasImport(
                $log->tahun_anggaran_id,
                $log->sekolah_id,
                $log->bulan,
                $log->sumber_dana_id,
                $log->id
            ), $this->filePath);

            $log->refresh();

            if ($log->baris_berhasil === 0) {
                $err = $log->error_detail ?? [];
                $err[] = "Tidak ada data yang berhasil diimpor. Periksa format file Excel — pastikan kolom sesuai template (No Urut di kolom A, Kode Rekening di kolom B, Uraian di kolom J, Jumlah di kolom T).";
                $log->update([
                    'status' => 'failed',
                    'error_detail' => $err,
                    'total_baris' => $log->baris_gagal,
                    'finished_at' => now(),
                ]);
                Log::error("Import gagal: 0 baris berhasil — format file tidak sesuai template untuk bulan " . $log->bulan . " sekolah " . $log->sekolah_id);
            } else {
                $log->update([
                    'status' => 'success',
                    'total_baris' => $log->baris_berhasil + $log->baris_gagal,
                    'finished_at' => now(),
                ]);
            }

            // Hapus file asli setelah selesai
            if ($log->file_path) {
                Storage::disk('local')->delete($log->file_path);
                $log->update(['file_path' => null]);
            }

        } catch (\Exception $e) {
            Log::error("Import gagal: " . $e->getMessage());
            $err = $log->error_detail ?? [];
            $err[] = "System Error: " . $e->getMessage();
            $log->update([
                'status' => 'failed',
                'error_detail' => $err,
                'finished_at' => now(),
            ]);
        }
    }
}
