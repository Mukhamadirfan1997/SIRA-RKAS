<?php

namespace Tests\Feature\Export;

use App\Exports\MasterKodeRekeningTemplateExport;
use App\Exports\RkasTemplateExport;
use Tests\TestCase;

class ExportTemplateTest extends TestCase
{
    public function test_master_kode_rekening_template_headings(): void
    {
        $export = new MasterKodeRekeningTemplateExport;

        $this->assertEquals(['Kode Rekening', 'Nama Rekening'], $export->headings());
    }

    public function test_master_kode_rekening_template_array(): void
    {
        $export = new MasterKodeRekeningTemplateExport;

        $data = $export->array();
        $this->assertCount(2, $data);
        $this->assertEquals(['5.1.02.01.01.0001', 'Belanja Bahan Bangunan'], $data[0]);
        $this->assertEquals(['5.1.02.01.01.0026', 'Belanja Cetak & Fotokopi'], $data[1]);
    }

    public function test_master_kode_rekening_template_title(): void
    {
        $export = new MasterKodeRekeningTemplateExport;

        $this->assertEquals('Master Kode Rekening', $export->title());
    }

    public function test_rkas_template_headings(): void
    {
        $export = new RkasTemplateExport;

        $this->assertEquals(
            ['No Urut', 'Kode Rekening', 'Kode Program', 'Uraian', 'Volume', 'Satuan', 'Tarif', 'Jumlah'],
            $export->headings()
        );
    }

    public function test_rkas_template_array(): void
    {
        $export = new RkasTemplateExport;

        $data = $export->array();
        $this->assertCount(2, $data);
        $this->assertEquals(['1', '5.1.02.01.01.0001', '1.1.01', 'Belanja Bahan Bangunan', 10, 'buah', 500000, 5000000], $data[0]);
        $this->assertEquals(['2', '5.1.02.01.01.0026', '1.1.01', 'Belanja Cetak & Fotokopi', 5, 'rim', 50000, 250000], $data[1]);
    }

    public function test_rkas_template_title(): void
    {
        $export = new RkasTemplateExport;

        $this->assertEquals('Template RKAS', $export->title());
    }
}
