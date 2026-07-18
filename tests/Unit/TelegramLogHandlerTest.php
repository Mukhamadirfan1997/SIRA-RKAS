<?php

namespace Tests\Unit;

use App\Jobs\SendTelegramNotificationJob;
use App\Logging\TelegramLogHandler;
use Illuminate\Support\Facades\Bus;
use Monolog\Level;
use Monolog\LogRecord;
use Tests\TestCase;

class TelegramLogHandlerTest extends TestCase
{
    private TelegramLogHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->handler = new TelegramLogHandler(level: Level::Error);
    }

    public function test_dispatches_job_for_error(): void
    {
        Bus::fake();

        $record = new LogRecord(
            datetime: new \DateTimeImmutable,
            channel: 'local',
            level: Level::Error,
            message: 'Test error message',
            context: [],
            extra: [],
        );

        $this->handler->handle($record);

        Bus::assertDispatched(SendTelegramNotificationJob::class, function ($job) {
            return $job->level === 'ERROR'
                && $job->message === 'Test error message';
        });
    }

    public function test_dispatches_job_for_critical(): void
    {
        Bus::fake();

        $record = new LogRecord(
            datetime: new \DateTimeImmutable,
            channel: 'local',
            level: Level::Critical,
            message: 'Critical failure',
            context: [],
            extra: [],
        );

        $this->handler->handle($record);

        Bus::assertDispatched(SendTelegramNotificationJob::class, function ($job) {
            return $job->level === 'CRITICAL';
        });
    }

    public function test_dispatches_job_for_alert(): void
    {
        Bus::fake();

        $record = new LogRecord(
            datetime: new \DateTimeImmutable,
            channel: 'local',
            level: Level::Alert,
            message: 'Alert triggered',
            context: [],
            extra: [],
        );

        $this->handler->handle($record);

        Bus::assertDispatched(SendTelegramNotificationJob::class, function ($job) {
            return $job->level === 'ALERT';
        });
    }

    public function test_dispatches_job_for_emergency(): void
    {
        Bus::fake();

        $record = new LogRecord(
            datetime: new \DateTimeImmutable,
            channel: 'local',
            level: Level::Emergency,
            message: 'Emergency!',
            context: [],
            extra: [],
        );

        $this->handler->handle($record);

        Bus::assertDispatched(SendTelegramNotificationJob::class, function ($job) {
            return $job->level === 'EMERGENCY';
        });
    }

    public function test_does_not_dispatch_for_warning(): void
    {
        Bus::fake();

        $record = new LogRecord(
            datetime: new \DateTimeImmutable,
            channel: 'local',
            level: Level::Warning,
            message: 'Just a warning',
            context: [],
            extra: [],
        );

        $this->handler->handle($record);

        Bus::assertNotDispatched(SendTelegramNotificationJob::class);
    }

    public function test_does_not_dispatch_for_info_or_debug(): void
    {
        Bus::fake();

        $infoRecord = new LogRecord(
            datetime: new \DateTimeImmutable,
            channel: 'local',
            level: Level::Info,
            message: 'Info message',
            context: [],
            extra: [],
        );

        $debugRecord = new LogRecord(
            datetime: new \DateTimeImmutable,
            channel: 'local',
            level: Level::Debug,
            message: 'Debug message',
            context: [],
            extra: [],
        );

        $this->handler->handle($infoRecord);
        $this->handler->handle($debugRecord);

        Bus::assertNotDispatched(SendTelegramNotificationJob::class);
    }

    public function test_passes_context_and_extra_to_job(): void
    {
        Bus::fake();

        $record = new LogRecord(
            datetime: new \DateTimeImmutable,
            channel: 'local',
            level: Level::Error,
            message: 'Error with context',
            context: ['file' => 'test.php', 'line' => 42],
            extra: ['existing_key' => 'existing_value'],
        );

        $this->handler->handle($record);

        Bus::assertDispatched(SendTelegramNotificationJob::class, function ($job) {
            return $job->context === ['file' => 'test.php', 'line' => 42]
                && isset($job->extra['url'])
                && isset($job->extra['user_email'])
                && $job->extra['existing_key'] === 'existing_value';
        });
    }
}
