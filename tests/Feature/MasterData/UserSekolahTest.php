<?php

namespace Tests\Feature\MasterData;

use App\Models\ProfilSekolah;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserSekolahTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Pastikan role yang dibutuhkan tersedia
        Role::firstOrCreate(['name' => 'sekolah', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'admin-kecamatan', 'guard_name' => 'web']);
    }

    public function test_admin_kecamatan_can_view_index(): void
    {
        $user = User::factory()->adminKecamatan()->create();
        User::factory()->sekolah(ProfilSekolah::factory()->create())->create(['name' => 'User Test']);

        $response = $this->actingAs($user)->get('/user-sekolah');

        $response->assertStatus(200);
        $response->assertSee('User Test');
    }

    public function test_admin_kecamatan_can_view_create(): void
    {
        $user = User::factory()->adminKecamatan()->create();

        $response = $this->actingAs($user)->get('/user-sekolah/create');

        $response->assertStatus(200);
    }

    public function test_admin_kecamatan_can_store_sekolah_user(): void
    {
        $admin = User::factory()->adminKecamatan()->create();
        $sekolah = ProfilSekolah::factory()->create();

        $response = $this->actingAs($admin)->post('/user-sekolah', [
            'name' => 'Operator Baru',
            'email' => 'operator@sekolah.sch.id',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'sekolah_id' => $sekolah->id,
            'role' => 'sekolah',
            'is_active' => '1',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('user-sekolah.index', absolute: false));
        $this->assertDatabaseHas('users', ['email' => 'operator@sekolah.sch.id']);
    }

    public function test_admin_kecamatan_can_store_admin_kecamatan_user(): void
    {
        $admin = User::factory()->adminKecamatan()->create();

        $response = $this->actingAs($admin)->post('/user-sekolah', [
            'name' => 'Admin Baru',
            'email' => 'admin@kecamatan.sch.id',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'sekolah_id' => '',
            'role' => 'admin-kecamatan',
            'is_active' => '1',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('user-sekolah.index', absolute: false));
        $this->assertDatabaseHas('users', ['email' => 'admin@kecamatan.sch.id']);
    }

    public function test_store_validates_sekolah_id_required_for_sekolah_role(): void
    {
        $admin = User::factory()->adminKecamatan()->create();

        $response = $this->actingAs($admin)->post('/user-sekolah', [
            'name' => 'Operator Error',
            'email' => 'error@sekolah.sch.id',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'sekolah_id' => '',
            'role' => 'sekolah',
        ]);

        $response->assertSessionHasErrors('sekolah_id');
    }

    public function test_admin_kecamatan_can_edit(): void
    {
        $admin = User::factory()->adminKecamatan()->create();
        $user = User::factory()->adminKecamatan()->create();

        $response = $this->actingAs($admin)->get("/user-sekolah/{$user->id}/edit");

        $response->assertStatus(200);
    }

    public function test_admin_kecamatan_can_update(): void
    {
        $admin = User::factory()->adminKecamatan()->create();
        $user = User::factory()->adminKecamatan()->create();

        $response = $this->actingAs($admin)->put("/user-sekolah/{$user->id}", [
            'name' => 'Nama Update',
            'email' => 'update@sekolah.sch.id',
            'role' => 'admin-kecamatan',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('user-sekolah.index', absolute: false));
        $this->assertDatabaseHas('users', ['name' => 'Nama Update']);
    }

    public function test_admin_kecamatan_can_destroy(): void
    {
        $admin = User::factory()->adminKecamatan()->create();
        $user = User::factory()->adminKecamatan()->create();

        $response = $this->actingAs($admin)->delete("/user-sekolah/{$user->id}");

        $response->assertStatus(302);
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    public function test_admin_kecamatan_can_reset_password(): void
    {
        $admin = User::factory()->adminKecamatan()->create();
        $sekolah = ProfilSekolah::factory()->create(['npsn' => '12345678']);
        $user = User::factory()->sekolah($sekolah)->create();

        $response = $this->actingAs($admin)->post("/user-sekolah/{$user->id}/reset-password");

        $response->assertSessionHasNoErrors();
        $response->assertStatus(302);
    }

    public function test_admin_kecamatan_can_toggle_active(): void
    {
        $admin = User::factory()->adminKecamatan()->create();
        $user = User::factory()->adminKecamatan()->create(['is_active' => true]);

        $response = $this->actingAs($admin)->post("/user-sekolah/{$user->id}/toggle-active");

        $response->assertStatus(302);
        $this->assertDatabaseHas('users', ['id' => $user->id, 'is_active' => false]);

        $response = $this->actingAs($admin)->post("/user-sekolah/{$user->id}/toggle-active");
        $this->assertDatabaseHas('users', ['id' => $user->id, 'is_active' => true]);
    }

    public function test_guest_cannot_access(): void
    {
        $response = $this->get('/user-sekolah');

        $response->assertRedirect('/login');
    }

    public function test_sekolah_user_cannot_access(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $user = User::factory()->sekolah($sekolah)->create();

        $response = $this->actingAs($user)->get('/user-sekolah');

        $response->assertStatus(403);
    }
}
