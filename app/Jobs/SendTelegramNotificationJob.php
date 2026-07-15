<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SendTelegramNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public string $level;
    public string $message;
    public array $context;
    public array $extra;

    public function __construct(string $level, string $message, array $context = [], array $extra = [])
    {
        $this->level = $level;
        $this->message = $message;
        $this->context = $context;
        $this->extra = $extra;
    }

    public function handle(): void
    {
        $lock = Cache::lock('telegram-notification', 5);

        if (!$lock->get()) {
            return;
        }

        try {
            $botToken = config('logging.telegram_bot_token') ?? env('TELEGRAM_BOT_TOKEN');
            $chatId = config('logging.telegram_chat_id') ?? env('TELEGRAM_CHAT_ID');

            if (empty($botToken) || empty($chatId)) {
                return;
            }

            $text = $this->formatMessage();

            Http::timeout(5)->post("https://api.telegram.org/bot{$botToken}/sendMessage", [
                'chat_id' => $chatId,
                'text' => $text,
                'parse_mode' => 'HTML',
            ]);
        } finally {
            $lock->release();
        }
    }

    protected function formatMessage(): string
    {
        $emoji = match (strtoupper($this->level)) {
            'EMERGENCY' => '💀',
            'ALERT'     => '🚨',
            'CRITICAL'  => '🔴',
            'ERROR'     => '❌',
            'WARNING'   => '⚠️',
            'NOTICE'    => '📢',
            'INFO'      => 'ℹ️',
            'DEBUG'     => '🐛',
            default     => '❓',
        };

        $appEnv = config('app.env', 'local');
        $now = now()->format('Y-m-d H:i:s');
        $url = $this->extra['url'] ?? 'N/A';
        $user = $this->extra['user_email'] ?? 'Guest';

        $lines = [
            "<b>{$emoji} [{$this->level}]</b>",
            "<b>Waktu:</b> {$now}",
            "<b>Lingkungan:</b> {$appEnv}",
            "<b>Pesan:</b> {$this->message}",
            "<b>URL:</b> {$url}",
            "<b>User:</b> {$user}",
        ];

        return implode("\n", $lines);
    }
}
