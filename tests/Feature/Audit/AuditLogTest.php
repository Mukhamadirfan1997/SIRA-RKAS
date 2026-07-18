<?php

namespace Tests\Feature\Audit;

use App\Models\AuditLog;
use App\Models\ProfilSekolah;
use App\Models\RkasItem;
use App\Models\TransaksiBku;
use App\Models\User;
use App\Observers\RkasItemObserver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_audit_log_created_when_rkas_item_created(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $sekolah = ProfilSekolah::factory()->create();
        $item = RkasItem::factory()->create(['sekolah_id' => $sekolah->id]);

        $this->assertDatabaseHas('audit_log', [
            'user_id' => $user->id,
            'tabel' => 'rkas_item',
            'aksi' => 'create',
        ]);

        $log = AuditLog::where('tabel', 'rkas_item')->where('aksi', 'create')->first();
        $this->assertNotEmpty($log->data_baru);
        $this->assertEquals($item->id, $log->data_baru['id']);
    }

    public function test_audit_log_created_when_rkas_item_updated(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $sekolah = ProfilSekolah::factory()->create();
        $item = RkasItem::factory()->create(['sekolah_id' => $sekolah->id]);

        $item->update(['uraian' => 'Uraian baru']);

        $this->assertDatabaseHas('audit_log', [
            'user_id' => $user->id,
            'tabel' => 'rkas_item',
            'aksi' => 'update',
        ]);
    }

    public function test_audit_log_created_when_rkas_item_deleted(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $sekolah = ProfilSekolah::factory()->create();
        $item = RkasItem::factory()->create(['sekolah_id' => $sekolah->id]);

        $item->delete();

        $this->assertDatabaseHas('audit_log', [
            'user_id' => $user->id,
            'tabel' => 'rkas_item',
            'aksi' => 'delete',
        ]);
    }

    public function test_audit_log_created_when_transaksi_bku_created(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $transaksi = TransaksiBku::factory()->create();

        $this->assertDatabaseHas('audit_log', [
            'user_id' => $user->id,
            'tabel' => 'transaksi_bku',
            'aksi' => 'create',
        ]);
    }

    public function test_no_audit_log_when_unauthenticated(): void
    {
        auth()->logout();
        RkasItemObserver::$importUserId = null;

        $sekolah = ProfilSekolah::factory()->create();
        $item = RkasItem::factory()->create(['sekolah_id' => $sekolah->id]);

        $this->assertDatabaseMissing('audit_log', [
            'tabel' => 'rkas_item',
            'aksi' => 'create',
        ]);
    }
}
