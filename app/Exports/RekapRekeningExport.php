<?php

namespace App\Exports;

use App\Models\RkasItem;
use App\Models\RkasItemBulan;
use App\Models\TahunAnggaran;
use App\Models\TransaksiBku;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithMapping;

class RekapRekeningExport implements FromCollection, WithHeadings, WithTitle, WithMapping
{
    protected int $bulan;
    protected ?int $sekolahId;
    protected ?int $tahunAnggaranId;
    protected ?int $sumberDanaId;

    public function __construct(int $bulan, ?int $sekolahId = null, ?int $tahunAnggaranId = null, ?int $sumberDanaId = null)
    {
        $this->bulan = $bulan;
        $this->sekolahId = $sekolahId;
        $this->tahunAnggaranId = $tahunAnggaranId;
        $this->sumberDanaId = $sumberDanaId;
    }

    public function collection()
    {
        $tahunAnggaranAktif = $this->tahunAnggaranId
            ? TahunAnggaran::find($this->tahunAnggaranId)
            : TahunAnggaran::where('status', true)->first();
        if (!$tahunAnggaranAktif) {
            return collect();
        }

        $query = RkasItem::with(['kodeRekening.jenisBelanja', 'program'])
            ->where('tahun_anggaran_id', $tahunAnggaranAktif->id);

        if ($this->sekolahId) {
            $query->withoutGlobalScope('sekolah')->where('sekolah_id', $this->sekolahId);
        }

        if ($this->sumberDanaId) {
            $query->where('sumber_dana_id', $this->sumberDanaId);
        }

        $itemSub = (clone $query)->select('id');

        $rencanaPerItem = RkasItemBulan::joinSub($itemSub, 'ri_filtered', fn($j) => $j->on('rkas_item_bulan.rkas_item_id', '=', 'ri_filtered.id'))
            ->where('rkas_item_bulan.bulan', $this->bulan)
            ->selectRaw('rkas_item_bulan.rkas_item_id, SUM(rkas_item_bulan.rencana) as total')
            ->groupBy('rkas_item_bulan.rkas_item_id')
            ->pluck('total', 'rkas_item_id');

        $realisasiPerItem = TransaksiBku::joinSub($itemSub, 'ri_filtered', fn($j) => $j->on('transaksi_bku.rkas_item_id', '=', 'ri_filtered.id'))
            ->where('transaksi_bku.jenis', 'pengeluaran')
            ->where('transaksi_bku.bulan', $this->bulan)
            ->selectRaw('transaksi_bku.rkas_item_id, SUM(transaksi_bku.jumlah) as total')
            ->groupBy('transaksi_bku.rkas_item_id')
            ->pluck('total', 'rkas_item_id');

        return $query->get()
            ->map(function ($item) use ($rencanaPerItem, $realisasiPerItem) {
                $rencana = (float) ($rencanaPerItem[$item->id] ?? 0);
                $realisasi = (float) ($realisasiPerItem[$item->id] ?? 0);
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
