<?php

namespace Tests\Feature\Import;

use App\Models\ProfilSekolah;
use App\Models\TahunAnggaran;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ImportRkasTest extends TestCase
{
    use RefreshDatabase;

    public function test_sekolah_user_can_view_import_page(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $user = User::factory()->sekolah($sekolah)->create();
        TahunAnggaran::factory()->create(['status' => true]);

        $response = $this->actingAs($user)->get('/import-rkas');

        $response->assertStatus(200);
    }

    public function test_guest_cannot_access_import(): void
    {
        $response = $this->get('/import-rkas');

        $response->assertRedirect('/login');
    }

    public function test_admin_kecamatan_cannot_access_import(): void
    {
        $user = User::factory()->adminKecamatan()->create();

        $response = $this->actingAs($user)->get('/import-rkas');

        $response->assertStatus(403);
    }

    public function test_import_status_returns_json(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $user = User::factory()->sekolah($sekolah)->create();
        TahunAnggaran::factory()->create(['status' => true]);

        $response = $this->actingAs($user)->get('/import-rkas/status');

        $response->assertStatus(200);
        $response->assertJson([]);
    }
}
