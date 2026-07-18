<?php

namespace Tests\Feature\MasterData;

use App\Models\Kecamatan;
use App\Models\ProfilSekolah;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfilSekolahTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_kecamatan_can_view_index(): void
    {
        $user = User::factory()->adminKecamatan()->create();
        ProfilSekolah::factory()->create(['nama' => 'SD Negeri Test']);

        $response = $this->actingAs($user)->get('/profil-sekolah');

        $response->assertStatus(200);
        $response->assertSee('SD Negeri Test');
    }

    public function test_admin_kecamatan_can_view_create(): void
    {
        $user = User::factory()->adminKecamatan()->create();

        $response = $this->actingAs($user)->get('/profil-sekolah/create');

        $response->assertStatus(200);
    }

    public function test_admin_kecamatan_can_store(): void
    {
        $user = User::factory()->adminKecamatan()->create();
        $kecamatan = Kecamatan::factory()->create();

        $response = $this->actingAs($user)->post('/profil-sekolah', [
            'npsn' => '12345678',
            'nama' => 'SD Negeri Baru',
            'kecamatan_id' => $kecamatan->id,
            'alamat' => 'Jl. Pendidikan No. 1',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('profil-sekolah.index', absolute: false));
        $this->assertDatabaseHas('profil_sekolah', ['npsn' => '12345678']);
    }

    public function test_admin_kecamatan_can_edit(): void
    {
        $user = User::factory()->adminKecamatan()->create();
        $sekolah = ProfilSekolah::factory()->create();

        $response = $this->actingAs($user)->get("/profil-sekolah/{$sekolah->id}/edit");

        $response->assertStatus(200);
    }

    public function test_admin_kecamatan_can_update(): void
    {
        $user = User::factory()->adminKecamatan()->create();
        $sekolah = ProfilSekolah::factory()->create();

        $response = $this->actingAs($user)->put("/profil-sekolah/{$sekolah->id}", [
            'npsn' => $sekolah->npsn,
            'nama' => 'SD Negeri Update',
            'kecamatan_id' => $sekolah->kecamatan_id,
            'alamat' => 'Jl. Baru No. 2',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('profil-sekolah.index', absolute: false));
        $this->assertDatabaseHas('profil_sekolah', ['nama' => 'SD Negeri Update']);
    }

    public function test_admin_kecamatan_can_destroy(): void
    {
        $user = User::factory()->adminKecamatan()->create();
        $sekolah = ProfilSekolah::factory()->create();

        $response = $this->actingAs($user)->delete("/profil-sekolah/{$sekolah->id}");

        $response->assertStatus(302);
        $this->assertDatabaseMissing('profil_sekolah', ['id' => $sekolah->id]);
    }

    public function test_guest_cannot_access(): void
    {
        $response = $this->get('/profil-sekolah');

        $response->assertRedirect('/login');
    }

    public function test_sekolah_user_cannot_access(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $user = User::factory()->sekolah($sekolah)->create();

        $response = $this->actingAs($user)->get('/profil-sekolah');

        $response->assertStatus(403);
    }
}
