<?php

namespace App\Exports;

use App\Models\RkasItem;
use App\Models\TahunAnggaran;
use App\Models\TransaksiBku;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnWidths;

class RekapKuartalExport implements FromArray, WithHeadings, WithTitle, ShouldAutoSize, WithColumnWidths
{
    protected int $kuartal;
    protected string $namaSekolah;
    protected ?int $sekolahId;
    protected ?int $tahunAnggaranId;
    protected ?int $sumberDanaId;
    protected array $bulanNames;
    protected array $bulanMonths;

    public function __construct(int $kuartal, string $namaSekolah, ?int $sekolahId = null, ?int $tahunAnggaranId = null, ?int $sumberDanaId = null)
    {
        $this->kuartal = $kuartal;
        $this->namaSekolah = $namaSekolah;
        $this->sekolahId = $sekolahId;
        $this->tahunAnggaranId = $tahunAnggaranId;
        $this->sumberDanaId = $sumberDanaId;

        $startMonth = ($kuartal - 1) * 3 + 1;
        $this->bulanMonths = [$startMonth, $startMonth + 1, $startMonth + 2];
        $this->bulanNames = array_map(
            fn($m) => \Carbon\Carbon::create()->month($m)->translatedFormat('F'),
            $this->bulanMonths
        );
    }

    public function array(): array
    {
        $tahunAnggaran = $this->tahunAnggaranId
            ? TahunAnggaran::find($this->tahunAnggaranId)
            : TahunAnggaran::where('status', true)->first();
        if (!$tahunAnggaran) {
            return [[]];
        }

        $months = $this->bulanMonths;

        $cases = [];
        foreach ($months as $i => $b) {
            $cases[] = "SUM(CASE WHEN transaksi_bku.bulan = {$b} THEN transaksi_bku.jumlah ELSE 0 END) as m{$i}";
        }
        $casesSql = implode(', ', $cases);

        $realisasiSub = TransaksiBku::selectRaw("transaksi_bku.rkas_item_id, {$casesSql}, SUM(transaksi_bku.jumlah) as total_all")
            ->join('rkas_item as ri_sub', 'ri_sub.id', '=', 'transaksi_bku.rkas_item_id')
            ->where('transaksi_bku.jenis', 'pengeluaran')
            ->whereIn('transaksi_bku.bulan', $months)
            ->where('ri_sub.tahun_anggaran_id', $tahunAnggaran->id)
            ->when($this->sekolahId, fn($q) => $q->where('ri_sub.sekolah_id', $this->sekolahId))
            ->when($this->sumberDanaId, fn($q) => $q->where('ri_sub.sumber_dana_id', $this->sumberDanaId))
            ->groupBy('transaksi_bku.rkas_item_id');

        $query = RkasItem::with(['kodeRekening.jenisBelanja', 'program'])
            ->select('rkas_item.*')
            ->leftJoinSub($realisasiSub, 'tb', fn($j) => $j->on('rkas_item.id', '=', 'tb.rkas_item_id'))
            ->where('rkas_item.tahun_anggaran_id', $tahunAnggaran->id);

        if ($this->sekolahId) {
            $query->withoutGlobalScope('sekolah')->where('rkas_item.sekolah_id', $this->sekolahId);
        }

        if ($this->sumberDanaId) {
            $query->where('rkas_item.sumber_dana_id', $this->sumberDanaId);
        }

        $rkasItems = $query->get()
            ->map(function ($item) use ($months) {
                $realisasiPerBulan = [];
                $totalRealisasi = 0;
                foreach ($months as $i => $bulan) {
                    $r = (float) ($item->{"m{$i}"} ?? 0);
                    $realisasiPerBulan[$bulan] = $r;
                    $totalRealisasi += $r;
                }
                $item->realisasi_per_bulan = $realisasiPerBulan;
                $item->total_realisasi = $totalRealisasi;
                return $item;
            });

        $grouped = $rkasItems->groupBy(
            fn($item) => $item->kodeRekening?->jenisBelanja?->nama ?? 'Tidak Terkategori'
        );

        $rows = [];

        $periodeLabel = implode(' s.d. ', $this->bulanNames);
        $tahun = $tahunAnggaran->tahun ?? '-';

        $rows[] = [$this->namaSekolah, '', '', '', '', '', ''];
        $rows[] = ['Rekap Realisasi Anggaran Per Kode Rekening', '', '', '', '', '', ''];
        $rows[] = ['Tribulan ' . $this->kuartal . ' (' . $periodeLabel . ') ' . $tahun, '', '', '', '', '', ''];
        $rows[] = ['', '', '', '', '', '', ''];

        $no = 1;
        $grandTotalPerBulan = array_fill_keys($months, 0);
        $grandTotalAll = 0;

        $groupLabels = range('A', 'Z');

        foreach ($grouped as $jenisBelanja => $items) {
            $groupIdx = array_search($jenisBelanja, array_keys($grouped->toArray()));
            $groupPrefix = ($groupIdx !== false && $groupIdx < 26) ? $groupLabels[$groupIdx] . '. ' : '';

            $rows[] = [$groupPrefix . strtoupper($jenisBelanja), '', '', '', '', '', ''];

            $subTotalPerBulan = array_fill_keys($months, 0);
            $subTotalAll = 0;

            foreach ($items->sortBy('kodeRekening.kode') as $item) {
                $row = [
                    $no,
                    $item->kodeRekening?->kode ?? '-',
                    $item->uraian,
                ];
                foreach ($months as $bulan) {
                    $row[] = $item->realisasi_per_bulan[$bulan] ?? 0;
                }
                $row[] = $item->total_realisasi;
                $rows[] = $row;

                foreach ($months as $bulan) {
                    $subTotalPerBulan[$bulan] += $item->realisasi_per_bulan[$bulan] ?? 0;
                }
                $subTotalAll += $item->total_realisasi;
                $no++;
            }

            $subRow = ['', '', 'SUBTOTAL ' . strtoupper($jenisBelanja)];
            foreach ($months as $bulan) {
                $subRow[] = $subTotalPerBulan[$bulan];
            }
            $subRow[] = $subTotalAll;
            $rows[] = $subRow;

            foreach ($months as $bulan) {
                $grandTotalPerBulan[$bulan] += $subTotalPerBulan[$bulan];
            }
            $grandTotalAll += $subTotalAll;

            $rows[] = ['', '', '', '', '', '', ''];
        }

        $gtRow = ['', '', 'TOTAL KESELURUHAN'];
        foreach ($months as $bulan) {
            $gtRow[] = $grandTotalPerBulan[$bulan];
        }
        $gtRow[] = $grandTotalAll;
        $rows[] = $gtRow;

        return $rows;
    }

    public function headings(): array
    {
        $cols = ['No', 'Kode Rekening', 'Uraian Anggaran'];
        foreach ($this->bulanNames as $name) {
            $cols[] = 'Realisasi ' . $name;
        }
        $cols[] = 'Total Tribulan ' . $this->kuartal;
        return $cols;
    }

    public function title(): string
    {
        return 'Rekap Tribulan ' . $this->kuartal;
    }

    public function columnWidths(): array
    {
        return [
            1 => 5,
            2 => 18,
            3 => 35,
            4 => 18,
            5 => 18,
            6 => 18,
            7 => 18,
        ];
    }
}
