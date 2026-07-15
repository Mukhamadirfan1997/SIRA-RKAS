<?php

namespace Tests\Unit;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SendTelegramNotificationJobTest extends TestCase
{
    private string $botToken = '8773817502:AAHmg5wCKEGdiPLDFV0fESiSfy_-SKVKa78';
    private string $chatId = '-4440074532';

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('logging.telegram_bot_token', $this->botToken);
        config()->set('logging.telegram_chat_id', $this->chatId);
    }

    public function test_job_sends_http_post_to_telegram(): void
    {
        Http::fake();

        $job = new \App\Jobs\SendTelegramNotificationJob(
            level: 'ERROR',
            message: 'Something went wrong',
            context: ['file' => 'test.php'],
            extra: [],
        );

        $job->handle();

        Http::assertSent(function ($request) {
            $expectedUrl = "https://api.telegram.org/bot{$this->botToken}/sendMessage";

            return $request->url() === $expectedUrl
                && $request->method() === 'POST'
                && $request['chat_id'] === $this->chatId
                && $request['parse_mode'] === 'HTML'
                && str_contains($request['text'], '[ERROR]')
                && str_contains($request['text'], 'Something went wrong');
        });
    }

    public function test_job_formats_message_correctly(): void
    {
        Http::fake();

        $job = new \App\Jobs\SendTelegramNotificationJob(
            level: 'CRITICAL',
            message: 'Database connection failed',
        );

        $job->handle();

        Http::assertSent(function ($request) {
            $text = $request['text'];

            $this->assertStringContainsString('[CRITICAL]', $text);
            $this->assertStringContainsString('Database connection failed', $text);
            $this->assertStringContainsString('<b>', $text);
            $this->assertStringContainsString('Waktu:', $text);
            $this->assertStringContainsString('Lingkungan:', $text);
            $this->assertStringContainsString('Pesan:', $text);

            return true;
        });
    }

    public function test_job_does_nothing_if_token_empty(): void
    {
        config()->set('logging.telegram_bot_token', '');

        Http::fake();

        $job = new \App\Jobs\SendTelegramNotificationJob(
            level: 'ERROR',
            message: 'test',
        );

        $job->handle();

        Http::assertNothingSent();
    }

    public function test_job_does_nothing_if_chat_id_empty(): void
    {
        config()->set('logging.telegram_chat_id', '');

        Http::fake();

        $job = new \App\Jobs\SendTelegramNotificationJob(
            level: 'ERROR',
            message: 'test',
        );

        $job->handle();

        Http::assertNothingSent();
    }

    public function test_rate_limiting_prevents_duplicate_within_5_seconds(): void
    {
        Http::fake();

        $lock = Cache::lock('telegram-notification', 5);
        $lock->get();

        $job = new \App\Jobs\SendTelegramNotificationJob('ERROR', 'Second error');
        $job->handle();

        $lock->release();

        Http::assertNothingSent();
    }

    public function test_rate_limiting_allows_when_no_active_lock(): void
    {
        Http::fake();

        $job = new \App\Jobs\SendTelegramNotificationJob('ERROR', 'First error');
        $job->handle();

        Http::assertSentCount(1);
    }

    public function test_different_levels_have_correct_emoji(): void
    {
        $levels = [
            'EMERGENCY' => '💀',
            'ALERT'     => '🚨',
            'CRITICAL'  => '🔴',
            'ERROR'     => '❌',
        ];

        foreach ($levels as $level => $emoji) {
            Http::fake();

            $job = new \App\Jobs\SendTelegramNotificationJob($level, "Test $level");
            $job->handle();

            Http::assertSent(function ($request) use ($emoji) {
                return str_contains($request['text'], $emoji);
            });

            Cache::forget('telegram-notification');
        }
    }
}
