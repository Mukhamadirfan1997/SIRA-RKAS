<?php

namespace App\Imports;

use App\Models\RkasItem;
use App\Models\MasterProgram;
use App\Models\MasterKodeRekening;
use App\Models\ImportLog;
use App\Models\RkasItemBulan;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithChunkReading;

class RkasImport implements ToModel, WithStartRow, WithHeadingRow, WithChunkReading
{
    protected int $tahunAnggaranId;
    protected int $sekolahId;
    protected int $bulan;
    protected int $sumberDanaId;
    protected int $importLogId;

    public function __construct(int $tahunAnggaranId, int $sekolahId, int $bulan, int $sumberDanaId, int $importLogId)
    {
        $this->tahunAnggaranId = $tahunAnggaranId;
        $this->sekolahId = $sekolahId;
        $this->bulan = $bulan;
        $this->sumberDanaId = $sumberDanaId;
        $this->importLogId = $importLogId;
    }

    public function startRow(): int
    {
        return 2;
    }

    public function headingRow(): int
    {
        return 1;
    }

    public function chunkSize(): int
    {
        return 100;
    }

    /**
     * @param array<string, mixed> $row
     */
    public function model(array $row)
    {
        $noUrut       = trim($row['no_urut'] ?? '');
        $kodeRekening = trim($row['kode_rekening'] ?? '');
        $kodeProgram  = trim($row['kode_program'] ?? '');
        $uraian       = trim($row['uraian'] ?? '');
        $volume       = $row['volume'] ?? null;
        $satuan       = trim($row['satuan'] ?? '');
        $tarif        = $row['tarif'] ?? null;
        $jumlah       = $row['jumlah'] ?? null;

        if (!is_numeric($noUrut) || empty($uraian) || !is_numeric($jumlah)) {
            return null;
        }

        $parsedJumlah = $this->parseNumber($jumlah);
        if ($parsedJumlah < 0) {
            $this->logError("No. Urut $noUrut: Jumlah tidak boleh negatif ($parsedJumlah)");
            return null;
        }

        if (empty($kodeRekening)) {
            return null;
        }

        $kodeProgram = str_replace(' ', '', $kodeProgram);

        $program = null;
        if (!empty($kodeProgram)) {
            $program = MasterProgram::where('kode', $kodeProgram)->first();
        }

        if (!$program) {
            $this->logError("No. Urut $noUrut: Program tidak ditemukan ($kodeProgram)");
            return null;
        }

        $kodeRekeningRecord = MasterKodeRekening::where('kode', rtrim($kodeRekening, '.'))->first();

        if (!$kodeRekeningRecord) {
            $this->logError("No. Urut $noUrut: Kode rekening tidak ditemukan ($kodeRekening)");
            return null;
        }

        $parsedVolume = $this->parseNumber($volume);
        if ($parsedVolume < 0) {
            $this->logError("No. Urut $noUrut: Volume tidak boleh negatif");
            return null;
        }

        $parsedTarif = $this->parseNumber($tarif);
        if ($parsedTarif < 0) {
            $this->logError("No. Urut $noUrut: Tarif tidak boleh negatif");
            return null;
        }

        $rkasItem = RkasItem::updateOrCreate(
            [
                'sekolah_id'        => $this->sekolahId,
                'tahun_anggaran_id' => $this->tahunAnggaranId,
                'no_urut'           => (int) $noUrut,
                'sumber_dana_id'    => $this->sumberDanaId,
            ],
            [
                'uraian'            => $uraian,
                'program_id'        => $program->id,
                'kode_rekening_id'  => $kodeRekeningRecord->id,
                'volume'            => $parsedVolume,
                'satuan'            => $satuan,
                'tarif'             => $parsedTarif,
                'jumlah'            => $parsedJumlah,
            ]
        );

        RkasItemBulan::updateOrCreate(
            [
                'rkas_item_id' => $rkasItem->id,
                'bulan'        => $this->bulan,
            ],
            [
                'rencana'      => $parsedJumlah,
            ]
        );

        $this->incrementBerhasil();

        return null;
    }

    protected function logError(string $message): void
    {
        if (!$this->importLogId) return;
        $log = ImportLog::withoutGlobalScope('sekolah')->find($this->importLogId);
        if ($log) {
            $log->increment('baris_gagal');
            $errs = $log->error_detail ?? [];
            $errs[] = $message;
            $log->error_detail = $errs;
            $log->save();
        }
    }

    protected function incrementBerhasil(): void
    {
        if (!$this->importLogId) return;
        ImportLog::withoutGlobalScope('sekolah')->where('id', $this->importLogId)
            ->increment('baris_berhasil');
    }

    protected function parseNumber(mixed $value): float|int
    {
        if ($value === null || $value === '') return 0;
        if (is_numeric($value)) return (float) $value;
        $cleaned = preg_replace('/[^0-9\,\.]/', '', $value);
        if (substr_count($cleaned, ',') === 1 && substr_count($cleaned, '.') >= 1) {
            $cleaned = str_replace('.', '', $cleaned);
            $cleaned = str_replace(',', '.', $cleaned);
        } elseif (substr_count($cleaned, '.') > 1) {
            $cleaned = str_replace('.', '', $cleaned);
        } else {
            $cleaned = str_replace(',', '.', $cleaned);
        }
        return is_numeric($cleaned) ? (float) $cleaned : 0;
    }
}
