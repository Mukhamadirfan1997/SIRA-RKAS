<?php

namespace Tests\Feature\Import;

use App\Imports\MasterKodeRekeningImport;
use App\Models\MasterKodeRekening;
use App\Models\JenisBelanja;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MasterKodeRekeningImportUnitTest extends TestCase
{
    use RefreshDatabase;

    /** @param array<string, mixed> $row */
    private function runModel(MasterKodeRekeningImport $import, array $row): void
    {
        $sheets = $import->sheets();
        $sheet = $sheets[0];
        $result = $sheet->model($row);
        if ($result) $result->save();
    }

    public function test_creates_record_from_valid_row(): void
    {
        $import = new MasterKodeRekeningImport;

        $this->runModel($import, [
            'kode_barang' => '5.1.02.01.01.0001',
            'rincian_objek' => 'Belanja Kertas',
        ]);

        $this->assertDatabaseHas('master_kode_rekening', [
            'kode' => '5.1.02.01.01.0001',
            'nama' => 'Belanja Kertas',
        ]);
    }

    public function test_assigns_correct_jenis_belanja_by_prefix(): void
    {
        $import = new MasterKodeRekeningImport;

        $this->runModel($import, [
            'kode_barang' => '5.1.02.01.01.0001',
            'rincian_objek' => 'ATK',
        ]);

        $jenis = JenisBelanja::where('nama', 'Belanja Barang Persediaan')->first();
        $this->assertNotNull($jenis);
        $this->assertDatabaseHas('master_kode_rekening', [
            'kode' => '5.1.02.01.01.0001',
            'jenis_belanja_id' => $jenis->id,
        ]);
    }

    public function test_assigns_belanja_jasa_prefix(): void
    {
        $import = new MasterKodeRekeningImport;

        $this->runModel($import, [
            'kode_barang' => '5.1.02.02.01.0002',
            'rincian_objek' => 'Biaya Sewa',
        ]);

        $jenis = JenisBelanja::where('nama', 'Belanja Jasa')->first();
        $this->assertNotNull($jenis);
        $this->assertDatabaseHas('master_kode_rekening', [
            'kode' => '5.1.02.02.01.0002',
            'jenis_belanja_id' => $jenis->id,
        ]);
    }

    public function test_assigns_belanja_modal_by_short_prefix(): void
    {
        $import = new MasterKodeRekeningImport;

        $this->runModel($import, [
            'kode_barang' => '5.2.02.10.01.0003',
            'rincian_objek' => 'Komputer',
        ]);

        $jenis = JenisBelanja::where('nama', 'Belanja Modal Peralatan & Mesin')->first();
        $this->assertNotNull($jenis);
        $this->assertDatabaseHas('master_kode_rekening', [
            'kode' => '5.2.02.10.01.0003',
            'jenis_belanja_id' => $jenis->id,
        ]);
    }

    public function test_assigns_belanja_lainnya_as_default(): void
    {
        $import = new MasterKodeRekeningImport;

        $this->runModel($import, [
            'kode_barang' => '9.9.99.99.99.9999',
            'rincian_objek' => 'Lain-lain',
        ]);

        $jenis = JenisBelanja::where('nama', 'Belanja Lainnya')->first();
        $this->assertNotNull($jenis);
        $this->assertDatabaseHas('master_kode_rekening', [
            'kode' => '9.9.99.99.99.9999',
            'jenis_belanja_id' => $jenis->id,
        ]);
    }

    public function test_creates_jenis_belanja_if_not_exists(): void
    {
        $this->assertEquals(0, JenisBelanja::count());

        $import = new MasterKodeRekeningImport;

        $this->runModel($import, [
            'kode_barang' => '5.1.02.01.01.0001',
            'rincian_objek' => 'ATK',
        ]);

        $this->assertEquals(1, JenisBelanja::count());
        $this->assertDatabaseHas('jenis_belanja', ['nama' => 'Belanja Barang Persediaan']);
    }

    public function test_caches_jenis_belanja_lookup(): void
    {
        $import = new MasterKodeRekeningImport;

        $this->runModel($import, [
            'kode_barang' => '5.1.02.01.01.0001',
            'rincian_objek' => 'ATK',
        ]);
        $this->runModel($import, [
            'kode_barang' => '5.1.02.01.01.0002',
            'rincian_objek' => 'Alat Tulis',
        ]);

        $this->assertEquals(1, JenisBelanja::where('nama', 'Belanja Barang Persediaan')->count());
    }

    public function test_returns_sheet_for_first_index(): void
    {
        $import = new MasterKodeRekeningImport;
        $sheets = $import->sheets();

        $this->assertCount(1, $sheets);
        $this->assertArrayHasKey(0, $sheets);
    }
}
