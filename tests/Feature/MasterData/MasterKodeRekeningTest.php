<?php

namespace Tests\Feature\MasterData;

use App\Models\MasterKodeRekening;
use App\Models\JenisBelanja;
use App\Models\ProfilSekolah;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class MasterKodeRekeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_kecamatan_can_view_index(): void
    {
        $user = User::factory()->adminKecamatan()->create();
        JenisBelanja::factory()->create(['nama' => 'Belanja Modal']);
        MasterKodeRekening::factory()->create(['nama' => 'Belanja Bahan Bangunan']);

        $response = $this->actingAs($user)->get('/master-kode-rekening');

        $response->assertStatus(200);
        $response->assertSee('Belanja Bahan Bangunan');
    }

    public function test_admin_kecamatan_can_view_create(): void
    {
        $user = User::factory()->adminKecamatan()->create();
        JenisBelanja::factory()->create();

        $response = $this->actingAs($user)->get('/master-kode-rekening/create');

        $response->assertStatus(200);
    }

    public function test_admin_kecamatan_can_store(): void
    {
        $user = User::factory()->adminKecamatan()->create();
        $jb = JenisBelanja::factory()->create();

        $response = $this->actingAs($user)->post('/master-kode-rekening', [
            'kode' => '5.1.02.01.01.0001',
            'nama' => 'Belanja Baru',
            'jenis_belanja_id' => $jb->id,
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('master-kode-rekening.index', absolute: false));
        $this->assertDatabaseHas('master_kode_rekening', ['kode' => '5.1.02.01.01.0001']);
    }

    public function test_admin_kecamatan_can_edit(): void
    {
        $user = User::factory()->adminKecamatan()->create();
        $rekening = MasterKodeRekening::factory()->create();

        $response = $this->actingAs($user)->get("/master-kode-rekening/{$rekening->id}/edit");

        $response->assertStatus(200);
    }

    public function test_admin_kecamatan_can_update(): void
    {
        $user = User::factory()->adminKecamatan()->create();
        $jb = JenisBelanja::factory()->create();
        $rekening = MasterKodeRekening::factory()->create();

        $response = $this->actingAs($user)->put("/master-kode-rekening/{$rekening->id}", [
            'kode' => '5.1.99.99.99.9999',
            'nama' => 'Rekening Update',
            'jenis_belanja_id' => $jb->id,
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('master-kode-rekening.index', absolute: false));
        $this->assertDatabaseHas('master_kode_rekening', ['kode' => '5.1.99.99.99.9999']);
    }

    public function test_admin_kecamatan_can_destroy(): void
    {
        $user = User::factory()->adminKecamatan()->create();
        $rekening = MasterKodeRekening::factory()->create();

        $response = $this->actingAs($user)->delete("/master-kode-rekening/{$rekening->id}");

        $response->assertStatus(302);
        $this->assertDatabaseMissing('master_kode_rekening', ['id' => $rekening->id]);
    }

    public function test_admin_kecamatan_can_download_template(): void
    {
        $user = User::factory()->adminKecamatan()->create();

        $response = $this->actingAs($user)->get('/master-kode-rekening/download-template');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    public function test_guest_cannot_access(): void
    {
        $response = $this->get('/master-kode-rekening');

        $response->assertRedirect('/login');
    }

    public function test_sekolah_user_cannot_access(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $user = User::factory()->sekolah($sekolah)->create();

        $response = $this->actingAs($user)->get('/master-kode-rekening');

        $response->assertStatus(403);
    }

    public function test_admin_can_import_with_invalid_file_returns_error_message(): void
    {
        $user = User::factory()->adminKecamatan()->create();

        $response = $this->actingAs($user)->post('/master-kode-rekening/import', [
            'file' => UploadedFile::fake()->create('rekening.xlsx', 100),
        ]);

        $response->assertSessionHas('error');
    }

    public function test_import_rejects_invalid_file_type(): void
    {
        $user = User::factory()->adminKecamatan()->create();

        $response = $this->actingAs($user)->post('/master-kode-rekening/import', [
            'file' => UploadedFile::fake()->create('rekening.pdf', 100),
        ]);

        $response->assertSessionHasErrors('file');
    }

    public function test_import_rejects_no_file(): void
    {
        $user = User::factory()->adminKecamatan()->create();

        $response = $this->actingAs($user)->post('/master-kode-rekening/import', []);

        $response->assertSessionHasErrors('file');
    }

    public function test_sekolah_cannot_import_master_kode_rekening(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $user = User::factory()->sekolah($sekolah)->create();

        $response = $this->actingAs($user)->post('/master-kode-rekening/import', [
            'file' => UploadedFile::fake()->create('rekening.xlsx', 100),
        ]);

        $response->assertStatus(403);
    }
}
