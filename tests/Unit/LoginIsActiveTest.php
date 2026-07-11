<?php

namespace Tests\Unit;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginIsActiveTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_nonaktif_tidak_bisa_login(): void
    {
        $user = User::factory()->nonaktif()->create([
            'email' => 'nonaktif@test.com',
            'password' => bcrypt('password'),
        ]);

        $response = $this->post('/login', [
            'email' => 'nonaktif@test.com',
            'password' => 'password',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('email');
    }

    public function test_user_aktif_bisa_login(): void
    {
        $user = User::factory()->create([
            'email' => 'aktif@test.com',
            'password' => bcrypt('password'),
        ]);

        $response = $this->post('/login', [
            'email' => 'aktif@test.com',
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
    }

    public function test_pesan_error_nonaktif_sesuai(): void
    {
        $user = User::factory()->nonaktif()->create([
            'email' => 'nonaktif2@test.com',
            'password' => bcrypt('password'),
        ]);

        $response = $this->post('/login', [
            'email' => 'nonaktif2@test.com',
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors(['email' => 'Akun Anda telah dinonaktifkan. Silakan hubungi administrator.']);
    }
}
