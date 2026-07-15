<?php

namespace Tests\Unit;

use App\Jobs\SendTelegramNotificationJob;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class TelegramLogHandlerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Queue::fake();
    }

    public function test_error_level_dispatches_job(): void
    {
        Log::error('Test error message', ['key' => 'value']);

        Queue::assertPushed(SendTelegramNotificationJob::class, function ($job) {
            return $job->level === 'ERROR'
                && $job->message === 'Test error message'
                && $job->context === ['key' => 'value'];
        });
    }

    public function test_critical_level_dispatches_job(): void
    {
        Log::critical('Test critical message');

        Queue::assertPushed(SendTelegramNotificationJob::class, function ($job) {
            return $job->level === 'CRITICAL'
                && $job->message === 'Test critical message';
        });
    }

    public function test_alert_level_dispatches_job(): void
    {
        Log::alert('Test alert message');

        Queue::assertPushed(SendTelegramNotificationJob::class, function ($job) {
            return $job->level === 'ALERT'
                && $job->message === 'Test alert message';
        });
    }

    public function test_emergency_level_dispatches_job(): void
    {
        Log::emergency('Test emergency message');

        Queue::assertPushed(SendTelegramNotificationJob::class, function ($job) {
            return $job->level === 'EMERGENCY'
                && $job->message === 'Test emergency message';
        });
    }

    public function test_warning_level_does_not_dispatch_job(): void
    {
        Log::warning('Test warning message');

        Queue::assertNotPushed(SendTelegramNotificationJob::class);
    }

    public function test_info_level_does_not_dispatch_job(): void
    {
        Log::info('Test info message');

        Queue::assertNotPushed(SendTelegramNotificationJob::class);
    }

    public function test_debug_level_does_not_dispatch_job(): void
    {
        Log::debug('Test debug message');

        Queue::assertNotPushed(SendTelegramNotificationJob::class);
    }

    public function test_notice_level_does_not_dispatch_job(): void
    {
        Log::notice('Test notice message');

        Queue::assertNotPushed(SendTelegramNotificationJob::class);
    }
}
