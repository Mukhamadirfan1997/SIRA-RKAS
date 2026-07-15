# Rencana: Generate Data Test RKAS + Transaksi + Kwitansi

## 1. Tujuan
Menguji kualitas & performa aplikasi SIRA-RKAS dengan data dalam jumlah besar.

## 2. Total Data (Aktual)

| Tabel | Jumlah | Keterangan |
|-------|--------|------------|
| `profil_sekolah` | 1 | 1 sekolah fokus test |
| `tahun_anggaran` | 1 | 2026 |
| `master_program` | 100 | 20 program × 5 sub-program |
| `master_kode_rekening` | 500 | Tersebar ke 5 jenis belanja |
| `sumber_dana` | 2 | Existing (BOSP-REG, BOSP-KIN) |
| `jenis_belanja` | 5 | Existing |
| `users` | 2 | 1 admin-kecamatan + 1 user sekolah |
| **`rkas_item`** | **50.000** | 1 sekolah, 1 tahun |
| **`rkas_item_bulan`** | **600.000** | 12 bulan per item |
| **`transaksi_bku`** | **25.000** | ~50% item punya transaksi pengeluaran |
| **`kwitansi`** | **25.000** | 1 kwitansi per transaksi |
| **Total** | **~700.310** | |

## 3. File

| File | Fungsi |
|------|--------|
| `database/seeders/TestDataSeeder.php` | Seeder utama — generate semua data test + cleanup |

## 4. Metode

- **DB::table()->insert()** batch — bukan Eloquent `create()`
- Nonaktifkan FK/Unique checks, event dispatcher
- 1 transaksi besar
- Pre-generate array data (hindari panggil Faker per row saat looping)

### Alur Eksekusi

```
TestDataSeeder::run()
├── 1. Buat reference data (sekolah, tahun, user, dll)
├── 2. Cek master program & kode rekening — fallback jika kosong
├── 3. DB::beginTransaction()
├── 4. FOREIGN_KEY_CHECKS=0, UNIQUE_CHECKS=0
├── 5. Model::unsetEventDispatcher()
├── 6. Generate 50.000 rkas_item (batch 1.000, 50×)
├── 7. Generate 600.000 rkas_item_bulan (batch 10.000, 60×)
├── 8. Generate 25.000 transaksi_bku (batch 1.000, 25×)
├── 9. Generate 25.000 kwitansi (batch 1.000, 25×)
├── 10. FOREIGN_KEY_CHECKS=1, UNIQUE_CHECKS=1
├── 11. Model::setEventDispatcher()
├── 12. DB::commit()
└── 13. Tampilkan summary
```

## 5. Detail Generator per Tabel

### `rkas_item` (50.000)

| Kolom | Sumber |
|-------|--------|
| `sekolah_id` | ID sekolah (fixed) |
| `tahun_anggaran_id` | ID tahun 2026 (fixed) |
| `no_urut` | 1–50.000 (sequential) |
| `uraian` | "Pembelian {barang} untuk {kegiatan}" (template rotation) |
| `program_id` | random dari **existing** master_program (atau fallback 30 record) |
| `kode_rekening_id` | random dari **existing** master_kode_rekening (atau fallback 50 record) |
| `sumber_dana_id` | random 1–2 |
| `volume` | random 1–100 |
| `satuan` | random: buah/paket/kali/set/lembar |
| `tarif` | random 1.000–100.000 |
| `jumlah` | `volume * tarif` |
| `created_at` / `updated_at` | `now()` |

### `rkas_item_bulan` (600.000)

| Kolom | Sumber |
|-------|--------|
| `rkas_item_id` | ID item (1–50.000) |
| `bulan` | 1–12 (fixed per item) |
| `rencana` | `jumlah / 12` dengan variasi musiman (±20%) |
| `created_at` / `updated_at` | `now()` |

### `transaksi_bku` (50.000)

