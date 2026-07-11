<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Buat role untuk multi-tenant SIRA-RKAS 2.0
        $adminKecamatanRole = Role::firstOrCreate(['name' => 'admin-kecamatan']);
        $sekolahRole = Role::firstOrCreate(['name' => 'sekolah']);

        // Akun Admin Kecamatan Default
        $adminKecamatan = User::firstOrCreate([
            'email' => 'admin@rejoso.sira.test'
        ], [
            'name' => 'Admin Kecamatan Rejoso',
            'password' => Hash::make('password'), // Sebaiknya diubah nanti
        ]);
        
        if(!$adminKecamatan->hasRole('admin-kecamatan')) {
            $adminKecamatan->assignRole($adminKecamatanRole);
        }

        // Buat Profil Sekolah jika tidak ada
        $profil = \App\Models\ProfilSekolah::firstOrCreate([
            'npsn' => '12345678'
        ], [
            'nama' => 'SDN Toyaning 1',
            'alamat' => 'Jl. Pendidikan No 1',
            'nama_kepsek' => 'Budi Santoso',
            'nip_kepsek' => '198001012010011001'
        ]);

        // Akun Sekolah Dummy Default
        $sekolahA = User::firstOrCreate([
            'email' => 'sdntoyaning1@sira.test'
        ], [
            'name' => 'SDN Toyaning 1',
            'password' => Hash::make('password'),
            'sekolah_id' => $profil->id
        ]);
        
        if(!$sekolahA->hasRole('sekolah')) {
            $sekolahA->assignRole($sekolahRole);
        }
    }
}
