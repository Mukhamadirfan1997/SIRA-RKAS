<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\SumberDana;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call(RoleAndPermissionSeeder::class);
        $this->call(TestDataSeeder::class);

        $adminKecRole = Role::firstOrCreate(['name' => 'admin-kecamatan']);
        $sekolahRole = Role::firstOrCreate(['name' => 'sekolah']);

        $user = User::create([
            'name' => 'Admin Kecamatan',
            'email' => 'admin@kecamatan.test',
            'password' => Hash::make('password'),
        ]);
        $user->assignRole($adminKecRole);

        SumberDana::insert([
            ['kode' => 'BOSP-REG', 'nama' => 'BOSP Reguler'],
            ['kode' => 'BOSP-KIN', 'nama' => 'BOSP Kinerja'],
        ]);
    }
}
