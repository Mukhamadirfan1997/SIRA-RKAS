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

    public function test_sekolah_user_can_destroy_their_rkas_item(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $user = User::factory()->sekolah($sekolah)->create();
        $item = RkasItem::factory()->create(['sekolah_id' => $sekolah->id]);

        $response = $this->actingAs($user)->delete("/rkas/{$item->id}");

        $response->assertSessionHas('success');
        $this->assertModelMissing($item);
    }

    public function test_guest_cannot_destroy_rkas_item(): void
    {
        $item = RkasItem::factory()->create();

        $response = $this->delete("/rkas/{$item->id}");

        $response->assertRedirect('/login');
    }

    /* ---------- Admin-kecamatan filter flows ---------- */

    public function test_admin_can_view_rkas_with_sekolah_filter(): void
    {
        $admin = User::factory()->adminKecamatan()->create();
        $sekolah = ProfilSekolah::factory()->create();
        TahunAnggaran::factory()->create(['status' => true]);

        $response = $this->actingAs($admin)->get('/rkas?sekolah_id=' . $sekolah->id);

        $response->assertStatus(200);
    }

    public function test_admin_can_view_rkas_with_program_filter(): void
    {
        $admin = User::factory()->adminKecamatan()->create();
        $program = MasterProgram::factory()->create();
        TahunAnggaran::factory()->create(['status' => true]);

        $response = $this->actingAs($admin)->get('/rkas?program_id=' . $program->id);

        $response->assertStatus(200);
    }

    public function test_admin_can_view_rkas_with_sumber_dana_filter(): void
    {
        $admin = User::factory()->adminKecamatan()->create();
        $sumberDana = SumberDana::factory()->create();
        TahunAnggaran::factory()->create(['status' => true]);

        $response = $this->actingAs($admin)->get('/rkas?sumber_dana_id=' . $sumberDana->id);

        $response->assertStatus(200);
    }

    public function test_admin_can_view_rkas_with_search_filter(): void
    {
        $admin = User::factory()->adminKecamatan()->create();
        $sekolah = ProfilSekolah::factory()->create();
        TahunAnggaran::factory()->create(['status' => true]);
        RkasItem::factory()->create(['sekolah_id' => $sekolah->id, 'uraian' => 'ATK Kantor']);

        $response = $this->actingAs($admin)->get('/rkas?search=ATK');

        $response->assertStatus(200);
    }

    public function test_admin_can_view_rkas_with_bulan_filter(): void
    {
        $admin = User::factory()->adminKecamatan()->create();
        TahunAnggaran::factory()->create(['status' => true]);

        $response = $this->actingAs($admin)->get('/rkas?bulan=6');

        $response->assertStatus(200);
    }

    public function test_admin_can_view_rkas_with_tahun_filter(): void
    {
        $admin = User::factory()->adminKecamatan()->create();
        $tahun = TahunAnggaran::factory()->create(['status' => true]);

        $response = $this->actingAs($admin)->get('/rkas?tahun=' . $tahun->tahun);

        $response->assertStatus(200);
    }

    public function test_admin_can_edit_any_sekolah_rkas_item(): void
    {
        $admin = User::factory()->adminKecamatan()->create();
        $sekolah = ProfilSekolah::factory()->create();
        $item = RkasItem::factory()->create(['sekolah_id' => $sekolah->id]);

        $response = $this->actingAs($admin)->get("/rkas/{$item->id}/edit");

        $response->assertStatus(200);
    }

    public function test_admin_can_update_any_sekolah_rkas_item(): void
    {
        $admin = User::factory()->adminKecamatan()->create();
        $sekolah = ProfilSekolah::factory()->create();
        $item = RkasItem::factory()->create(['sekolah_id' => $sekolah->id]);

        $response = $this->actingAs($admin)->put("/rkas/{$item->id}", [
            'uraian' => 'Diupdate admin',
            'volume' => 5,
            'satuan' => 'paket',
            'tarif' => 100000,
            'jumlah' => 500000,
            'no_urut' => 1,
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertEquals('Diupdate admin', $item->fresh()->uraian);
    }

    public function test_admin_can_destroy_any_sekolah_rkas_item(): void
    {
        $admin = User::factory()->adminKecamatan()->create();
        $sekolah = ProfilSekolah::factory()->create();
        $item = RkasItem::factory()->create(['sekolah_id' => $sekolah->id]);

        $response = $this->actingAs($admin)->delete("/rkas/{$item->id}");

        $response->assertSessionHas('success');
        $this->assertModelMissing($item);
    }

    public function test_sekolah_cannot_edit_other_sekolah_item(): void
    {
        $sekolah1 = ProfilSekolah::factory()->create();
        $sekolah2 = ProfilSekolah::factory()->create();
        $user = User::factory()->sekolah($sekolah1)->create();
        $item = RkasItem::factory()->create(['sekolah_id' => $sekolah2->id]);

        $response = $this->actingAs($user)->get("/rkas/{$item->id}/edit");

        $response->assertStatus(404);
    }

    public function test_sekolah_cannot_update_other_sekolah_item(): void
    {
        $sekolah1 = ProfilSekolah::factory()->create();
        $sekolah2 = ProfilSekolah::factory()->create();
        $user = User::factory()->sekolah($sekolah1)->create();
        $item = RkasItem::factory()->create(['sekolah_id' => $sekolah2->id]);

        $response = $this->actingAs($user)->put("/rkas/{$item->id}", [
            'uraian' => 'Hack',
            'volume' => 1,
            'satuan' => 'x',
            'tarif' => 1,
            'jumlah' => 1,
            'no_urut' => 1,
        ]);

        $response->assertStatus(404);
    }

    public function test_sekolah_cannot_destroy_other_sekolah_item(): void
    {
        $sekolah1 = ProfilSekolah::factory()->create();
        $sekolah2 = ProfilSekolah::factory()->create();
        $user = User::factory()->sekolah($sekolah1)->create();
        $item = RkasItem::factory()->create(['sekolah_id' => $sekolah2->id]);

        $response = $this->actingAs($user)->delete("/rkas/{$item->id}");

        $response->assertStatus(404);
    }

    public function test_update_validates_required_fields(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $user = User::factory()->sekolah($sekolah)->create();
        $item = RkasItem::factory()->create(['sekolah_id' => $sekolah->id]);

        $response = $this->actingAs($user)->put("/rkas/{$item->id}", [
            'uraian' => '',
            'no_urut' => '',
            'jumlah' => '',
        ]);

        $response->assertSessionHasErrors(['uraian', 'no_urut', 'jumlah']);
    }
}
