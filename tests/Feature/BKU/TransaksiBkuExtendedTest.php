<?php

namespace Tests\Feature\BKU;

use App\Models\ProfilSekolah;
use App\Models\RkasItem;
use App\Models\RkasItemBulan;
use App\Models\TahunAnggaran;
use App\Models\TransaksiBku;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class TransaksiBkuExtendedTest extends TestCase
{
    use RefreshDatabase;

    public function test_sekolah_user_can_view_edit(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $user = User::factory()->sekolah($sekolah)->create();
        TahunAnggaran::factory()->create(['status' => true]);
        $trans = TransaksiBku::factory()->create(['sekolah_id' => $sekolah->id]);

        $response = $this->actingAs($user)->get("/transaksi-bku/{$trans->id}/edit");

        $response->assertStatus(200);
    }

    public function test_sekolah_user_can_update(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $user = User::factory()->sekolah($sekolah)->create();
        TahunAnggaran::factory()->create(['status' => true]);
        $trans = TransaksiBku::factory()->create([
            'sekolah_id' => $sekolah->id,
            'no_bukti' => 'BPU-ORIGINAL',
        ]);

        $response = $this->actingAs($user)->put("/transaksi-bku/{$trans->id}", [
            'tanggal' => '2025-02-15',
            'no_bukti' => 'BPU-ORIGINAL',
            'jenis' => 'pengeluaran',
            'jumlah' => 300000,
            'uraian' => 'Uraian update',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('transaksi-bku.index', absolute: false));
        $this->assertDatabaseHas('transaksi_bku', [
            'id' => $trans->id,
            'jumlah' => 300000,
            'uraian' => 'Uraian update',
        ]);
    }

    public function test_sekolah_user_can_destroy(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $user = User::factory()->sekolah($sekolah)->create();
        TahunAnggaran::factory()->create(['status' => true]);
        $trans = TransaksiBku::factory()->create(['sekolah_id' => $sekolah->id]);

        $response = $this->actingAs($user)->delete("/transaksi-bku/{$trans->id}");

        $response->assertStatus(302);
        $this->assertDatabaseMissing('transaksi_bku', ['id' => $trans->id]);
    }

    public function test_update_rejects_when_exceeds_budget(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $user = User::factory()->sekolah($sekolah)->create();
        $tahun = TahunAnggaran::factory()->create(['status' => true]);
        $rkasItem = RkasItem::factory()->create(['sekolah_id' => $sekolah->id, 'jumlah' => 100000]);
        RkasItemBulan::factory()->create([
            'rkas_item_id' => $rkasItem->id,
            'bulan' => 2,
            'rencana' => 100000,
        ]);
        $trans = TransaksiBku::factory()->create([
            'sekolah_id' => $sekolah->id,
            'rkas_item_id' => $rkasItem->id,
            'jenis' => 'pengeluaran',
            'jumlah' => 50000,
            'bulan' => 2,
            'tahun_anggaran_id' => $tahun->id,
            'no_bukti' => 'BPU-001',
        ]);

        $response = $this->actingAs($user)->put("/transaksi-bku/{$trans->id}", [
            'tanggal' => '2025-02-15',
            'no_bukti' => 'BPU-001',
            'jenis' => 'pengeluaran',
            'jumlah' => 200000,
            'rkas_item_id' => $rkasItem->id,
        ]);

        $response->assertSessionHas('error');
        $this->assertDatabaseHas('transaksi_bku', ['id' => $trans->id, 'jumlah' => 50000]);
    }

    public function test_sekolah_cannot_edit_other_sekolah_transaksi(): void
    {
        $sekolah1 = ProfilSekolah::factory()->create();
        $sekolah2 = ProfilSekolah::factory()->create();
        $user = User::factory()->sekolah($sekolah1)->create();
        $trans = TransaksiBku::factory()->create(['sekolah_id' => $sekolah2->id]);

        $response = $this->actingAs($user)->get("/transaksi-bku/{$trans->id}/edit");

        $response->assertStatus(404);
    }

    public function test_sekolah_cannot_update_other_sekolah_transaksi(): void
    {
        $sekolah1 = ProfilSekolah::factory()->create();
        $sekolah2 = ProfilSekolah::factory()->create();
        $user = User::factory()->sekolah($sekolah1)->create();
        $trans = TransaksiBku::factory()->create(['sekolah_id' => $sekolah2->id]);

        $response = $this->actingAs($user)->put("/transaksi-bku/{$trans->id}", [
            'tanggal' => '2025-02-15',
            'no_bukti' => $trans->no_bukti,
            'jenis' => 'penerimaan',
            'jumlah' => 100000,
        ]);

        $response->assertStatus(404);
    }

    public function test_sekolah_cannot_destroy_other_sekolah_transaksi(): void
    {
        $sekolah1 = ProfilSekolah::factory()->create();
        $sekolah2 = ProfilSekolah::factory()->create();
        $user = User::factory()->sekolah($sekolah1)->create();
        $trans = TransaksiBku::factory()->create(['sekolah_id' => $sekolah2->id]);

        $response = $this->actingAs($user)->delete("/transaksi-bku/{$trans->id}");

        $response->assertStatus(404);
    }

    public function test_sekolah_user_can_view_create(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $user = User::factory()->sekolah($sekolah)->create();
        TahunAnggaran::factory()->create(['status' => true]);

        $response = $this->actingAs($user)->get('/transaksi-bku/create');

        $response->assertStatus(200);
    }

    public function test_sekolah_user_can_cetak_kwitansi_batch(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $user = User::factory()->sekolah($sekolah)->create();
        $tahun = TahunAnggaran::factory()->create(['status' => true]);
        $rkasItem = RkasItem::factory()->create(['sekolah_id' => $sekolah->id]);

        $trans1 = TransaksiBku::factory()->create([
            'sekolah_id' => $sekolah->id,
            'tahun_anggaran_id' => $tahun->id,
            'rkas_item_id' => $rkasItem->id,
            'jenis' => 'pengeluaran',
        ]);
        $trans2 = TransaksiBku::factory()->create([
            'sekolah_id' => $sekolah->id,
            'tahun_anggaran_id' => $tahun->id,
            'rkas_item_id' => $rkasItem->id,
            'jenis' => 'pengeluaran',
        ]);

        $response = $this->actingAs($user)->post('/transaksi-bku/cetak-kwitansi-batch', [
            'ids' => [$trans1->id, $trans2->id],
        ]);

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_cetak_kwitansi_batch_rejects_empty_ids(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $user = User::factory()->sekolah($sekolah)->create();

        $response = $this->actingAs($user)->post('/transaksi-bku/cetak-kwitansi-batch', [
            'ids' => [],
        ]);

        $response->assertSessionHas('error');
    }
}
