<?php

namespace Tests\Feature;

use App\Models\MasterKodeRekening;
use App\Models\MasterProgram;
use App\Models\ProfilSekolah;
use App\Models\RkasItem;
use App\Models\SumberDana;
use App\Models\TahunAnggaran;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RkasItemSelect2Test extends TestCase
{
    use RefreshDatabase;

    public function test_select2_returns_json_for_sekolah_user(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $user = User::factory()->sekolah($sekolah)->create();
        TahunAnggaran::factory()->create(['status' => true]);
        $program = MasterProgram::factory()->create();
        $rekening = MasterKodeRekening::factory()->create();
        $sd = SumberDana::factory()->create();
        $item = RkasItem::factory()->create([
            'sekolah_id' => $sekolah->id,
            'no_urut' => 1,
            'uraian' => 'Belanja ATK',
            'program_id' => $program->id,
            'kode_rekening_id' => $rekening->id,
            'sumber_dana_id' => $sd->id,
        ]);

        $response = $this->actingAs($user)->getJson('/rkas-items/select2?q=ATK');

        $response->assertStatus(200);
        $response->assertJsonStructure(['results' => [['id', 'text']]]);
        $response->assertJsonFragment(['id' => $item->id]);
    }

    public function test_select2_returns_paginated_results(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $user = User::factory()->sekolah($sekolah)->create();
        TahunAnggaran::factory()->create(['status' => true]);
        $program = MasterProgram::factory()->create();
        $rekening = MasterKodeRekening::factory()->create();
        $sd = SumberDana::factory()->create();
        RkasItem::factory()->count(25)->create([
            'sekolah_id' => $sekolah->id,
            'program_id' => $program->id,
            'kode_rekening_id' => $rekening->id,
            'sumber_dana_id' => $sd->id,
        ]);

        $response = $this->actingAs($user)->getJson('/rkas-items/select2?page=1');

        $response->assertStatus(200);
        $response->assertJsonStructure(['results', 'pagination' => ['more']]);
    }

    public function test_select2_returns_empty_for_no_match(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $user = User::factory()->sekolah($sekolah)->create();
        TahunAnggaran::factory()->create(['status' => true]);

        $response = $this->actingAs($user)->getJson('/rkas-items/select2?q=NONEXISTENT');

        $response->assertStatus(200);
        $response->assertJson(['results' => []]);
    }

    public function test_select2_shows_only_own_sekolah_items(): void
    {
        $sekolah1 = ProfilSekolah::factory()->create();
        $sekolah2 = ProfilSekolah::factory()->create();
        $user = User::factory()->sekolah($sekolah1)->create();
        TahunAnggaran::factory()->create(['status' => true]);
        $program = MasterProgram::factory()->create();
        $rekening = MasterKodeRekening::factory()->create();
        $sd = SumberDana::factory()->create();
        $item1 = RkasItem::factory()->create([
            'sekolah_id' => $sekolah1->id,
            'no_urut' => 1,
            'uraian' => 'Item Milik Saya',
            'program_id' => $program->id,
            'kode_rekening_id' => $rekening->id,
            'sumber_dana_id' => $sd->id,
        ]);
        $item2 = RkasItem::factory()->create([
            'sekolah_id' => $sekolah2->id,
            'no_urut' => 2,
            'uraian' => 'Item Milik Orang Lain',
            'program_id' => $program->id,
            'kode_rekening_id' => $rekening->id,
            'sumber_dana_id' => $sd->id,
        ]);

        $response = $this->actingAs($user)->getJson('/rkas-items/select2');

        $response->assertStatus(200);
        $response->assertJsonFragment(['id' => $item1->id]);
        $response->assertJsonMissing(['id' => $item2->id]);
    }

    public function test_guest_cannot_access_select2(): void
    {
        $response = $this->getJson('/rkas-items/select2');

        $response->assertStatus(401);
    }
}
