<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CleanAuditLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_no_old_logs_shows_zero_deleted(): void
    {
        $this->artisan('audit:clean', ['days' => 90])
            ->expectsOutput('Deleted 0 audit log entries older than 90 days.')
            ->assertSuccessful();
    }

    public function test_deletes_old_logs(): void
    {
        User::factory()->create();

        $old = AuditLog::create([
            'user_id' => 1,
            'tabel' => 'test',
            'aksi' => 'create',
        ]);
        $old->created_at = now()->subDays(100);
        $old->save();

        $recent = AuditLog::create([
            'user_id' => 1,
            'tabel' => 'test',
            'aksi' => 'create',
        ]);
        $recent->created_at = now()->subDays(30);
        $recent->save();

        $this->artisan('audit:clean', ['days' => 90])
            ->expectsOutput('Deleted 1 audit log entries older than 90 days.')
            ->assertSuccessful();

        $this->assertEquals(1, AuditLog::count());
    }

    public function test_uses_default_days_argument(): void
    {
        User::factory()->create();

        $old = AuditLog::create([
            'user_id' => 1,
            'tabel' => 'test',
            'aksi' => 'create',
        ]);
        $old->created_at = now()->subDays(91);
        $old->save();

        $recent = AuditLog::create([
            'user_id' => 1,
            'tabel' => 'test',
            'aksi' => 'create',
        ]);
        $recent->created_at = now()->subDays(89);
        $recent->save();

        $this->artisan('audit:clean')
            ->assertSuccessful();

        $this->assertEquals(1, AuditLog::count());
    }
}
