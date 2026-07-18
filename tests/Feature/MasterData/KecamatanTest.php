<?php

namespace Tests\Feature\MasterData;

use App\Models\Kecamatan;
use App\Models\ProfilSekolah;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KecamatanTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_kecamatan_can_view_index(): void
    {
        $user = User::factory()->adminKecamatan()->create();
        Kecamatan::factory()->create(['nama' => 'Kecamatan Test']);

        $response = $this->actingAs($user)->get('/kecamatan');

        $response->assertStatus(200);
        $response->assertSee('Kecamatan Test');
    }

    public function test_admin_kecamatan_can_view_create(): void
    {
        $user = User::factory()->adminKecamatan()->create();

        $response = $this->actingAs($user)->get('/kecamatan/create');

        $response->assertStatus(200);
    }

    public function test_admin_kecamatan_can_store(): void
    {
        $user = User::factory()->adminKecamatan()->create();

        $response = $this->actingAs($user)->post('/kecamatan', [
            'nama' => 'Kecamatan Baru',
            'kabupaten' => 'Kabupaten Test',
            'provinsi' => 'Provinsi Test',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('kecamatan.index', absolute: false));
        $this->assertDatabaseHas('kecamatan', ['nama' => 'Kecamatan Baru']);
    }

    public function test_admin_kecamatan_can_edit(): void
    {
        $user = User::factory()->adminKecamatan()->create();
        $kecamatan = Kecamatan::factory()->create();

        $response = $this->actingAs($user)->get("/kecamatan/{$kecamatan->id}/edit");

        $response->assertStatus(200);
    }

    public function test_admin_kecamatan_can_update(): void
    {
        $user = User::factory()->adminKecamatan()->create();
        $kecamatan = Kecamatan::factory()->create();

        $response = $this->actingAs($user)->put("/kecamatan/{$kecamatan->id}", [
            'nama' => 'Kecamatan Update',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('kecamatan.index', absolute: false));
        $this->assertDatabaseHas('kecamatan', ['nama' => 'Kecamatan Update']);
    }

    public function test_admin_kecamatan_can_destroy(): void
    {
        $user = User::factory()->adminKecamatan()->create();
        $kecamatan = Kecamatan::factory()->create();

        $response = $this->actingAs($user)->delete("/kecamatan/{$kecamatan->id}");

        $response->assertStatus(302);
        $this->assertDatabaseMissing('kecamatan', ['id' => $kecamatan->id]);
    }

    public function test_guest_cannot_access(): void
    {
        $response = $this->get('/kecamatan');

        $response->assertRedirect('/login');
    }

    public function test_sekolah_user_cannot_access(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $user = User::factory()->sekolah($sekolah)->create();

        $response = $this->actingAs($user)->get('/kecamatan');

        $response->assertStatus(403);
    }
}
