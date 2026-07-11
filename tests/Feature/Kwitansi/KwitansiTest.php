<?php

namespace Tests\Feature\Kwitansi;

use App\Models\Kwitansi;
use App\Models\ProfilSekolah;
use App\Models\RkasItem;
use App\Models\TransaksiBku;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KwitansiTest extends TestCase
{
    use RefreshDatabase;

    public function test_sekolah_can_view_cetak_kwitansi(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $user = User::factory()->sekolah($sekolah)->create();
        $rkasItem = RkasItem::factory()->create(['sekolah_id' => $sekolah->id]);
        $transaksi = TransaksiBku::factory()->create([
            'sekolah_id' => $sekolah->id,
            'rkas_item_id' => $rkasItem->id,
        ]);

        $response = $this->actingAs($user)->get("/transaksi-bku/{$transaksi->id}/cetak-kwitansi");

        $response->assertStatus(200);
    }

    public function test_kwitansi_is_recorded_after_cetak(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $user = User::factory()->sekolah($sekolah)->create();
        $rkasItem = RkasItem::factory()->create(['sekolah_id' => $sekolah->id]);
        $transaksi = TransaksiBku::factory()->create([
            'sekolah_id' => $sekolah->id,
            'rkas_item_id' => $rkasItem->id,
        ]);

        $this->actingAs($user)->get("/transaksi-bku/{$transaksi->id}/cetak-kwitansi");

        $this->assertDatabaseHas('kwitansi', [
            'transaksi_bku_id' => $transaksi->id,
        ]);
    }

    public function test_guest_cannot_cetak_kwitansi(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $rkasItem = RkasItem::factory()->create(['sekolah_id' => $sekolah->id]);
        $transaksi = TransaksiBku::factory()->create([
            'sekolah_id' => $sekolah->id,
            'rkas_item_id' => $rkasItem->id,
        ]);

        $response = $this->get("/transaksi-bku/{$transaksi->id}/cetak-kwitansi");

        $response->assertRedirect('/login');
    }

    public function test_sekolah_cannot_cetak_other_sekolah_kwitansi(): void
    {
        $sekolah1 = ProfilSekolah::factory()->create();
        $sekolah2 = ProfilSekolah::factory()->create();
        $user = User::factory()->sekolah($sekolah1)->create();
        $rkasItem = RkasItem::factory()->create(['sekolah_id' => $sekolah2->id]);
        $transaksi = TransaksiBku::factory()->create([
            'sekolah_id' => $sekolah2->id,
            'rkas_item_id' => $rkasItem->id,
        ]);

        $response = $this->actingAs($user)->get("/transaksi-bku/{$transaksi->id}/cetak-kwitansi");

        $response->assertStatus(404);
    }
}
