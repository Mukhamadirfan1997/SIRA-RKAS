<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class RkasTemplateExport implements FromArray, WithHeadings, WithTitle
{
    /** @return array<int, array<int, string|int>> */
    public function array(): array
    {
        return [
            ['1', '5.1.02.01.01.0001', '1.1.01', 'Belanja Bahan Bangunan', 10, 'buah', 500000, 5000000],
            ['2', '5.1.02.01.01.0026', '1.1.01', 'Belanja Cetak & Fotokopi', 5, 'rim', 50000, 250000],
        ];
    }

    /** @return array<int, string> */
    public function headings(): array
    {
        return [
            'No Urut',
            'Kode Rekening',
            'Kode Program',
            'Uraian',
            'Volume',
            'Satuan',
            'Tarif',
            'Jumlah',
        ];
    }

    public function title(): string
    {
        return 'Template RKAS';
    }
}
