<?php

namespace Tests\Unit;

use App\Models\ImportLog;
use App\Models\Kwitansi;
use App\Models\ProfilSekolah;
use App\Models\RkasItem;
use App\Models\TransaksiBku;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GlobalScopeTest extends TestCase
{
    use RefreshDatabase;

    public function test_rkas_item_scope_otomatis_filter_sekolah(): void
    {
        $sekolah1 = ProfilSekolah::factory()->create();
        $sekolah2 = ProfilSekolah::factory()->create();

        RkasItem::factory()->create(['sekolah_id' => $sekolah1->id]);
        RkasItem::factory()->create(['sekolah_id' => $sekolah2->id]);

        $user = User::factory()->sekolah($sekolah1)->create();
        $this->actingAs($user);

        $items = RkasItem::all();
        $this->assertCount(1, $items);
        $this->assertEquals($sekolah1->id, $items->first()->sekolah_id);
    }

    public function test_rkas_item_tanpa_scope_melihat_semua(): void
    {
        $sekolah1 = ProfilSekolah::factory()->create();
        $sekolah2 = ProfilSekolah::factory()->create();

        RkasItem::factory()->create(['sekolah_id' => $sekolah1->id]);
        RkasItem::factory()->create(['sekolah_id' => $sekolah2->id]);

        $items = RkasItem::withoutGlobalScope('sekolah')->get();
        $this->assertCount(2, $items);
    }

    public function test_transaksi_bku_scope_otomatis_filter_sekolah(): void
    {
        $sekolah1 = ProfilSekolah::factory()->create();
        $sekolah2 = ProfilSekolah::factory()->create();

        TransaksiBku::factory()->create(['sekolah_id' => $sekolah1->id]);
        TransaksiBku::factory()->create(['sekolah_id' => $sekolah2->id]);

        $user = User::factory()->sekolah($sekolah1)->create();
        $this->actingAs($user);

        $items = TransaksiBku::all();
        $this->assertCount(1, $items);
    }

    public function test_kwitansi_scope_otomatis_filter_sekolah(): void
    {
        $sekolah1 = ProfilSekolah::factory()->create();
        $sekolah2 = ProfilSekolah::factory()->create();

        Kwitansi::factory()->create(['sekolah_id' => $sekolah1->id]);
        Kwitansi::factory()->create(['sekolah_id' => $sekolah2->id]);

        $user = User::factory()->sekolah($sekolah1)->create();
        $this->actingAs($user);

        $items = Kwitansi::all();
        $this->assertCount(1, $items);
    }

    public function test_import_log_scope_otomatis_filter_sekolah(): void
    {
        $sekolah1 = ProfilSekolah::factory()->create();
        $sekolah2 = ProfilSekolah::factory()->create();

        ImportLog::factory()->create(['sekolah_id' => $sekolah1->id]);
        ImportLog::factory()->create(['sekolah_id' => $sekolah2->id]);

        $user = User::factory()->sekolah($sekolah1)->create();
        $this->actingAs($user);

        $items = ImportLog::all();
        $this->assertCount(1, $items);
    }

    public function test_rkas_item_tanpa_auth_tidak_error(): void
    {
        RkasItem::factory()->create();

        $items = RkasItem::all();
        $this->assertCount(1, $items);
    }
}
