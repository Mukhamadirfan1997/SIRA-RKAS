# Planning: Telegram Real-time Logger & Alerting System

## 1. Tujuan
Mengirim notifikasi error/critical/alert/emergency dari aplikasi ke grup Telegram secara real-time via queue, tanpa mengganggu performa request user.

## 2. File yang akan dibuat

| File | Fungsi |
|------|--------|
| `app/Logging/TelegramLogHandler.php` | Custom Monolog handler — membaca log record, lalu dispatch job |
| `app/Jobs/SendTelegramNotificationJob.php` | Queue job — HTTP POST ke Telegram Bot API |

## 3. File yang akan diubah

| File | Perubahan |
|------|-----------|
| `config/logging.php` | Tambah channel `telegram` di array `channels`, tambahkan ke `stack` |
| `.env` | Tambah `TELEGRAM_BOT_TOKEN`, `TELEGRAM_CHAT_ID`, `LOG_TELEGRAM_LEVEL` |
| `.env.example` | Tambah 3 key yang sama untuk dokumentasi |

## 4. Arsitektur Alur

```
Log::error('pesan')
  → Monolog memproses semua channel di stack
    → StreamHandler → tulis ke laravel.log (existing, unchanged)
    → TelegramLogHandler (baru)
        → cek level >= threshold (error/critical/alert/emergency)
        → dispatch SendTelegramNotificationJob ke queue database
            → job: format pesan HTML
            → job: Http::timeout(2)->post(Telegram API)
            → job: rate limiting via Cache::lock()
```

## 5. Format Pesan Telegram (HTML parse mode)

```
<b>🔴 [CRITICAL]</b>
<b>Waktu:</b> 2026-07-14 10:30:00
<b>Lingkungan:</b> production
<b>Pesan:</b> SQLSTATE[HY000] - Connection refused
<b>URL:</b> /rkas/import
<b>User:</b> admin@sekolah.sch.id
```

## 6. Konfigurasi Logging

```php
'channels' => [
    // ... existing channels ...

    'telegram' => [
        'driver' => 'monolog',
        'handler' => App\Logging\TelegramLogHandler::class,
        'level' => env('LOG_TELEGRAM_LEVEL', 'error'),
    ],
],
```

Stack channel diupdate jadi:

```php
'stack' => [
    'driver' => 'stack',
    'channels' => explode(',', env('LOG_STACK', 'single')),
    'ignore_exceptions' => false,
],
```

Dan `telegram` channel ditambahkan sebagai channel terpisah di LOG_STACK env.

## 7. Environment Variables

```ini
TELEGRAM_BOT_TOKEN=
TELEGRAM_CHAT_ID=
LOG_TELEGRAM_LEVEL=error
```

## 8. Rate Limiting Strategy

Pakai `Cache::lock('telegram-notification', 5)` di job — agar dalam banjir error hanya 1 pesan per 5 detik yang dikirim ke Telegram.

## 9. Test Plan

- Test handler: pastikan hanya level >= error yang dispatch job
- Test job: pastikan format HTML valid, HTTP call pakai fake `Http::fake()`
- Test rate limiting: pastikan job kedua dalam 5 detik tidak dikirim

## 10. Rollback Plan

Jika terjadi masalah:
1. Hapus `telegram` dari array stack di `config/logging.php`
2. Queue worker tetap jalan, pesan hanya akan gagal + masuk `failed_jobs`
3. Semua fitur aplikasi tetap normal
