<?php

namespace Tests\Feature\MasterData;

use App\Models\JenisBelanja;
use App\Models\ProfilSekolah;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JenisBelanjaTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_kecamatan_can_view_index(): void
    {
        $user = User::factory()->adminKecamatan()->create();
        JenisBelanja::factory()->create(['nama' => 'Belanja Modal']);

        $response = $this->actingAs($user)->get('/jenis-belanja');

        $response->assertStatus(200);
        $response->assertSee('Belanja Modal');
    }

    public function test_admin_kecamatan_can_view_create(): void
    {
        $user = User::factory()->adminKecamatan()->create();

        $response = $this->actingAs($user)->get('/jenis-belanja/create');

        $response->assertStatus(200);
    }

    public function test_admin_kecamatan_can_store(): void
    {
        $user = User::factory()->adminKecamatan()->create();

        $response = $this->actingAs($user)->post('/jenis-belanja', [
            'nama' => 'Belanja Baru',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('jenis-belanja.index', absolute: false));
        $this->assertDatabaseHas('jenis_belanja', ['nama' => 'Belanja Baru']);
    }

    public function test_admin_kecamatan_can_edit(): void
    {
        $user = User::factory()->adminKecamatan()->create();
        $jb = JenisBelanja::factory()->create();

        $response = $this->actingAs($user)->get("/jenis-belanja/{$jb->id}/edit");

        $response->assertStatus(200);
    }

    public function test_admin_kecamatan_can_update(): void
    {
        $user = User::factory()->adminKecamatan()->create();
        $jb = JenisBelanja::factory()->create();

        $response = $this->actingAs($user)->put("/jenis-belanja/{$jb->id}", [
            'nama' => 'Belanja Update',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('jenis-belanja.index', absolute: false));
        $this->assertDatabaseHas('jenis_belanja', ['nama' => 'Belanja Update']);
    }

    public function test_admin_kecamatan_can_destroy(): void
    {
        $user = User::factory()->adminKecamatan()->create();
        $jb = JenisBelanja::factory()->create();

        $response = $this->actingAs($user)->delete("/jenis-belanja/{$jb->id}");

        $response->assertStatus(302);
        $this->assertDatabaseMissing('jenis_belanja', ['id' => $jb->id]);
    }

    public function test_guest_cannot_access(): void
    {
        $response = $this->get('/jenis-belanja');

        $response->assertRedirect('/login');
    }

    public function test_sekolah_user_cannot_access(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $user = User::factory()->sekolah($sekolah)->create();

        $response = $this->actingAs($user)->get('/jenis-belanja');

        $response->assertStatus(403);
    }
}
