<?php

namespace Tests\Feature\MasterData;

use App\Models\ProfilSekolah;
use App\Models\TahunAnggaran;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TahunAnggaranTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_kecamatan_can_view_index(): void
    {
        $user = User::factory()->adminKecamatan()->create();
        TahunAnggaran::factory()->create(['tahun' => 2025]);

        $response = $this->actingAs($user)->get('/tahun-anggaran');

        $response->assertStatus(200);
        $response->assertSee('2025');
    }

    public function test_admin_kecamatan_can_view_create(): void
    {
        $user = User::factory()->adminKecamatan()->create();

        $response = $this->actingAs($user)->get('/tahun-anggaran/create');

        $response->assertStatus(200);
    }

    public function test_admin_kecamatan_can_store(): void
    {
        $user = User::factory()->adminKecamatan()->create();

        $response = $this->actingAs($user)->post('/tahun-anggaran', [
            'tahun' => 2026,
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('tahun-anggaran.index', absolute: false));
        $this->assertDatabaseHas('tahun_anggaran', ['tahun' => 2026]);
    }

    public function test_store_validates_unique_tahun(): void
    {
        $user = User::factory()->adminKecamatan()->create();
        TahunAnggaran::factory()->create(['tahun' => 2026]);

        $response = $this->actingAs($user)->post('/tahun-anggaran', [
            'tahun' => 2026,
        ]);

        $response->assertSessionHasErrors('tahun');
    }

    public function test_admin_kecamatan_can_edit(): void
    {
        $user = User::factory()->adminKecamatan()->create();
        $tahun = TahunAnggaran::factory()->create();

        $response = $this->actingAs($user)->get("/tahun-anggaran/{$tahun->id}/edit");

        $response->assertStatus(200);
    }

    public function test_admin_kecamatan_can_update(): void
    {
        $user = User::factory()->adminKecamatan()->create();
        $tahun = TahunAnggaran::factory()->create(['tahun' => 2025]);

        $response = $this->actingAs($user)->put("/tahun-anggaran/{$tahun->id}", [
            'tahun' => 2027,
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('tahun-anggaran.index', absolute: false));
        $this->assertDatabaseHas('tahun_anggaran', ['tahun' => 2027]);
    }

    public function test_admin_kecamatan_can_set_active(): void
    {
        $user = User::factory()->adminKecamatan()->create();
        TahunAnggaran::factory()->create(['tahun' => 2025, 'status' => true]);
        $tahunBaru = TahunAnggaran::factory()->create(['tahun' => 2026, 'status' => false]);

        $response = $this->actingAs($user)->post("/tahun-anggaran/{$tahunBaru->id}/set-active");

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('tahun-anggaran.index', absolute: false));
        $this->assertDatabaseHas('tahun_anggaran', ['tahun' => 2026, 'status' => true]);
        $this->assertDatabaseHas('tahun_anggaran', ['tahun' => 2025, 'status' => false]);
    }

    public function test_cannot_destroy_active_tahun(): void
    {
        $user = User::factory()->adminKecamatan()->create();
        $tahun = TahunAnggaran::factory()->create(['status' => true]);

        $response = $this->actingAs($user)->delete("/tahun-anggaran/{$tahun->id}");

        $response->assertSessionHas('error');
        $this->assertDatabaseHas('tahun_anggaran', ['id' => $tahun->id]);
    }

    public function test_can_destroy_inactive_tahun(): void
    {
        $user = User::factory()->adminKecamatan()->create();
        $tahun = TahunAnggaran::factory()->create(['status' => false]);

        $response = $this->actingAs($user)->delete("/tahun-anggaran/{$tahun->id}");

        $response->assertStatus(302);
        $this->assertDatabaseMissing('tahun_anggaran', ['id' => $tahun->id]);
    }

    public function test_guest_cannot_access(): void
    {
        $response = $this->get('/tahun-anggaran');

        $response->assertRedirect('/login');
    }

    public function test_sekolah_user_cannot_access(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $user = User::factory()->sekolah($sekolah)->create();

        $response = $this->actingAs($user)->get('/tahun-anggaran');

        $response->assertStatus(403);
    }
}
