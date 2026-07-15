# SIRA-RKAS

Sistem Informasi Rencana Anggaran Kegiatan Sekolah — aplikasi manajemen RKAS berbasis web untuk sekolah dan kecamatan.

## Fitur

- **Manajemen RKAS** — CRUD item RKAS per sekolah, realisasi dari transaksi BKU, filter program/bulan
- **Transaksi BKU** — Buku Kas Umum dengan cetak kwitansi (PDF), metode pengadaan (SIPLAH/Non-SIPLAH)
- **Laporan** — BKU Bulanan, Rekap per Kode Rekening, Rekap Kuartal, Rekap SIPLAH (web preview, PDF, Excel)
- **Import RKAS** — Upload file Excel dengan queue job, log status import
- **Dashboard** — Sisa anggaran, tren realisasi 12 bulan, pengingat kelengkapan data
- **Admin Kecamatan** — Kelola profil sekolah, user sekolah, master program & kode rekening
- **Role-based access** — Admin kecamatan (lihat semua sekolah) & Sekolah (hanya data sendiri)
- **Notifikasi Error (Telegram)** — Monitoring error via bot Telegram

## Tech Stack

- **Backend**: Laravel 11, PHP 8.2
- **Database**: MySQL 8.4 (WAL mode untuk fallback SQLite di test)
- **Frontend**: Blade, Tailwind CSS, Chart.js, Alpine.js
- **Export**: Laravel Excel (Maatwebsite), DomPDF
- **Queue**: Database driver
- **Auth**: Laravel Breeze (Blade)
- **Role/Permission**: Spatie Laravel Permission

## Requirements

- PHP 8.1+
- MySQL 8.4+ / SQLite (test only)
- Composer
- Node.js & NPM (untuk frontend build)

## Instalasi

```bash
git clone <repo-url>
cd sira-rkas
composer install
npm install && npm run build
cp .env.example .env
php artisan key:generate
```

Konfigurasi database di `.env`:
```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=sira_rkas
DB_USERNAME=root
DB_PASSWORD=
```

Jalankan migrasi dan seeder:
```bash
php artisan migrate
php artisan db:seed
```

## Seeder Data Besar (Stress Test)

Untuk generate 2.1 juta records (3 tahun data dummy):
```bash
php artisan db:seed --class=TestDataSeeder --force
```

## Development

```bash
# Jalankan queue worker (untuk import & Telegram)
php artisan queue:work

# Compile assets
npm run dev

# Tests
php artisan test
```

## Deployment

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize
```

Pastikan queue worker berjalan di production:
```bash
php artisan queue:work --queue=default --sleep=3 --tries=3
```

## License

MIT
