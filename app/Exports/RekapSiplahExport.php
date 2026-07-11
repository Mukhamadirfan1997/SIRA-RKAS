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
    protected int $bulan;
    protected ?int $sekolahId;

    public function __construct(int $bulan, ?int $sekolahId = null)
    {
        $this->bulan = $bulan;
        $this->sekolahId = $sekolahId;
    }

    public function collection()
    {
        $query = TransaksiBku::with('rkasItem.kodeRekening.jenisBelanja')
            ->where('jenis', 'pengeluaran')
            ->where('bulan', $this->bulan);

        if ($this->sekolahId) {
            $query->withoutGlobalScope('sekolah')->where('sekolah_id', $this->sekolahId);
        }

        $transaksis = $query->get();

        $grouped = $transaksis->groupBy(function ($t) {
            return $t->rkasItem?->kodeRekening?->jenisBelanja?->nama ?? 'Tidak Terkategori';
        });

        $rows = collect();
        foreach ($grouped as $jenisBelanja => $items) {
            $total = $items->sum('jumlah');
            $siplah = $items->where('metode_pengadaan', 'siplah')->sum('jumlah');
            $nonSiplah = $items->where('metode_pengadaan', 'non_siplah')->sum('jumlah');
            $belumDiisi = $total - $siplah - $nonSiplah;

            $rows->push((object) [
                'jenis_belanja' => $jenisBelanja,
                'total' => $total,
                'siplah' => $siplah,
                'non_siplah' => $nonSiplah,
                'belum_diisi' => $belumDiisi,
                'persen_siplah' => $total > 0 ? round(($siplah / $total) * 100, 1) : 0,
                'persen_non_siplah' => $total > 0 ? round(($nonSiplah / $total) * 100, 1) : 0,
            ]);
        }

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
        return 'Rekap SIPLAH ' . \Carbon\Carbon::create()->month($this->bulan)->translatedFormat('F');
    }
}
