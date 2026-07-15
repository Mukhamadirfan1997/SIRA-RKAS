<?php

namespace Tests\Feature\BKU;

use App\Models\ProfilSekolah;
use App\Models\RkasItem;
use App\Models\TahunAnggaran;
use App\Models\TransaksiBku;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransaksiBkuTest extends TestCase
{
    use RefreshDatabase;

    public function test_sekolah_user_can_view_index(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $user = User::factory()->sekolah($sekolah)->create();

        TahunAnggaran::factory()->create(['status' => true]);
        TransaksiBku::factory()->create(['sekolah_id' => $sekolah->id]);

        $response = $this->actingAs($user)->get('/transaksi-bku');

        $response->assertStatus(200);
    }

    public function test_sekolah_user_can_create_transaksi(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $user = User::factory()->sekolah($sekolah)->create();
        $rkasItem = RkasItem::factory()->create(['sekolah_id' => $sekolah->id]);

        TahunAnggaran::factory()->create(['status' => true]);

        $response = $this->actingAs($user)->post('/transaksi-bku', [
            'rkas_item_id' => $rkasItem->id,
            'tanggal' => '2025-01-15',
            'no_bukti' => 'BKU-0001-00001',
            'jenis' => 'penerimaan',
            'jumlah' => 250000,
            'uraian' => 'Penerimaan dana BOS',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('transaksi-bku.index', absolute: false));
        $this->assertDatabaseHas('transaksi_bku', ['no_bukti' => 'BKU-0001-00001']);
    }

    public function test_guest_cannot_access_transaksi_bku(): void
    {
        $response = $this->get('/transaksi-bku');

        $response->assertRedirect('/login');
    }

    public function test_sekolah_user_only_sees_own_data_in_index(): void
    {
        $sekolah1 = ProfilSekolah::factory()->create();
        $sekolah2 = ProfilSekolah::factory()->create();
        $user = User::factory()->sekolah($sekolah1)->create();

        $tahun = TahunAnggaran::factory()->create(['status' => true]);
        $trans1 = TransaksiBku::factory()->create([
            'sekolah_id' => $sekolah1->id, 'no_bukti' => 'BKU-MILIK-1',
            'tahun_anggaran_id' => $tahun->id,
        ]);
        TransaksiBku::factory()->create([
            'sekolah_id' => $sekolah2->id, 'no_bukti' => 'BKU-MILIK-2',
            'tahun_anggaran_id' => $tahun->id,
        ]);

        $response = $this->actingAs($user)->get('/transaksi-bku?bulan=' . $trans1->bulan);

        $response->assertStatus(200);
        $response->assertSee('BKU-MILIK-1');
        $response->assertDontSee('BKU-MILIK-2');
    }

    public function test_admin_kecamatan_cannot_access_transaksi_bku(): void
    {
        $user = User::factory()->adminKecamatan()->create();

        $response = $this->actingAs($user)->get('/transaksi-bku');

        $response->assertStatus(403);
    }
}
