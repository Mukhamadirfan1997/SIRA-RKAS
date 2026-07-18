<?php

namespace Tests\Feature;

use App\Jobs\SendTelegramNotificationJob;
use App\Listeners\NotifyBackupTelegram;
use Illuminate\Support\Facades\Bus;
use Spatie\Backup\BackupDestination\BackupDestination;
use Spatie\Backup\Events\BackupWasSuccessful;
use Spatie\Backup\Events\BackupHasFailed;
use Spatie\Backup\Events\CleanupWasSuccessful;
use Spatie\Backup\Events\CleanupHasFailed;
use Spatie\Backup\Events\HealthyBackupWasFound;
use Spatie\Backup\Events\UnhealthyBackupWasFound;
use Tests\TestCase;

class NotifyBackupTelegramTest extends TestCase
{
    private NotifyBackupTelegram $listener;

    protected function setUp(): void
    {
        parent::setUp();
        $this->listener = new NotifyBackupTelegram;
    }

    private function mockDest(): BackupDestination
    {
        $dest = $this->createMock(BackupDestination::class);
        $dest->method('diskName')->willReturn('local');
        $dest->method('backupName')->willReturn('backup-2026');
        return $dest;
    }

    public function test_backup_success_dispatches_info(): void
    {
        Bus::fake();
        $this->listener->handle(new BackupWasSuccessful($this->mockDest()));

        Bus::assertDispatched(SendTelegramNotificationJob::class, function ($job) {
            return str_contains($job->level, 'INFO') && str_contains($job->message, 'Backup berhasil');
        });
    }

    public function test_backup_failed_dispatches_error(): void
    {
        Bus::fake();
        $this->listener->handle(new BackupHasFailed(new \Exception('Disk full')));

        Bus::assertDispatched(SendTelegramNotificationJob::class, function ($job) {
            return str_contains($job->level, 'ERROR') && str_contains($job->message, 'Backup GAGAL');
        });
    }

    public function test_cleanup_success_dispatches_info(): void
    {
        Bus::fake();
        $this->listener->handle(new CleanupWasSuccessful($this->mockDest()));

        Bus::assertDispatched(SendTelegramNotificationJob::class, function ($job) {
            return str_contains($job->level, 'INFO') && str_contains($job->message, 'Pembersihan backup berhasil');
        });
    }

    public function test_cleanup_failed_dispatches_error(): void
    {
        Bus::fake();
        $this->listener->handle(new CleanupHasFailed(new \Exception('Cleanup error')));

        Bus::assertDispatched(SendTelegramNotificationJob::class, function ($job) {
            return str_contains($job->level, 'ERROR') && str_contains($job->message, 'Pembersihan backup GAGAL');
        });
    }

    public function test_healthy_backup_dispatches_info(): void
    {
        Bus::fake();

        $status = $this->getMockBuilder(\Spatie\Backup\Tasks\Monitor\BackupDestinationStatus::class)
            ->disableOriginalConstructor()
            ->addMethods(['diskName', 'amountOfBackups'])
            ->getMock();
        $status->method('diskName')->willReturn('local');
        $status->method('amountOfBackups')->willReturn(3);

        $this->listener->handle(new HealthyBackupWasFound($status));

        Bus::assertDispatched(SendTelegramNotificationJob::class, function ($job) {
            return str_contains($job->level, 'INFO') && str_contains($job->message, 'Backup sehat');
        });
    }

    public function test_unhealthy_backup_dispatches_warning(): void
    {
        Bus::fake();

        $mockBuilder = $this->getMockBuilder(\Spatie\Backup\Tasks\Monitor\BackupDestinationStatus::class)
            ->disableOriginalConstructor()
            ->addMethods(['diskName', 'healthCheckFailures']);
        $status = $mockBuilder->getMock();
        $status->method('diskName')->willReturn('s3');
        $status->method('healthCheckFailures')->willReturn([new \Exception('Backup terlalu tua')]);

        $this->listener->handle(new UnhealthyBackupWasFound($status));

        Bus::assertDispatched(SendTelegramNotificationJob::class, function ($job) {
            return str_contains($job->level, 'WARNING') && str_contains($job->message, 'Backup TIDAK SEHAT');
        });
    }

    public function test_unknown_event_does_nothing(): void
    {
        Bus::fake();
        $this->listener->handle(new \stdClass);

        Bus::assertNothingDispatched();
    }
}
