<?php

namespace Tests\Unit;

use App\Models\AuditLog;
use App\Models\RkasItem;
use App\Models\TransaksiBku;
use App\Models\User;
use App\Observers\RkasItemObserver;
use App\Observers\TransaksiBkuObserver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ObserversTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    protected function tearDown(): void
    {
        RkasItemObserver::$importUserId = null;
        parent::tearDown();
    }

    private function freshItem(): RkasItem
    {
        return RkasItem::factory()->make();
    }

    private function freshTransaksi(): TransaksiBku
    {
        return TransaksiBku::factory()->make();
    }

    /* ---------- RkasItemObserver ---------- */

    public function test_rkas_item_observer_created_creates_audit_log(): void
    {
        $observer = new RkasItemObserver;
        RkasItemObserver::$importUserId = $this->user->id;
        $observer->created($this->freshItem());

        $this->assertDatabaseHas('audit_log', [
            'user_id' => $this->user->id,
            'tabel' => 'rkas_item',
            'aksi' => 'create',
        ]);
    }

    public function test_rkas_item_observer_created_uses_auth_when_no_import_user(): void
    {
        $observer = new RkasItemObserver;
        RkasItemObserver::$importUserId = null;

        $this->actingAs($this->user);
        $observer->created($this->freshItem());

        $this->assertDatabaseHas('audit_log', [
            'user_id' => $this->user->id,
            'tabel' => 'rkas_item',
            'aksi' => 'create',
        ]);
    }

    public function test_rkas_item_observer_created_skips_when_no_user(): void
    {
        $observer = new RkasItemObserver;
        RkasItemObserver::$importUserId = null;
        $observer->created($this->freshItem());

        $this->assertDatabaseMissing('audit_log', [
            'tabel' => 'rkas_item',
            'aksi' => 'create',
        ]);
    }

    public function test_rkas_item_observer_updated_creates_audit_log(): void
    {
        $item = $this->freshItem();
        $item->uraian = 'Baru';

        $observer = new RkasItemObserver;
        RkasItemObserver::$importUserId = $this->user->id;
        $observer->updated($item);

        $this->assertDatabaseHas('audit_log', [
            'user_id' => $this->user->id,
            'tabel' => 'rkas_item',
            'aksi' => 'update',
        ]);
    }

    public function test_rkas_item_observer_deleted_creates_audit_log(): void
    {
        $observer = new RkasItemObserver;
        RkasItemObserver::$importUserId = $this->user->id;
        $observer->deleted($this->freshItem());

        $this->assertDatabaseHas('audit_log', [
            'user_id' => $this->user->id,
            'tabel' => 'rkas_item',
            'aksi' => 'delete',
        ]);
    }

    /* ---------- TransaksiBkuObserver ---------- */

    public function test_transaksi_bku_observer_created_creates_audit_log(): void
    {
        $observer = new TransaksiBkuObserver;

        $this->actingAs($this->user);
        $observer->created($this->freshTransaksi());

        $this->assertDatabaseHas('audit_log', [
            'user_id' => $this->user->id,
            'tabel' => 'transaksi_bku',
            'aksi' => 'create',
        ]);
    }

    public function test_transaksi_bku_observer_updated_creates_audit_log(): void
    {
        $transaksi = $this->freshTransaksi();
        $transaksi->keterangan = 'Baru';

        $observer = new TransaksiBkuObserver;

        $this->actingAs($this->user);
        $observer->updated($transaksi);

        $this->assertDatabaseHas('audit_log', [
            'user_id' => $this->user->id,
            'tabel' => 'transaksi_bku',
            'aksi' => 'update',
        ]);
    }

    public function test_transaksi_bku_observer_deleted_creates_audit_log(): void
    {
        $observer = new TransaksiBkuObserver;

        $this->actingAs($this->user);
        $observer->deleted($this->freshTransaksi());

        $this->assertDatabaseHas('audit_log', [
            'user_id' => $this->user->id,
            'tabel' => 'transaksi_bku',
            'aksi' => 'delete',
        ]);
    }

    public function test_transaksi_bku_observer_skips_when_guest(): void
    {
        $observer = new TransaksiBkuObserver;
        $observer->created($this->freshTransaksi());

        $this->assertDatabaseMissing('audit_log', [
            'tabel' => 'transaksi_bku',
            'aksi' => 'create',
        ]);
    }
}
