<?php

namespace Tests\Feature\Import;

use App\Models\ProfilSekolah;
use App\Models\TahunAnggaran;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Http\UploadedFile;

class ImportRkasExtendedTest extends TestCase
{
    use RefreshDatabase;

    public function test_sekolah_user_can_download_template(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $user = User::factory()->sekolah($sekolah)->create();
        TahunAnggaran::factory()->create(['status' => true]);

        $response = $this->actingAs($user)->get('/import-rkas/download-template');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    public function test_store_rejects_no_files(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $user = User::factory()->sekolah($sekolah)->create();
        TahunAnggaran::factory()->create(['status' => true]);

        $response = $this->actingAs($user)->post('/import-rkas', [
            'files' => [],
            'sumber_dana_id' => '',
        ]);

        $response->assertSessionHasErrors();
    }

    public function test_store_rejects_invalid_sumber_dana(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $user = User::factory()->sekolah($sekolah)->create();
        TahunAnggaran::factory()->create(['status' => true]);

        $response = $this->actingAs($user)->post('/import-rkas', [
            'files' => [1 => UploadedFile::fake()->create('test.xlsx', 100)],
            'sumber_dana_id' => '999',
        ]);

        $response->assertSessionHasErrors('sumber_dana_id');
    }

    public function test_store_rejects_invalid_file_type(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $user = User::factory()->sekolah($sekolah)->create();
        TahunAnggaran::factory()->create(['status' => true]);
        \App\Models\SumberDana::factory()->create(['id' => 1]);

        $response = $this->actingAs($user)->post('/import-rkas', [
            'files' => [1 => UploadedFile::fake()->create('test.pdf', 100)],
            'sumber_dana_id' => 1,
        ]);

        $response->assertSessionHasErrors('files.1');
    }

    public function test_store_dispatches_job(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $user = User::factory()->sekolah($sekolah)->create();
        TahunAnggaran::factory()->create(['status' => true]);
        $sd = \App\Models\SumberDana::factory()->create();
        $tahun = TahunAnggaran::where('status', true)->first();

        \Illuminate\Support\Facades\Queue::fake();

        $response = $this->actingAs($user)->post('/import-rkas', [
            'files' => [1 => UploadedFile::fake()->create('rkas.xlsx', 100)],
            'sumber_dana_id' => $sd->id,
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertStatus(302);

        \Illuminate\Support\Facades\Queue::assertPushed(\App\Jobs\ProcessRkasImport::class);
        $this->assertDatabaseHas('import_log', [
            'sekolah_id' => $sekolah->id,
            'bulan' => 1,
            'status' => 'pending',
            'sumber_dana_id' => $sd->id,
        ]);
    }

    public function test_guest_cannot_upload(): void
    {
        $response = $this->post('/import-rkas', [
            'files' => [1 => UploadedFile::fake()->create('test.xlsx', 100)],
        ]);

        $response->assertRedirect('/login');
    }

    public function test_admin_kecamatan_cannot_upload(): void
    {
        $user = User::factory()->adminKecamatan()->create();

        $response = $this->actingAs($user)->post('/import-rkas', [
            'files' => [1 => UploadedFile::fake()->create('test.xlsx', 100)],
        ]);

        $response->assertStatus(403);
    }
}
