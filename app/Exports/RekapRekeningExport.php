<?php

namespace App\Exports;

use App\Models\RkasItem;
use App\Models\TahunAnggaran;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithMapping;

class RekapRekeningExport implements FromCollection, WithHeadings, WithTitle, WithMapping
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
        $tahunAnggaranAktif = TahunAnggaran::where('status', true)->first();
        if (!$tahunAnggaranAktif) {
            return collect();
        }

        $query = RkasItem::with(['kodeRekening.jenisBelanja', 'program', 'bulanRencana', 'transaksiBkus'])
            ->where('tahun_anggaran_id', $tahunAnggaranAktif->id);

        if ($this->sekolahId) {
            $query->withoutGlobalScope('sekolah')->where('sekolah_id', $this->sekolahId);
        }

        return $query->get()
            ->map(function ($item) {
                $rencana = $item->bulanRencana->where('bulan', $this->bulan)->sum('rencana');
                $realisasi = $item->transaksiBkus->where('jenis', 'pengeluaran')
                    ->where('bulan', $this->bulan)->sum('jumlah');
                $item->rencana_bulan = $rencana;
                $item->realisasi_bulan = $realisasi;
                $item->sisa_bulan = $rencana - $realisasi;
                $item->persen = $rencana > 0 ? round(($realisasi / $rencana) * 100, 1) : 0;
                return $item;
            });
    }

    public function headings(): array
    {
        return [
            'Jenis Belanja',
            'Kode Kegiatan',
            'Program',
            'Kode Rekening',
            'Nama Rekening',
            'Rencana',
            'Realisasi',
            'Sisa',
            '%'
        ];
    }

    public function map($row): array
    {
        return [
            $row->kodeRekening?->jenisBelanja?->nama ?? '-',
            $row->program?->kode ?? '-',
            $row->program?->nama ?? '-',
            $row->kodeRekening?->kode ?? '-',
            $row->kodeRekening?->nama ?? '-',
            number_format($row->rencana_bulan ?? 0, 0, ',', '.'),
            number_format($row->realisasi_bulan ?? 0, 0, ',', '.'),
            number_format($row->sisa_bulan ?? 0, 0, ',', '.'),
            ($row->persen ?? 0) . '%',
        ];
    }

    public function title(): string
    {
        return 'Rekap Rekening Bulan ' . \Carbon\Carbon::create()->month($this->bulan)->translatedFormat('F');
    }
}
