<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginIsActiveTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    public function test_nonaktif_user_cannot_login(): void
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

    public function test_aktif_user_can_login(): void
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
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_nonaktif_error_message(): void
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
