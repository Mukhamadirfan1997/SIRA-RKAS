<?php

namespace Tests\Feature\MasterData;

use App\Models\MasterProgram;
use App\Models\ProfilSekolah;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class MasterProgramTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_kecamatan_can_view_index(): void
    {
        $user = User::factory()->adminKecamatan()->create();
        MasterProgram::factory()->create(['nama' => 'Program Test']);

        $response = $this->actingAs($user)->get('/master-program');

        $response->assertStatus(200);
        $response->assertSee('Program Test');
    }

    public function test_admin_kecamatan_can_view_create(): void
    {
        $user = User::factory()->adminKecamatan()->create();

        $response = $this->actingAs($user)->get('/master-program/create');

        $response->assertStatus(200);
    }

    public function test_admin_kecamatan_can_store(): void
    {
        $user = User::factory()->adminKecamatan()->create();

        $response = $this->actingAs($user)->post('/master-program', [
            'kode' => '1.1.01',
            'nama' => 'Program Baru',
            'level' => 3,
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('master-program.index', absolute: false));
        $this->assertDatabaseHas('master_program', ['kode' => '1.1.01']);
    }

    public function test_admin_kecamatan_can_edit(): void
    {
        $user = User::factory()->adminKecamatan()->create();
        $program = MasterProgram::factory()->create();

        $response = $this->actingAs($user)->get("/master-program/{$program->id}/edit");

        $response->assertStatus(200);
    }

    public function test_admin_kecamatan_can_update(): void
    {
        $user = User::factory()->adminKecamatan()->create();
        $program = MasterProgram::factory()->create();

        $response = $this->actingAs($user)->put("/master-program/{$program->id}", [
            'kode' => '1.1.99',
            'nama' => 'Program Update',
            'level' => 3,
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('master-program.index', absolute: false));
        $this->assertDatabaseHas('master_program', ['kode' => '1.1.99']);
    }

    public function test_admin_kecamatan_can_destroy(): void
    {
        $user = User::factory()->adminKecamatan()->create();
        $program = MasterProgram::factory()->create();

        $response = $this->actingAs($user)->delete("/master-program/{$program->id}");

        $response->assertStatus(302);
        $this->assertDatabaseMissing('master_program', ['id' => $program->id]);
    }

    public function test_guest_cannot_access(): void
    {
        $response = $this->get('/master-program');

        $response->assertRedirect('/login');
    }

    public function test_sekolah_user_cannot_access(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $user = User::factory()->sekolah($sekolah)->create();

        $response = $this->actingAs($user)->get('/master-program');

        $response->assertStatus(403);
    }

    public function test_admin_can_import_with_invalid_file_returns_error_message(): void
    {
        $user = User::factory()->adminKecamatan()->create();

        $response = $this->actingAs($user)->post('/master-program/import', [
            'file' => UploadedFile::fake()->create('program.xlsx', 100),
        ]);

        $response->assertSessionHas('error');
    }

    public function test_import_rejects_invalid_file_type(): void
    {
        $user = User::factory()->adminKecamatan()->create();

        $response = $this->actingAs($user)->post('/master-program/import', [
            'file' => UploadedFile::fake()->create('program.pdf', 100),
        ]);

        $response->assertSessionHasErrors('file');
    }

    public function test_import_rejects_no_file(): void
    {
        $user = User::factory()->adminKecamatan()->create();

        $response = $this->actingAs($user)->post('/master-program/import', []);

        $response->assertSessionHasErrors('file');
    }

    public function test_sekolah_cannot_import_master_program(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $user = User::factory()->sekolah($sekolah)->create();

        $response = $this->actingAs($user)->post('/master-program/import', [
            'file' => UploadedFile::fake()->create('program.xlsx', 100),
        ]);

        $response->assertStatus(403);
    }
}