| Kolom | Sumber |
|-------|--------|
| `sekolah_id` | ID sekolah (fixed) |
| `rkas_item_id` | random dari item yang masih punya sisa anggaran |
| `tanggal` | random tgl dalam 2026 |
| `bulan` | ekstrak dari tanggal |
| `no_bukti` | `BKU-2026-{00001..50000}` (unique) |
| `jenis` | `'pengeluaran'` |
| `jumlah` | min(10000, sisa_anggaran_item) |
| `toko_penerima` | random dari 8 toko |
| `metode_pengadaan` | random: siplah/non_siplah |
| `uraian` | "Pembayaran belanja" |
| `tahap` | 1 |
| `status_lunas` | true (80%) / false (20%) |
| `saldo_berjalan` | kumulatif berurutan |
| `created_by` | ID user sekolah test |
| `created_at` / `updated_at` | `now()` |

### `kwitansi` (50.000)

| Kolom | Sumber |
|-------|--------|
| `transaksi_bku_id` | ID transaksi (relasi 1:1) |
| `sekolah_id` | ID sekolah (fixed) |
| `nomor` | `KWT-2026-{00001..50000}` (unique) |
| `dicetak_pada` | null |
| `file_pdf_path` | null |
| `created_at` / `updated_at` | `now()` |

## 6. Opsi Cleanup

| Mode | Perintah | Aksi |
|------|----------|------|
| Generate | `php artisan db:seed --class=TestDataSeeder` | Buat semua data test |
| Purge | `php artisan db:seed --class=TestDataSeeder -- --purge` | Hapus SEMUA data test (dengan konfirmasi) |

Data yang dihapus saat purge:
1. `TRUNCATE kwitansi`
2. `TRUNCATE transaksi_bku`
3. `TRUNCATE rkas_item_bulan`
4. `TRUNCATE rkas_item`
5. `DELETE FROM users WHERE email LIKE '%@test-data.local'`
6. `DELETE FROM master_program WHERE kode LIKE 'TST-%'`
7. `DELETE FROM master_kode_rekening WHERE kode LIKE '5.TST.%'`
8. `DELETE FROM profil_sekolah WHERE npsn = '0000000000'`

## 7. Validasi Kualitas (Setelah Data Jadi)

1. Load halaman RKAS index — waktu render tabel
2. Filter by bulan — query join `rkas_item_bulan`
3. Cari realisasi — query SUM `transaksi_bku` per item (aksesor)
4. Cetak kwitansi — generate PDF via DomPDF
5. Export Excel — RekapRekeningExport, BkuExport
6. Query dashboard — ringkasan total anggaran, realisasi, sisa per sumber dana

## 8. Rollback Plan

**Metode aman: Backup & Restore MySQL**

```bash
# Backup sebelum test
mysqldump -u root sira_rkas > backup_sebelum_test.sql

# Generate test data
php artisan db:seed --class=TestDataSeeder

# Lakukan pengujian...

# Rollback ke kondisi awal
mysql -u root sira_rkas < backup_sebelum_test.sql
```

## 9. Controller Fixes untuk Large Dataset

Setelah test data digenerate, ditemukan beberapa controller bermasalah dengan ~700k records:

| Controller | Masalah | Solusi |
|-----------|---------|--------|
| `DashboardController` | `->get()` 50rb item + `whereIn` dengan 50rb ID | `paginate(50)`, SQL aggregates via subquery (`whereIn(..., $baseQuery->select('id'))`) |
| `RkasController` | `->get()` 50rb item + `whereIn` 50rb placeholder | `paginate(50)`, subquery untuk aggregate totals |
| `TransaksiBkuController` | `->get()` semua transaksi + filter di PHP | `paginate(50)`, SQL `SUM(CASE ...)` untuk saldoAwal |
| `LaporanController` (loadRekapRekeningItems / loadKuartalItems) | `whereIn` 50rb placeholder | ganti dengan subquery `whereIn('id', $cloneQuery->select('id'))` |

### Prinsip Fix
- **Pagination**: halaman index wajib `paginate(50)` bukan `->get()->take(50)`
- **SQL aggregates**: total/rencana/realisasi dihitung via `SUM()` di database, bukan `->get()->reduce(...)` di PHP
- **Subquery instead of WHERE IN list**: gunakan `whereIn('col', $builder->select('id'))` (subquery) bukan `whereIn('col', $collection->toArray())` (ribuan placeholder)
- **Dynamic props**: data per-item yang tidak tersedia via eager loading dihitung di loop setelah pagination
