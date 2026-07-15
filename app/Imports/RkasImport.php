<?php

namespace App\Imports;

use App\Models\RkasItem;
use App\Models\MasterProgram;
use App\Models\MasterKodeRekening;
use App\Models\ImportLog;
use App\Models\RkasItemBulan;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithStartRow;

class RkasImport implements ToModel, WithStartRow
{
    protected $tahunAnggaranId;
    protected $sekolahId;
    protected $bulan;
    protected $sumberDanaId;
    protected $importLogId;

    public function __construct($tahunAnggaranId, $sekolahId, $bulan, $sumberDanaId, $importLogId)
    {
        $this->tahunAnggaranId = $tahunAnggaranId;
        $this->sekolahId = $sekolahId;
        $this->bulan = $bulan;
        $this->sumberDanaId = $sumberDanaId;
        $this->importLogId = $importLogId;
    }

    public function startRow(): int
    {
        return 10;
    }

    public function model(array $row)
    {
        // $row is 0-indexed: A=0, B=1, C=2, ...
        $noUrut       = trim($row[0] ?? '');
        $kodeRekening = trim($row[1] ?? '');
        $kodeProgram  = trim($row[5] ?? '');
        $uraian       = trim($row[9] ?? '');
        $volume       = $row[14] ?? null;
        $satuan       = trim($row[16] ?? '');
        $tarif        = $row[17] ?? null;
        $jumlah       = $row[19] ?? null;

        if (!is_numeric($noUrut) || empty($uraian) || !is_numeric($jumlah)) {
            return null;
        }

        if (empty($kodeRekening)) {
            return null;
        }

        $kodeProgram = str_replace(' ', '', $kodeProgram);
        $kodeProgram = rtrim($kodeProgram, '.');

        $program = null;
        if (!empty($kodeProgram)) {
            $program = MasterProgram::where('kode', $kodeProgram . '.')->first();
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

        $parsedJumlah = $this->parseNumber($jumlah);

        $rkasItem = RkasItem::create([
            'sekolah_id'        => $this->sekolahId,
            'tahun_anggaran_id' => $this->tahunAnggaranId,
            'no_urut'           => (int) $noUrut,
            'uraian'            => $uraian,
            'program_id'        => $program->id,
            'kode_rekening_id'  => $kodeRekeningRecord->id,
            'sumber_dana_id'    => $this->sumberDanaId,
            'volume'            => $this->parseNumber($volume),
            'satuan'            => $satuan,
            'tarif'             => $this->parseNumber($tarif),
            'jumlah'            => $parsedJumlah,
        ]);

        RkasItemBulan::create([
            'rkas_item_id' => $rkasItem->id,
            'bulan'        => $this->bulan,
            'rencana'      => $parsedJumlah,
        ]);

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

    protected function parseNumber($value)
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
