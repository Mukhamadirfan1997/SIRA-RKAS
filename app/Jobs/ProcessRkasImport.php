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
use App\Models\AuditLog;
use App\Observers\RkasItemObserver;
use Illuminate\Support\Facades\Log;

class ProcessRkasImport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $importLogId;
    public string $filePath;

    public function __construct(int $importLogId, string $filePath)
    {
        $this->importLogId = $importLogId;
        $this->filePath = $filePath;
    }

    public function handle(): void
    {
        $log = ImportLog::find($this->importLogId);
        if (!$log) return;

        $log->update(['status' => 'processing']);

        try {
            // Idempotensi: hapus Rencana bulan yang diimpor saja
            DB::transaction(function () use ($log) {
                $rkasIds = RkasItem::where('sekolah_id', $log->sekolah_id)
                                    ->where('tahun_anggaran_id', $log->tahun_anggaran_id)
                                    ->pluck('id');

                if ($rkasIds->isNotEmpty()) {
                    \App\Models\RkasItemBulan::whereIn('rkas_item_id', $rkasIds)
                                             ->where('bulan', $log->bulan)
                                             ->delete();
                }
            });

            // Set user context untuk observer audit
            RkasItemObserver::$importUserId = $log->uploaded_by;

            try {
                // Jalankan import
                Excel::import(new RkasImport(
                    $log->tahun_anggaran_id,
                    $log->sekolah_id,
                    $log->bulan,
                    $log->sumber_dana_id,
                    $log->id
                ), $this->filePath);
            } finally {
                // Reset user context (harus tetap di-reset walau exception)
                RkasItemObserver::$importUserId = null;
            }

            $log->refresh();

            if ($log->baris_berhasil === 0) {
                $err = $log->error_detail ?? [];
                $err[] = "Tidak ada data yang berhasil diimpor. Periksa format file Excel — pastikan kolom sesuai template (No Urut, Kode Rekening, Kode Program, Uraian, Volume, Satuan, Tarif, Jumlah).";
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

                AuditLog::create([
                    'user_id' => $log->uploaded_by,
                    'sekolah_id' => $log->sekolah_id,
                    'tabel' => 'import_rkas',
                    'aksi' => 'import',
                    'data_baru' => [
                        'bulan' => $log->bulan,
                        'baris_berhasil' => $log->baris_berhasil,
                        'total_baris' => $log->total_baris,
                    ],
                ]);
            }

            $this->cleanupFile($log);

        } catch (\Exception $e) {
            Log::error("Import gagal: " . $e->getMessage());
            $err = $log->error_detail ?? [];
            $err[] = "System Error: " . $e->getMessage();
            $log->update([
                'status' => 'failed',
                'finished_at' => now(),
            ]);

            $this->cleanupFile($log);
        }
    }

    protected function cleanupFile(?ImportLog $log): void
    {
        if ($log->file_path) {
            Storage::disk('local')->delete($log->file_path);
            $log->updateQuietly(['file_path' => null]);
        }
    }
}
