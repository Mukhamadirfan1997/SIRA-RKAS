<?php

namespace Tests\Feature\RKAS;

use App\Models\MasterProgram;
use App\Models\MasterKodeRekening;
use App\Models\ProfilSekolah;
use App\Models\RkasItem;
use App\Models\SumberDana;
use App\Models\TahunAnggaran;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RkasItemTest extends TestCase
{
    use RefreshDatabase;

    public function test_sekolah_user_can_view_rkas_index(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $user = User::factory()->sekolah($sekolah)->create();

        TahunAnggaran::factory()->create(['status' => true]);

        $response = $this->actingAs($user)->get('/rkas');

        $response->assertStatus(200);
    }

    public function test_sekolah_user_can_edit_their_rkas_item(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $user = User::factory()->sekolah($sekolah)->create();
        $item = RkasItem::factory()->create(['sekolah_id' => $sekolah->id]);

        $response = $this->actingAs($user)->get("/rkas/{$item->id}/edit");

        $response->assertStatus(200);
    }

    public function test_sekolah_user_can_update_their_rkas_item(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $user = User::factory()->sekolah($sekolah)->create();
        $item = RkasItem::factory()->create(['sekolah_id' => $sekolah->id]);

        $response = $this->actingAs($user)->put("/rkas/{$item->id}", [
            'uraian' => 'Uraian yang diedit',
            'volume' => 10,
            'satuan' => 'buah',
            'tarif' => 50000,
            'jumlah' => 500000,
            'no_urut' => 1,
            'program_id' => MasterProgram::factory()->create()->id,
            'kode_rekening_id' => MasterKodeRekening::factory()->create()->id,
            'sumber_dana_id' => SumberDana::factory()->create()->id,
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('rkas.index', absolute: false));
        $this->assertEquals('Uraian yang diedit', $item->fresh()->uraian);
    }

    public function test_guest_cannot_access_rkas(): void
    {
        $response = $this->get('/rkas');

        $response->assertRedirect('/login');
    }

    public function test_sekolah_user_cannot_see_other_sekolah_item_in_index(): void
    {
        $sekolah1 = ProfilSekolah::factory()->create();
        $sekolah2 = ProfilSekolah::factory()->create();
        $user = User::factory()->sekolah($sekolah1)->create();

        TahunAnggaran::factory()->create(['status' => true]);
        RkasItem::factory()->create(['sekolah_id' => $sekolah1->id]);
        RkasItem::factory()->create(['sekolah_id' => $sekolah2->id]);

        $response = $this->actingAs($user)->get('/rkas');

        $response->assertStatus(200);
    }
}
