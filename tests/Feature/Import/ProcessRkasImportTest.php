<?php

namespace Tests\Feature\Import;

use App\Jobs\ProcessRkasImport;
use App\Models\AuditLog;
use App\Models\ImportLog;
use App\Models\RkasItem;
use App\Models\RkasItemBulan;
use App\Models\ProfilSekolah;
use App\Models\TahunAnggaran;
use App\Models\SumberDana;
use App\Models\User;
use App\Observers\RkasItemObserver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class ProcessRkasImportTest extends TestCase
{
    use RefreshDatabase;

    private ProfilSekolah $sekolah;
    private TahunAnggaran $tahunAnggaran;
    private SumberDana $sumberDana;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->sekolah = ProfilSekolah::factory()->create();
        $this->tahunAnggaran = TahunAnggaran::factory()->create();
        $this->sumberDana = SumberDana::factory()->create();
        $this->user = User::factory()->create();
    }

    public function test_does_nothing_when_import_log_not_found(): void
    {
        Excel::fake();

        ProcessRkasImport::dispatch(999, 'import_rkas/nonexistent.xlsx');

        $this->assertDatabaseMissing('import_log', ['id' => 999]);
    }

    public function test_deletes_existing_rkas_item_bulan_before_import(): void
    {
        $item = RkasItem::factory()->create([
            'sekolah_id' => $this->sekolah->id,
            'tahun_anggaran_id' => $this->tahunAnggaran->id,
            'sumber_dana_id' => $this->sumberDana->id,
        ]);

        $item2 = RkasItem::factory()->create([
            'sekolah_id' => $this->sekolah->id,
            'tahun_anggaran_id' => $this->tahunAnggaran->id,
            'sumber_dana_id' => $this->sumberDana->id,
        ]);

        RkasItemBulan::factory()->create([
            'rkas_item_id' => $item->id,
            'bulan' => 1,
        ]);

        RkasItemBulan::factory()->create([
            'rkas_item_id' => $item2->id,
            'bulan' => 1,
        ]);

        RkasItemBulan::factory()->create([
            'rkas_item_id' => $item->id,
            'bulan' => 2,
        ]);

        $log = ImportLog::factory()->create([
            'sekolah_id' => $this->sekolah->id,
            'tahun_anggaran_id' => $this->tahunAnggaran->id,
            'sumber_dana_id' => $this->sumberDana->id,
            'bulan' => 1,
            'status' => 'pending',
            'file_path' => 'import_rkas/test.xlsx',
            'uploaded_by' => $this->user->id,
            'baris_berhasil' => 0,
            'baris_gagal' => 0,
        ]);

        Excel::fake();

        ProcessRkasImport::dispatch($log->id, $log->file_path);

        // bulan 1 items should be deleted
        $this->assertEquals(0, RkasItemBulan::where('rkas_item_id', $item->id)
            ->where('bulan', 1)
            ->count());
        $this->assertEquals(0, RkasItemBulan::where('rkas_item_id', $item2->id)
            ->where('bulan', 1)
            ->count());

        // bulan 2 should remain
        $this->assertEquals(1, RkasItemBulan::where('rkas_item_id', $item->id)
            ->where('bulan', 2)
            ->count());
    }

    public function test_sets_failed_when_zero_berhasil(): void
    {
        $log = ImportLog::factory()->create([
            'sekolah_id' => $this->sekolah->id,
            'tahun_anggaran_id' => $this->tahunAnggaran->id,
            'sumber_dana_id' => $this->sumberDana->id,
            'bulan' => 1,
            'status' => 'pending',
            'file_path' => 'import_rkas/test.xlsx',
            'uploaded_by' => $this->user->id,
            'baris_berhasil' => 0,
            'baris_gagal' => 0,
        ]);

        Excel::fake();

        ProcessRkasImport::dispatch($log->id, $log->file_path);

        $log->refresh();
        $this->assertEquals('failed', $log->status);
        $this->assertNotNull($log->finished_at);
        $this->assertStringContainsString(
            'Tidak ada data yang berhasil diimpor',
            $log->error_detail[0] ?? ''
        );
    }

    public function test_sets_success_and_creates_audit_log_when_berhasil_positive(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('import_rkas/valid_test.xlsx', 'dummy');

        $log = ImportLog::factory()->create([
            'sekolah_id' => $this->sekolah->id,
            'tahun_anggaran_id' => $this->tahunAnggaran->id,
            'sumber_dana_id' => $this->sumberDana->id,
            'bulan' => 3,
            'status' => 'pending',
            'file_path' => 'import_rkas/valid_test.xlsx',
            'uploaded_by' => $this->user->id,
            'baris_berhasil' => 5,
            'baris_gagal' => 1,
        ]);

        Excel::fake();

        ProcessRkasImport::dispatch($log->id, $log->file_path);

        $log->refresh();
        $this->assertEquals('success', $log->status);
        $this->assertNotNull($log->finished_at);
        $this->assertEquals(6, $log->total_baris);

        $this->assertDatabaseHas('audit_log', [
            'user_id' => $this->user->id,
            'sekolah_id' => $this->sekolah->id,
            'tabel' => 'import_rkas',
            'aksi' => 'import',
        ]);
    }

    public function test_cleans_up_file_on_success(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('import_rkas/to_clean.xlsx', 'content');

        $log = ImportLog::factory()->create([
            'sekolah_id' => $this->sekolah->id,
            'tahun_anggaran_id' => $this->tahunAnggaran->id,
            'sumber_dana_id' => $this->sumberDana->id,
            'bulan' => 2,
            'status' => 'pending',
            'file_path' => 'import_rkas/to_clean.xlsx',
            'uploaded_by' => $this->user->id,
            'baris_berhasil' => 3,
            'baris_gagal' => 0,
        ]);

        Excel::fake();

        ProcessRkasImport::dispatch($log->id, $log->file_path);

        Storage::disk('local')->assertMissing('import_rkas/to_clean.xlsx');

        $log->refresh();
        $this->assertNull($log->file_path);
    }

    public function test_cleans_up_file_on_failure(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('import_rkas/fail_clean.xlsx', 'content');

        $log = ImportLog::factory()->create([
            'sekolah_id' => $this->sekolah->id,
            'tahun_anggaran_id' => $this->tahunAnggaran->id,
            'sumber_dana_id' => $this->sumberDana->id,
            'bulan' => 4,
            'status' => 'pending',
            'file_path' => 'import_rkas/fail_clean.xlsx',
            'uploaded_by' => $this->user->id,
            'baris_berhasil' => 0,
            'baris_gagal' => 0,
        ]);

        Excel::fake();

        ProcessRkasImport::dispatch($log->id, $log->file_path);

        Storage::disk('local')->assertMissing('import_rkas/fail_clean.xlsx');

        $log->refresh();
        $this->assertNull($log->file_path);
    }

    public function test_resets_observer_user_id_after_import(): void
    {
        $log = ImportLog::factory()->create([
            'sekolah_id' => $this->sekolah->id,
            'tahun_anggaran_id' => $this->tahunAnggaran->id,
            'sumber_dana_id' => $this->sumberDana->id,
            'bulan' => 1,
            'status' => 'pending',
            'file_path' => 'import_rkas/test.xlsx',
            'uploaded_by' => $this->user->id,
            'baris_berhasil' => 0,
            'baris_gagal' => 0,
        ]);

        Excel::fake();

        ProcessRkasImport::dispatch($log->id, $log->file_path);

        $this->assertNull(RkasItemObserver::$importUserId);
    }

    public function test_handles_exception_gracefully(): void
    {
        Storage::fake('local');
        $filePath = 'import_rkas/exception_test.xlsx';
        Storage::disk('local')->put($filePath, 'content');

        $log = ImportLog::factory()->create([
            'sekolah_id' => $this->sekolah->id,
            'tahun_anggaran_id' => $this->tahunAnggaran->id,
            'sumber_dana_id' => $this->sumberDana->id,
            'bulan' => 5,
            'status' => 'pending',
            'file_path' => $filePath,
            'uploaded_by' => $this->user->id,
            'baris_berhasil' => 0,
            'baris_gagal' => 0,
        ]);

        $mock = $this->createMock(\Maatwebsite\Excel\Fakes\ExcelFake::class);
        $mock->method('import')->willThrowException(new \Exception('Simulated error'));
        Excel::swap($mock);

        ProcessRkasImport::dispatch($log->id, $log->file_path);

        $log->refresh();
        $this->assertEquals('failed', $log->status);
        $this->assertNotNull($log->finished_at);

        Storage::disk('local')->assertMissing($filePath);
        $this->assertNull($log->file_path);
    }
}
