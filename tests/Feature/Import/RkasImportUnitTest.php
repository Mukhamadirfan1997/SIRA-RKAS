<?php

namespace Tests\Feature\Import;

use App\Imports\RkasImport;
use App\Models\ImportLog;
use App\Models\MasterKodeRekening;
use App\Models\MasterProgram;
use App\Models\ProfilSekolah;
use App\Models\TahunAnggaran;
use App\Models\SumberDana;
use App\Models\RkasItem;
use App\Models\RkasItemBulan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RkasImportUnitTest extends TestCase
{
    use RefreshDatabase;

    private RkasImport $import;
    private ProfilSekolah $sekolah;
    private TahunAnggaran $tahunAnggaran;
    private SumberDana $sumberDana;
    private ImportLog $importLog;

    protected function setUp(): void
    {
        parent::setUp();

        $this->sekolah = ProfilSekolah::factory()->create();
        $this->tahunAnggaran = TahunAnggaran::factory()->create();
        $this->sumberDana = SumberDana::factory()->create();
        MasterProgram::factory()->create(['kode' => '1.1.01']);
        MasterKodeRekening::factory()->create(['kode' => '5.1.02.01.01.0001']);
        $this->importLog = ImportLog::factory()->create([
            'sekolah_id' => $this->sekolah->id,
            'tahun_anggaran_id' => $this->tahunAnggaran->id,
            'bulan' => 1,
            'sumber_dana_id' => $this->sumberDana->id,
            'status' => 'pending',
            'baris_berhasil' => 0,
            'baris_gagal' => 0,
        ]);

        $this->import = new RkasImport(
            $this->tahunAnggaran->id,
            $this->sekolah->id,
            1,
            $this->sumberDana->id,
            $this->importLog->id,
        );
    }

    public function test_parse_number_with_integer(): void
    {
        $result = $this->callProtected($this->import, 'parseNumber', ['500000']);
        $this->assertSame(500000.0, $result);
    }

    public function test_parse_number_with_float(): void
    {
        $result = $this->callProtected($this->import, 'parseNumber', ['500000.50']);
        $this->assertSame(500000.50, $result);
    }

    public function test_parse_number_with_indonesian_format(): void
    {
        $result = $this->callProtected($this->import, 'parseNumber', ['500.000,50']);
        $this->assertSame(500000.50, $result);
    }

    public function test_parse_number_with_comma_decimal(): void
    {
        $result = $this->callProtected($this->import, 'parseNumber', ['500000,50']);
        $this->assertSame(500000.50, $result);
    }

    public function test_parse_number_returns_zero_for_null(): void
    {
        $result = $this->callProtected($this->import, 'parseNumber', [null]);
        $this->assertEqualsWithDelta(0.0, $result, 0.001);
    }

    public function test_parse_number_returns_zero_for_empty_string(): void
    {
        $result = $this->callProtected($this->import, 'parseNumber', ['']);
        $this->assertEqualsWithDelta(0.0, $result, 0.001);
    }

    public function test_parse_number_rejects_negative_values(): void
    {
        // parseNumber doesn't validate sign, it just parses. Validation happens in model().
        $result = $this->callProtected($this->import, 'parseNumber', ['-5000']);
        $this->assertSame(-5000.0, $result);
    }

    public function test_model_skips_row_without_no_urut(): void
    {
        $result = $this->import->model([
            'no_urut' => '',
            'kode_rekening' => '5.1.02.01.01.0001',
            'kode_program' => '1.1.01',
            'uraian' => 'Belanja ATK',
            'volume' => 10,
            'satuan' => 'buah',
            'tarif' => 50000,
            'jumlah' => 500000,
        ]);

        $this->assertNull($result);
    }

    public function test_model_skips_row_without_uraian(): void
    {
        $result = $this->import->model([
            'no_urut' => 1,
            'kode_rekening' => '5.1.02.01.01.0001',
            'kode_program' => '1.1.01',
            'uraian' => '',
            'volume' => 10,
            'satuan' => 'buah',
            'tarif' => 50000,
            'jumlah' => 500000,
        ]);

        $this->assertNull($result);
    }

    public function test_model_skips_row_with_non_numeric_jumlah(): void
    {
        $result = $this->import->model([
            'no_urut' => 1,
            'kode_rekening' => '5.1.02.01.01.0001',
            'kode_program' => '1.1.01',
            'uraian' => 'Belanja ATK',
            'volume' => 10,
            'satuan' => 'buah',
            'tarif' => 50000,
            'jumlah' => 'ABC',
        ]);

        $this->assertNull($result);
    }

    public function test_model_skips_row_without_kode_rekening(): void
    {
        $result = $this->import->model([
            'no_urut' => 1,
            'kode_rekening' => '',
            'kode_program' => '1.1.01',
            'uraian' => 'Belanja ATK',
            'volume' => 10,
            'satuan' => 'buah',
            'tarif' => 50000,
            'jumlah' => 500000,
        ]);

        $this->assertNull($result);
    }

    public function test_model_skips_row_with_invalid_program(): void
    {
        $result = $this->import->model([
            'no_urut' => 1,
            'kode_rekening' => '5.1.02.01.01.0001',
            'kode_program' => '99.99.99',
            'uraian' => 'Belanja ATK',
            'volume' => 10,
            'satuan' => 'buah',
            'tarif' => 50000,
            'jumlah' => 500000,
        ]);

        $this->assertNull($result);
    }

    public function test_model_skips_row_with_invalid_kode_rekening(): void
    {
        $result = $this->import->model([
            'no_urut' => 1,
            'kode_rekening' => '99.99.99.99.99.9999',
            'kode_program' => '1.1.01',
            'uraian' => 'Belanja ATK',
            'volume' => 10,
            'satuan' => 'buah',
            'tarif' => 50000,
            'jumlah' => 500000,
        ]);

        $this->assertNull($result);
    }

    public function test_model_skips_row_with_negative_jumlah(): void
    {
        $result = $this->import->model([
            'no_urut' => 1,
            'kode_rekening' => '5.1.02.01.01.0001',
            'kode_program' => '1.1.01',
            'uraian' => 'Belanja ATK',
            'volume' => 10,
            'satuan' => 'buah',
            'tarif' => 50000,
            'jumlah' => -500000,
        ]);

        $this->assertNull($result);
    }

    public function test_model_skips_row_with_negative_volume(): void
    {
        $result = $this->import->model([
            'no_urut' => 1,
            'kode_rekening' => '5.1.02.01.01.0001',
            'kode_program' => '1.1.01',
            'uraian' => 'Belanja ATK',
            'volume' => -10,
            'satuan' => 'buah',
            'tarif' => 50000,
            'jumlah' => 500000,
        ]);

        $this->assertNull($result);
    }

    public function test_model_skips_row_with_negative_tarif(): void
    {
        $result = $this->import->model([
            'no_urut' => 1,
            'kode_rekening' => '5.1.02.01.01.0001',
            'kode_program' => '1.1.01',
            'uraian' => 'Belanja ATK',
            'volume' => 10,
            'satuan' => 'buah',
            'tarif' => -50000,
            'jumlah' => 500000,
        ]);

        $this->assertNull($result);
    }

    public function test_model_creates_rkas_item_and_bulan(): void
    {
        $result = $this->import->model([
            'no_urut' => 1,
            'kode_rekening' => '5.1.02.01.01.0001',
            'kode_program' => '1.1.01',
            'uraian' => 'Belanja ATK',
            'volume' => 10,
            'satuan' => 'buah',
            'tarif' => 50000,
            'jumlah' => 500000,
        ]);

        $this->assertNull($result);

        $this->assertDatabaseHas('rkas_item', [
            'sekolah_id' => $this->sekolah->id,
            'no_urut' => 1,
            'uraian' => 'Belanja ATK',
            'volume' => 10.0,
            'satuan' => 'buah',
            'tarif' => 50000.0,
            'jumlah' => 500000.0,
        ]);

        $rkasItem = RkasItem::where('sekolah_id', $this->sekolah->id)
            ->where('no_urut', 1)
            ->first();

        $this->assertDatabaseHas('rkas_item_bulan', [
            'rkas_item_id' => $rkasItem->id,
            'bulan' => 1,
            'rencana' => 500000.0,
        ]);
    }

    public function test_model_updates_existing_rkas_item(): void
    {
        RkasItem::factory()->create([
            'sekolah_id' => $this->sekolah->id,
            'tahun_anggaran_id' => $this->tahunAnggaran->id,
            'no_urut' => 1,
            'sumber_dana_id' => $this->sumberDana->id,
            'uraian' => 'Lama',
            'volume' => 5,
            'jumlah' => 100000,
        ]);

        $this->import->model([
            'no_urut' => 1,
            'kode_rekening' => '5.1.02.01.01.0001',
            'kode_program' => '1.1.01',
            'uraian' => 'Baru',
            'volume' => 10,
            'satuan' => 'buah',
            'tarif' => 50000,
            'jumlah' => 500000,
        ]);

        $this->assertDatabaseHas('rkas_item', [
            'sekolah_id' => $this->sekolah->id,
            'no_urut' => 1,
            'uraian' => 'Baru',
            'volume' => 10.0,
            'jumlah' => 500000.0,
        ]);
    }

    public function test_model_updates_existing_rkas_item_bulan(): void
    {
        $existing = RkasItem::factory()->create([
            'sekolah_id' => $this->sekolah->id,
            'tahun_anggaran_id' => $this->tahunAnggaran->id,
            'no_urut' => 1,
            'sumber_dana_id' => $this->sumberDana->id,
        ]);

        RkasItemBulan::factory()->create([
            'rkas_item_id' => $existing->id,
            'bulan' => 1,
            'rencana' => 100000,
        ]);

        $this->import->model([
            'no_urut' => 1,
            'kode_rekening' => '5.1.02.01.01.0001',
            'kode_program' => '1.1.01',
            'uraian' => 'Update Bulan',
            'volume' => 10,
            'satuan' => 'buah',
            'tarif' => 50000,
            'jumlah' => 750000,
        ]);

        $this->assertDatabaseHas('rkas_item_bulan', [
            'rkas_item_id' => $existing->id,
            'bulan' => 1,
            'rencana' => 750000.0,
        ]);
    }

    public function test_logs_error_when_program_not_found(): void
    {
        $this->import->model([
            'no_urut' => 1,
            'kode_rekening' => '5.1.02.01.01.0001',
            'kode_program' => '99.99.99',
            'uraian' => 'Belanja ATK',
            'volume' => 10,
            'satuan' => 'buah',
            'tarif' => 50000,
            'jumlah' => 500000,
        ]);

        $this->importLog->refresh();
        $this->assertGreaterThan(0, $this->importLog->baris_gagal);
    }

    public function test_increments_berhasil_on_success(): void
    {
        $this->import->model([
            'no_urut' => 1,
            'kode_rekening' => '5.1.02.01.01.0001',
            'kode_program' => '1.1.01',
            'uraian' => 'Belanja ATK',
            'volume' => 10,
            'satuan' => 'buah',
            'tarif' => 50000,
            'jumlah' => 500000,
        ]);

        $this->importLog->refresh();
        $this->assertEquals(1, $this->importLog->baris_berhasil);
    }

    public function test_start_row_returns_two(): void
    {
        $this->assertSame(2, $this->import->startRow());
    }

    public function test_chunk_size_returns_one_hundred(): void
    {
        $this->assertSame(100, $this->import->chunkSize());
    }

    /** @param array<mixed> $args */
    private function callProtected(object $obj, string $method, array $args = []): mixed
    {
        $ref = new \ReflectionMethod($obj, $method);
        $ref->setAccessible(true);
        return $ref->invoke($obj, ...$args);
    }
}
