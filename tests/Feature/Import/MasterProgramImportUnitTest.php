<?php

namespace Tests\Feature\Import;

use App\Imports\MasterProgramImport;
use App\Models\MasterProgram;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MasterProgramImportUnitTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<string, mixed> */
    private function row(string $kode, string $uraian, string $program, string $subProgram): array
    {
        return [
            'kode_kegiatan' => $kode,
            'uraian' => $uraian,
            'program' => $program,
            'sub_program' => $subProgram,
        ];
    }

    public function test_creates_record_from_valid_row(): void
    {
        $import = new MasterProgramImport;
        $sheets = $import->sheets();
        $sheet = $sheets[1];

        $sheet->collection(collect([$this->row('1.1.01', 'Penyediaan ATK', 'Program A', 'Sub A')]));

        $this->assertDatabaseHas('master_program', [
            'kode' => '1.1.01',
            'nama' => 'Penyediaan ATK',
            'program' => 'Program A',
            'sub_program' => 'Sub A',
        ]);
    }

    public function test_calculates_level_from_kode_dots(): void
    {
        $import = new MasterProgramImport;
        $sheets = $import->sheets();
        $sheet = $sheets[1];

        $sheet->collection(collect([$this->row('1', 'Level 1', '', '')]));

        $this->assertDatabaseHas('master_program', ['kode' => '1', 'level' => 1]);
    }

    public function test_calculates_level_three_for_kode_with_two_dots(): void
    {
        $import = new MasterProgramImport;
        $sheets = $import->sheets();
        $sheet = $sheets[1];

        $sheet->collection(collect([$this->row('1.1.01', 'Level 3', '', '')]));

        $this->assertDatabaseHas('master_program', ['kode' => '1.1.01', 'level' => 3]);
    }

    public function test_skips_row_without_kode(): void
    {
        $import = new MasterProgramImport;
        $sheets = $import->sheets();
        $sheet = $sheets[1];

        $sheet->collection(collect([$this->row('', 'No Kode', '', '')]));

        $this->assertEquals(0, MasterProgram::count());
        $this->assertEquals(1, $import->skippedCount);
    }

    public function test_skips_row_without_uraian(): void
    {
        $import = new MasterProgramImport;
        $sheets = $import->sheets();
        $sheet = $sheets[1];

        $sheet->collection(collect([$this->row('1.1.01', '', '', '')]));

        $this->assertEquals(0, MasterProgram::count());
        $this->assertEquals(1, $import->skippedCount);
    }

    public function test_updates_existing_record_by_kode(): void
    {
        MasterProgram::factory()->create([
            'kode' => '1.1.01',
            'nama' => 'Nama Lama',
        ]);

        $import = new MasterProgramImport;
        $sheets = $import->sheets();
        $sheet = $sheets[1];

        $sheet->collection(collect([$this->row('1.1.01', 'Nama Baru', 'Program Baru', 'Sub Baru')]));

        $this->assertDatabaseHas('master_program', [
            'kode' => '1.1.01',
            'nama' => 'Nama Baru',
            'program' => 'Program Baru',
            'sub_program' => 'Sub Baru',
        ]);
        $this->assertEquals(1, MasterProgram::count());
    }

    public function test_increments_imported_count(): void
    {
        $import = new MasterProgramImport;
        $sheets = $import->sheets();
        $sheet = $sheets[1];

        $sheet->collection(collect([
            $this->row('1.1.01', 'Item 1', '', ''),
            $this->row('1.1.02', 'Item 2', '', ''),
        ]));

        $this->assertEquals(2, $import->importedCount);
    }

    public function test_sheet_only_accepts_index_one(): void
    {
        $import = new MasterProgramImport;
        $sheets = $import->sheets();

        $this->assertCount(1, $sheets);
        $this->assertArrayHasKey(1, $sheets);
    }
}
