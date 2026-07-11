<?php

namespace App\Exports;

use App\Models\TransaksiBku;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithMapping;

class BkuExport implements FromCollection, WithHeadings, WithTitle, WithMapping
{
    protected int $bulan;
    protected string $profil;
    protected ?int $sekolahId;

    public function __construct(int $bulan, string $profil, ?int $sekolahId = null)
    {
        $this->bulan = $bulan;
        $this->profil = $profil;
        $this->sekolahId = $sekolahId;
    }

    public function collection()
    {
        $query = TransaksiBku::with('rkasItem.program', 'rkasItem.kodeRekening.jenisBelanja')
            ->where('bulan', $this->bulan)
            ->orderBy('tanggal')
            ->orderBy('id');

        if ($this->sekolahId) {
            $query->withoutGlobalScope('sekolah')->where('sekolah_id', $this->sekolahId);
        }

        $transaksis = $query->get();

        $baseQuery = TransaksiBku::where('bulan', '<', $this->bulan);
        if ($this->sekolahId) {
            $baseQuery->withoutGlobalScope('sekolah')->where('sekolah_id', $this->sekolahId);
        }
        $saldoAwal = $baseQuery->get()
            ->reduce(fn($c, $t) => $c + (strtolower($t->jenis) == 'penerimaan' ? $t->jumlah : -$t->jumlah), 0);

        $saldo = $saldoAwal;
        foreach ($transaksis as $t) {
            $saldo += strtolower($t->jenis) == 'penerimaan' ? $t->jumlah : -$t->jumlah;
            $t->saldo_berjalan = $saldo;
        }

        $t = new \stdClass();
        $t->kode = '';
        $t->uraian = 'Saldo Awal';
        $t->penerimaan = '';
        $t->pengeluaran = '';
        $t->saldo = $saldoAwal;
        $t->is_header = true;
        $transaksis->prepend($t);

        $t2 = new \stdClass();
        $t2->kode = '';
        $t2->uraian = 'Saldo Akhir';
        $t2->penerimaan = $transaksis->where('jenis', 'penerimaan')->sum('jumlah') ?? 0;
        $t2->pengeluaran = $transaksis->where('jenis', 'pengeluaran')->sum('jumlah') ?? 0;
        $t2->saldo = $saldo;
        $t2->is_header = true;
        $transaksis->push($t2);

        return $transaksis;
    }

    public function headings(): array
    {
        return [
            'Tanggal',
            'No Bukti',
            'Kode Kegiatan',
            'Kode Rekening',
            'Uraian',
            'Penerimaan',
            'Pengeluaran',
            'Saldo'
        ];
    }

    public function map($row): array
    {
        if (isset($row->is_header)) {
            $label = $row->uraian;
            $saldo = is_numeric($row->saldo) ? number_format($row->saldo, 0, ',', '.') : '';
            return ['', '', '', '', $label, '', '', $saldo];
        }

        return [
            $row->tanggal ? \Carbon\Carbon::parse($row->tanggal)->format('d/m/Y') : '',
            $row->no_bukti ?? '',
            $row->rkasItem && $row->rkasItem->program ? $row->rkasItem->program->kode : '',
            $row->rkasItem && $row->rkasItem->kodeRekening ? $row->rkasItem->kodeRekening->kode : '',
            $row->uraian ?? '',
            strtolower($row->jenis) == 'penerimaan' ? number_format($row->jumlah, 0, ',', '.') : '',
            strtolower($row->jenis) == 'pengeluaran' ? number_format($row->jumlah, 0, ',', '.') : '',
            isset($row->saldo_berjalan) ? number_format($row->saldo_berjalan, 0, ',', '.') : '',
        ];
    }

    public function title(): string
    {
        return 'BKU Bulan ' . \Carbon\Carbon::create()->month($this->bulan)->translatedFormat('F');
    }
}
