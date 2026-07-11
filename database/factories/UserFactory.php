<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (User $user) {
            if ($user->sekolah_id) {
                $role = Role::firstOrCreate(['name' => 'sekolah']);
                $user->assignRole($role);
            } elseif ($user->is_active) {
                $role = Role::firstOrCreate(['name' => 'admin-kecamatan']);
                $user->assignRole($role);
            }
        });
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function sekolah(\App\Models\ProfilSekolah $profil): static
    {
        return $this->state(fn (array $attributes) => [
            'sekolah_id' => $profil->id,
            'is_active' => true,
        ]);
    }

    public function nonaktif(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    public function adminKecamatan(): static
    {
        return $this->state(fn (array $attributes) => [
            'sekolah_id' => null,
            'is_active' => true,
        ]);
    }
}
