<?php

namespace App\Exports;

use App\Models\TransaksiBku;
use App\Models\TahunAnggaran;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithMapping;

class RekapSiplahExport implements FromCollection, WithHeadings, WithTitle, WithMapping
{
    protected array $months;
    protected ?int $sekolahId;
    protected string $periodeLabel;
    protected ?int $tahunAnggaranId;
    protected ?int $sumberDanaId;

    public function __construct(array $months, ?int $sekolahId = null, string $periodeLabel = '', ?int $tahunAnggaranId = null, ?int $sumberDanaId = null)
    {
        $this->months = $months;
        $this->sekolahId = $sekolahId;
        $this->periodeLabel = $periodeLabel;
        $this->tahunAnggaranId = $tahunAnggaranId ?? \App\Models\TahunAnggaran::where('status', true)->value('id');
        $this->sumberDanaId = $sumberDanaId;
    }

    public function collection()
    {
        $query = TransaksiBku::where('transaksi_bku.tahun_anggaran_id', $this->tahunAnggaranId)
            ->where('jenis', 'pengeluaran')
            ->whereIn('bulan', $this->months)
            ->when($this->sumberDanaId, fn($q) => $q->where('transaksi_bku.sumber_dana_id', $this->sumberDanaId));

        if ($this->sekolahId) {
            $query->withoutGlobalScope('sekolah')->where('sekolah_id', $this->sekolahId);
        }

        $rows = $query
            ->join('rkas_item', 'rkas_item.id', '=', 'transaksi_bku.rkas_item_id')
            ->join('master_kode_rekening', 'master_kode_rekening.id', '=', 'rkas_item.kode_rekening_id')
            ->join('jenis_belanja', 'jenis_belanja.id', '=', 'master_kode_rekening.jenis_belanja_id')
            ->selectRaw("
                COALESCE(jenis_belanja.nama, 'Tidak Terkategori') as jenis_belanja,
                COALESCE(SUM(transaksi_bku.jumlah), 0) as total,
                COALESCE(SUM(CASE WHEN transaksi_bku.metode_pengadaan = 'siplah' THEN transaksi_bku.jumlah ELSE 0 END), 0) as siplah,
                COALESCE(SUM(CASE WHEN transaksi_bku.metode_pengadaan = 'non_siplah' THEN transaksi_bku.jumlah ELSE 0 END), 0) as non_siplah
            ")
            ->groupBy('jenis_belanja.nama')
            ->orderBy('jenis_belanja.nama')
            ->get()
            ->map(function ($row) {
                $total = (float) $row->total;
                $siplah = (float) $row->siplah;
                $nonSiplah = (float) $row->non_siplah;
                $belumDiisi = $total - $siplah - $nonSiplah;
                return (object) [
                    'jenis_belanja' => $row->jenis_belanja,
                    'total' => $total,
                    'siplah' => $siplah,
                    'non_siplah' => $nonSiplah,
                    'belum_diisi' => max(0, $belumDiisi),
                    'persen_siplah' => $total > 0 ? round(($siplah / $total) * 100, 1) : 0,
                    'persen_non_siplah' => $total > 0 ? round(($nonSiplah / $total) * 100, 1) : 0,
                ];
            });

        return $rows;
    }

    public function headings(): array
    {
        return [
            'Jenis Belanja',
            'Total Pengeluaran',
            'SIPLAH',
            'Non-SIPLAH',
            'Belum Diisi',
            '% SIPLAH',
            '% Non-SIPLAH',
        ];
    }

    public function map($row): array
    {
        return [
            $row->jenis_belanja,
            number_format($row->total, 0, ',', '.'),
            number_format($row->siplah, 0, ',', '.'),
            number_format($row->non_siplah, 0, ',', '.'),
            number_format($row->belum_diisi, 0, ',', '.'),
            $row->persen_siplah . '%',
            $row->persen_non_siplah . '%',
        ];
    }

    public function title(): string
    {
        return 'Rekap SIPLAH ' . ($this->periodeLabel ?: 'Periode');
    }
}
