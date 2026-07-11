<?php

namespace Database\Seeders;

use App\Models\JenisBelanja;
use App\Models\ProfilSekolah;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleAndPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create roles
        $adminRole = Role::create(['name' => 'admin']);
        $bendaharaRole = Role::create(['name' => 'bendahara']);
        $kepalaSekolahRole = Role::create(['name' => 'kepala_sekolah']);
        $komiteRole = Role::create(['name' => 'komite']);

        // Create a default admin user
        $admin = User::create([
            'name' => 'Administrator',
            'email' => 'admin@sira-rkas.test',
            'password' => Hash::make('password'),
        ]);
        $admin->assignRole($adminRole);

        // Create default jenis belanja
        JenisBelanja::create(['nama' => 'Belanja Barang Persediaan']);
        JenisBelanja::create(['nama' => 'Belanja Jasa']);
        JenisBelanja::create(['nama' => 'Belanja Modal Peralatan & Mesin']);
        JenisBelanja::create(['nama' => 'Belanja Modal Buku']);
        JenisBelanja::create(['nama' => 'Belanja Modal Aset Tetap Lainnya']);

        // Create default profil sekolah
        ProfilSekolah::create([
            'npsn' => '20519260',
            'nama' => 'UPT SDN Toyaning I Rejoso',
            'alamat' => 'Desa Toyaning, Kecamatan Rejoso, Kabupaten Pasuruan',
            'kecamatan' => 'Rejoso',
            'kabupaten' => 'Pasuruan',
            'provinsi' => 'Jawa Timur',
        ]);
    }
}
