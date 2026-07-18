<?php

namespace Tests\Feature\MasterData;

use App\Models\ProfilSekolah;
use App\Models\SumberDana;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SumberDanaTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_kecamatan_can_view_index(): void
    {
        $user = User::factory()->adminKecamatan()->create();
        SumberDana::factory()->create(['nama' => 'BOS']);

        $response = $this->actingAs($user)->get('/sumber-dana');

        $response->assertStatus(200);
        $response->assertSee('BOS');
    }

    public function test_admin_kecamatan_can_view_create(): void
    {
        $user = User::factory()->adminKecamatan()->create();

        $response = $this->actingAs($user)->get('/sumber-dana/create');

        $response->assertStatus(200);
    }

    public function test_admin_kecamatan_can_store(): void
    {
        $user = User::factory()->adminKecamatan()->create();

        $response = $this->actingAs($user)->post('/sumber-dana', [
            'kode' => 'BOS-001',
            'nama' => 'Dana BOS',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('sumber-dana.index', absolute: false));
        $this->assertDatabaseHas('sumber_dana', ['kode' => 'BOS-001']);
    }

    public function test_admin_kecamatan_can_edit(): void
    {
        $user = User::factory()->adminKecamatan()->create();
        $sd = SumberDana::factory()->create();

        $response = $this->actingAs($user)->get("/sumber-dana/{$sd->id}/edit");

        $response->assertStatus(200);
    }

    public function test_admin_kecamatan_can_update(): void
    {
        $user = User::factory()->adminKecamatan()->create();
        $sd = SumberDana::factory()->create();

        $response = $this->actingAs($user)->put("/sumber-dana/{$sd->id}", [
            'kode' => 'BOS-UPD',
            'nama' => 'Dana BOS Update',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('sumber-dana.index', absolute: false));
        $this->assertDatabaseHas('sumber_dana', ['kode' => 'BOS-UPD']);
    }

    public function test_admin_kecamatan_can_destroy(): void
    {
        $user = User::factory()->adminKecamatan()->create();
        $sd = SumberDana::factory()->create();

        $response = $this->actingAs($user)->delete("/sumber-dana/{$sd->id}");

        $response->assertStatus(302);
        $this->assertDatabaseMissing('sumber_dana', ['id' => $sd->id]);
    }

    public function test_guest_cannot_access(): void
    {
        $response = $this->get('/sumber-dana');

        $response->assertRedirect('/login');
    }

    public function test_sekolah_user_cannot_access(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $user = User::factory()->sekolah($sekolah)->create();

        $response = $this->actingAs($user)->get('/sumber-dana');

        $response->assertStatus(403);
    }
}
