<?php

namespace Tests\Feature;

use App\Models\ProfilSekolah;
use App\Models\TahunAnggaran;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_sekolah_user_can_view_dashboard(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $user = User::factory()->sekolah($sekolah)->create();
        TahunAnggaran::factory()->create(['status' => true]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertStatus(200);
    }

    public function test_guest_cannot_access_dashboard(): void
    {
        $response = $this->get('/dashboard');

        $response->assertRedirect('/login');
    }

    public function test_admin_kecamatan_can_view_kecamatan_dashboard(): void
    {
        $user = User::factory()->adminKecamatan()->create();
        TahunAnggaran::factory()->create(['status' => true]);

        $response = $this->actingAs($user)->get('/dashboard/kecamatan');

        $response->assertStatus(200);
    }

    public function test_sekolah_user_cannot_access_kecamatan_dashboard(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $user = User::factory()->sekolah($sekolah)->create();

        $response = $this->actingAs($user)->get('/dashboard/kecamatan');

        $response->assertStatus(403);
    }

    public function test_guest_cannot_access_kecamatan_dashboard(): void
    {
        $response = $this->get('/dashboard/kecamatan');

        $response->assertRedirect('/login');
    }
}
