# PRD (Product Requirements Document)
# Aplikasi Realisasi RKAS Tingkat Kecamatan

**Nama Produk:** SIRA-RKAS (Sistem Informasi Realisasi Anggaran RKAS)
**Studi kasus rujukan:** UPT SDN Toyaning I Rejoso, Kab. Pasuruan (NPSN 20519260) — TA 2026
**Tech Stack:** Laravel 11 + MySQL 8
**Versi Dokumen:** 2.0 (Revisi: multi-sekolah tingkat kecamatan, import RKAS per bulan via queue)
**Tanggal:** 07 Juli 2026

---

## Riwayat Revisi

| Versi | Tanggal | Perubahan |
|---|---|---|
| 1.0 | 03 Jul 2026 | Draf awal: single-tenant (1 sekolah), anggaran per Tahap 1/2 |
| 2.0 | 07 Jul 2026 | Naik level jadi **tingkat kecamatan (multi-sekolah)**; anggaran diubah dari per-tahap jadi **per bulan**; ditambahkan fitur **import RKAS bulanan via satu tombol dengan queue**; sementara **1 akun per sekolah** (tanpa approval berjenjang) |

---

## 1. Latar Belakang

Sebelumnya aplikasi ini dirancang untuk kebutuhan internal satu sekolah saja. Cakupan kini diperluas ke **tingkat kecamatan**: satu instalasi aplikasi digunakan bersama oleh seluruh SD di kecamatan tersebut, masing-masing dengan data yang terpisah (scoped per sekolah).

Selain itu, siklus perencanaan anggaran berubah dari **per Tahap (Tahap 1 & 2)** menjadi **per bulan**, mengikuti kebutuhan sekolah untuk memantau realisasi lebih granular dan mengimpor ulang RKAS setiap bulan berjalan (mis. akibat revisi/pergeseran anggaran bulanan).

Aplikasi ini tetap **tidak menggantikan atau terintegrasi dengan ARKAS** milik Kemendikbud — pelaporan resmi ke Dinas Pendidikan tetap dilakukan lewat ARKAS seperti biasa. Aplikasi ini murni alat bantu internal kecamatan/sekolah untuk memantau sisa anggaran secara cepat dan rapi.

**Masalah utama (masih berlaku + tambahan):**
- Tidak ada cara cepat melihat **sisa anggaran per item RKAS**, sehingga bendahara harus mencocokkan manual antara RKAS dan BKU.
- Tidak ada **pengelompokan otomatis** antara Belanja Barang, Jasa, Modal, dan Modal Aset Tetap Lainnya.
- Rekap per **Kode Kegiatan**, **Kode Program**, dan **Kode Rekening** dilakukan manual, rawan salah rumus.
- Data tidak terpusat antar sekolah — Admin Kecamatan kesulitan memantau progres realisasi seluruh sekolah sekaligus.
- **[Baru]** Import RKAS bulanan masih manual satu-satu per sekolah; dibutuhkan proses **impor massal dengan satu tombol** yang tidak membebani server (file besar / banyak sekolah sekaligus) → perlu **antrian (queue)**.

---

## 2. Tujuan Produk

1. Menyediakan **satu sumber data** untuk rencana anggaran (RKAS bulanan) dan realisasi (BKU/Kwitansi), terpisah rapi per sekolah.
2. Menampilkan **sisa anggaran real-time** per item, per kode rekening, per kegiatan, per program, **per bulan**, dan per sekolah.
3. Mengklasifikasikan otomatis setiap transaksi ke kategori: Barang, Jasa, Modal Peralatan & Mesin, Modal Buku, Modal Aset Tetap Lainnya, dll.
4. Memungkinkan **Admin Kecamatan** memantau progres realisasi seluruh sekolah dalam satu dashboard.
5. Menyediakan **import RKAS per bulan** yang cepat, andal, dan tidak memblokir aplikasi (proses di background via queue), dengan **satu tombol** saja.
6. Menyediakan laporan siap cetak/ekspor untuk kebutuhan internal sekolah/kecamatan.

## 3. Target Pengguna

Untuk saat ini, **setiap sekolah hanya memiliki 1 akun** (belum ada pemisahan Operator/Bendahara/Kepala Sekolah secara login terpisah). Struktur role dibuat tetap fleksibel untuk berkembang ke multi-user per sekolah di fase berikutnya.

| Role | Cakupan Data | Hak Akses Utama |
|---|---|---|
| **Akun Sekolah** (1 akun/sekolah) | Hanya data sekolah sendiri (`sekolah_id` miliknya) | Import RKAS bulanan, input transaksi BKU, cetak kwitansi, lihat dashboard & laporan sekolahnya |
| **Admin Kecamatan** | Semua sekolah | Kelola master kode rekening & program standar, monitoring rekap seluruh sekolah, kelola akun sekolah (buat/reset akun) |

> **Catatan desain:** Pembatasan "1 akun per sekolah" adalah keputusan proses bisnis saat ini, bukan pembatasan arsitektur. Skema `users` tetap memakai kolom `role_id` + `sekolah_id`, sehingga menambah akun kedua (mis. Bendahara terpisah dari Kepala Sekolah) di kemudian hari tidak memerlukan perubahan skema — cukup tambah baris `users` baru dan (jika perlu) role baru untuk approval berjenjang.

## 4. Ruang Lingkup

### In-Scope (MVP)
- Multi-sekolah dalam satu instalasi (data terpisah per `sekolah_id`), dikelola oleh Admin Kecamatan.
- Master data: Tahun Anggaran, Profil Sekolah (banyak sekolah), Sumber Dana, Kode Program (hierarkis), Kode Rekening + Jenis Belanja — kode rekening/program bisa jadi **master standar kecamatan** (dipakai bersama semua sekolah) atau override per sekolah bila perlu.
- **RKAS disusun per bulan** (bukan per Tahap 1/2 lagi).
- **Import RKAS bulanan via Excel, satu tombol, diproses di background (queue)** — lihat detail di Bagian 8.
- Input & kelola transaksi BKU (realisasi) harian, link wajib ke item RKAS bulan terkait.
- Perhitungan otomatis: **Sisa = Rencana Bulan − Realisasi Bulan**, per item, per rekening, per kegiatan, per program, **per bulan**, dan per sekolah.
- Dashboard klasifikasi Barang/Jasa/Modal/Aset Tetap Lainnya, dengan filter tambahan **per sekolah** (untuk Admin Kecamatan) dan **per bulan**.
- Cetak kwitansi (PDF) mengikuti format & penomoran seperti kwitansi Excel saat ini.
- Laporan BKU per bulan dan rekap realisasi per kode rekening.
- Log & status import (riwayat upload per sekolah per bulan, sukses/gagal).

### Out-of-Scope (Tidak Dibutuhkan)
- Integrasi/API ke aplikasi ARKAS atau sistem Dinas Pendidikan — pelaporan ke Dinas tetap manual melalui ARKAS.
- Approval berjenjang (Operator → Bendahara → Kepala Sekolah) — ditunda ke fase lanjutan, karena saat ini 1 akun per sekolah.

### Out-of-Scope (Fase Lanjutan, opsional)
- Multi-user per sekolah + approval berjenjang.
- Modul pajak otomatis (PPh/PPN).
- Aplikasi mobile native.
- Notifikasi real-time (WebSocket/Pusher) untuk status import — MVP cukup polling status.

---

## 5. Struktur Data Sumber (Hasil Analisis Dokumen Awal)

### 5.1 RKAS (kini per bulan, bukan per tahap)
Setiap baris rincian tetap punya kolom: `No Urut`, `Uraian`, `Volume`, `Satuan`, `Tarif/Harga`, `Jumlah Total`, `Kode Rekening`, `Kode Program`. Kolom `Tahap 1`/`Tahap 2` **digantikan** dengan rencana per bulan (`Bulan 1`...`Bulan 12`, atau 1 kolom `Bulan` + `Rencana` bila 1 file = 1 bulan).

Struktur kode berjenjang (Kode Program) dan pola prefix Kode Rekening → Jenis Belanja **tidak berubah** dari versi 1.0 (lihat referensi mapping di bawah), tetap sebagai master data yang bisa diimpor/diedit admin, bukan hardcode:

| Prefix Kode Rekening | Jenis Belanja (contoh) |
|---|---|
| `5.1.02.01.01.x` | Belanja Barang Persediaan |
| `5.1.02.02.01.x` | Belanja Jasa (honor, sewa, daya & jasa) |
| `5.1.02.03.02.x` / `5.1.02.03.03.x` | Belanja Jasa Pemeliharaan |
| `5.1.02.04.01.x` | Belanja Perjalanan Dinas |
| `5.2.02.10.01.x` / `5.2.02.10.02.x` | Belanja Modal Peralatan & Mesin |
| `5.2.05.01.01.x` | Belanja Modal Buku |
| `5.2.02.05.01.x` | Belanja Modal Aset Tetap Lainnya |

### 5.2 BKU (Buku Kas Umum) — tidak berubah
Kolom: `Tanggal`, `Kode Kegiatan`, `Kode Rekening`, `No. Bukti`, `Uraian`, `Saldo Tahun Lalu`, `Penerimaan`, `Pengeluaran`, `Saldo` (berjalan), `Toko/Penerima`, `Lunas Bayar`, `Uraian Lengkap`. Kolom `bulan` ditambahkan (atau diturunkan dari `tanggal`) agar bisa dicocokkan langsung ke rencana bulan yang sesuai.

### 5.3 Kwitansi (Bukti Pembayaran) — tidak berubah
Field: `No`, `Kegiatan`, `Program`, `Sub Program`, `Uraian`, `Terima Dari`, `Sebesar`, `Untuk`, tempat/tanggal, tanda tangan Kepala Sekolah/Bendahara/Penerima.

---

## 6. Model Data (Skema Database — Ringkasan ERD, Revisi)

```
tahun_anggaran         (id, tahun, status)

kecamatan              (id, nama)
profil_sekolah         (id, npsn, nama, alamat, kecamatan_id, kabupaten, provinsi,
                         nama_kepsek, nip_kepsek, nama_bendahara, nip_bendahara)
                        -- kini banyak baris (1 per sekolah dalam kecamatan)

sumber_dana            (id, kode, nama)                         -- ex: BOSP Reguler

master_program         (id, kode, nama, parent_id, level)       -- master standar kecamatan
master_kode_rekening   (id, kode, nama, jenis_belanja_id)       -- master standar kecamatan
jenis_belanja          (id, nama)                                -- Barang, Jasa, Modal Peralatan,
                                                                  -- Modal Buku, Modal Aset Tetap Lainnya

rkas_item              (id, sekolah_id, tahun_anggaran_id, no_urut, uraian,
                         program_id, kode_rekening_id,
                         volume, satuan, tarif, jumlah_total)

rkas_item_bulan        (id, rkas_item_id, bulan (1-12), rencana)
                        -- pecahan rencana per bulan; menggantikan rencana_tahap1/2

transaksi_bku          (id, sekolah_id, rkas_item_id, tanggal, bulan, no_bukti,
                         jenis (penerimaan/pengeluaran), jumlah,
                         toko_penerima, uraian, status_lunas,
                         saldo_berjalan, created_by)

kwitansi               (id, transaksi_bku_id, nomor, dicetak_pada, file_pdf_path)

import_log             (id, sekolah_id, tahun_anggaran_id, bulan, file_name,
                         status (pending/processing/success/failed),
                         total_baris, baris_berhasil, baris_gagal,
                         error_detail, uploaded_by, created_at, finished_at)

users                  (id, name, email, password, sekolah_id nullable, role_id)
roles                  (id, nama)                                -- "Sekolah", "Admin Kecamatan"
audit_log              (id, user_id, tabel, aksi, data_lama, data_baru, created_at)
```

**Relasi & perhitungan kunci:**
- `rkas_item` 1—N `rkas_item_bulan` → rencana anggaran dipecah per bulan.
- `rkas_item` 1—N `transaksi_bku` (di-scope juga oleh `bulan`) → realisasi dicocokkan ke bulan yang sama.
- `sisa_anggaran_bulan = rkas_item_bulan.rencana − SUM(transaksi_bku.jumlah WHERE bulan sama, jenis=pengeluaran)`.
- Semua tabel transaksional (`rkas_item`, `transaksi_bku`, `kwitansi`, `import_log`) memiliki `sekolah_id` dan **wajib** dibatasi lewat **global scope** berdasarkan sekolah user yang login (kecuali Admin Kecamatan).

---

## 7. Fitur Utama

### A. Master Data
- CRUD Tahun Anggaran, Kecamatan, Profil Sekolah (oleh Admin Kecamatan).
- CRUD Master Program & Master Kode Rekening + mapping Jenis Belanja — berlaku standar untuk seluruh sekolah di kecamatan (dikelola Admin Kecamatan, bisa import massal Excel/CSV).
- Kelola akun sekolah (buat akun baru per sekolah, reset password) oleh Admin Kecamatan.

### B. Perencanaan Anggaran (RKAS Bulanan)
- Tabel rincian anggaran **per bulan**, filter per Program/Kegiatan/Kode Rekening/Jenis Belanja/Sekolah.
- Validasi total rencana bulan vs total anggaran tahunan item (jika ada acuan tahunan).

### C. Import RKAS Bulanan — Satu Tombol + Queue *(fitur baru utama)*
- Tombol tunggal **"Import RKAS Bulan Ini"**: user pilih Tahun Anggaran + Bulan, upload 1 file Excel, klik import.
- File langsung disimpan, baris `import_log` dibuat berstatus `pending`, lalu **job di-dispatch ke queue** — halaman tidak perlu menunggu proses selesai.
- Proses membaca file dilakukan **per-chunk** (mis. 200 baris/batch) agar file besar tidak timeout dan tidak membebani memori server.
- **Idempotensi**: bila sekolah mengimpor ulang bulan yang sama (revisi anggaran bulan berjalan), sistem menghapus/mengganti data `rkas_item_bulan` lama untuk kombinasi sekolah+bulan+tahun tersebut sebelum menyisipkan data baru — dibungkus dalam transaksi database agar tidak ada data setengah jalan bila terjadi kegagalan.
- Setelah job selesai, `import_log` diperbarui (`success`/`failed`, jumlah baris berhasil/gagal, detail error per baris) dan user melihat status ini di halaman riwayat import (via polling AJAX sederhana; bisa upgrade ke notifikasi real-time di fase lanjutan).
- Validasi baris: kolom wajib, format kode rekening/program harus dikenali di master, angka rencana tidak boleh negatif — baris yang gagal dicatat di `error_detail`, baris yang valid tetap diproses (bukan all-or-nothing per file, supaya 1 baris salah tidak menggagalkan seluruh bulan).

### D. Realisasi & BKU
- Form input transaksi harian → pilih item RKAS bulan terkait (dropdown pencarian, otomatis scoped ke sekolah user & bulan berjalan).
- Perhitungan saldo berjalan otomatis.
- Validasi transaksi tidak melebihi sisa anggaran bulan berjalan (dengan opsi override + catatan).
- Cetak Kwitansi PDF otomatis (nomor urut auto-generate, terbilang otomatis), termasuk cetak batch.

### E. Monitoring Sisa Anggaran *(fitur inti)*
- **Dashboard Sisa Anggaran per Bulan**: Rencana, Realisasi, Sisa, % Terserap — filter per Program, Kegiatan, Kode Rekening, Jenis Belanja, **Bulan**, dan (khusus Admin Kecamatan) per Sekolah.
- Badge: item belum direalisasikan, hampir habis (>90%), over budget.
- **Dashboard Kecamatan** (khusus Admin Kecamatan): ringkasan progres realisasi seluruh sekolah dalam satu tampilan (mis. tabel: Sekolah | Total Rencana Bulan Ini | Realisasi | Sisa | Status Import).

### F. Pelaporan
- Laporan BKU per bulan (cetak PDF).
- Rekap realisasi per Kode Rekening per Bulan.
- Riwayat log import (siapa, kapan, bulan berapa, berhasil/gagal berapa baris).
- Ekspor Excel/PDF.

### G. User & Akses
- 1 akun per sekolah, scoped otomatis lewat `sekolah_id`.
- Akun Admin Kecamatan lintas sekolah.
- Audit trail perubahan data RKAS & transaksi.
- *(Fase lanjutan)* Multi-user per sekolah + approval berjenjang.

---

## 8. Alur Proses Utama (Revisi)

```
1. Admin Kecamatan siapkan master Kode Rekening, Kode Program, dan akun tiap sekolah
        ↓
2. Setiap bulan, akun Sekolah klik "Import RKAS Bulan Ini" → upload 1 file Excel
        ↓
3. Sistem simpan import_log (status: pending) → job di-dispatch ke queue
        ↓
4. Worker queue memproses file per-chunk:
   - hapus data rkas_item_bulan lama utk sekolah+bulan+tahun ini (jika re-import)
   - insert/update rkas_item & rkas_item_bulan baru
   - baris gagal dicatat, baris sukses tetap masuk
        ↓
5. import_log diupdate (success/failed + detail) → user cek status di halaman riwayat
        ↓
6. Akun Sekolah input transaksi realisasi harian → pilih item RKAS bulan terkait
        ↓
7. Sistem hitung saldo berjalan + update sisa anggaran bulan berjalan secara real-time
        ↓
8. Akun Sekolah cetak kwitansi per transaksi (atau batch)
        ↓
9. Admin Kecamatan pantau dashboard rekap seluruh sekolah; Akun Sekolah pantau dashboard sendiri
        ↓
10. Akhir bulan/tahun → generate laporan rekap untuk arsip/pertanggungjawaban internal
```

---

## 9. Kebutuhan Non-Fungsional (Revisi)

| Aspek | Kebutuhan |
|---|---|
| **Keamanan** | Auth Laravel (Breeze/Jetstream), role & permission (`spatie/laravel-permission`), **global scope wajib per `sekolah_id`** agar data antar sekolah tidak bocor, HTTPS wajib |
| **Audit** | Semua perubahan RKAS & transaksi tercatat (siapa, kapan, apa, sekolah mana) |
| **Backup** | Backup database otomatis harian (`spatie/laravel-backup`) |
| **Antrian (Queue)** | Wajib pakai queue driver **database** atau **Redis** (bukan `sync`) untuk proses import; worker dijalankan via `php artisan queue:work` (idealnya di-supervise, mis. Supervisor/systemd) |
| **Performa** | Mendukung puluhan sekolah × ratusan item RKAS × ribuan transaksi/bulan tanpa lag pada dashboard (index database di `sekolah_id`, `bulan`, kolom filter utama) |
| **Kompatibilitas Data** | Import Excel per bulan (`maatwebsite/excel`, `WithChunkReading` + `ShouldQueue`) dengan format kolom sesuai template yang ditentukan |
| **Cetak** | PDF kwitansi & laporan (`barryvdh/laravel-dompdf`) |
| **Responsif** | UI dapat diakses dari laptop maupun tablet/HP |
| **Multi Tahun & Multi Bulan** | Data RKAS & realisasi per tahun+bulan terpisah tapi bisa dibandingkan historis antar bulan/tahun |
| **Idempotensi Import** | Re-import bulan yang sama harus mengganti data lama, bukan menumpuk duplikat |

---

## 10. Rekomendasi Tumpukan Teknologi (Tech Stack)

- **Backend:** Laravel 11, PHP 8.3
- **Database:** MySQL 8
- **Queue:** Database queue driver (MVP, cukup untuk skala kecamatan) — bisa upgrade ke Redis bila volume import bertambah besar
- **Frontend:** Blade + Livewire/Alpine.js
- **Auth & Role:** Laravel Breeze + `spatie/laravel-permission`
- **Import/Export Excel:** `maatwebsite/excel` (dengan `ToModel`, `WithHeadingRow`, `WithChunkReading`, `ShouldQueue`, `WithValidation`)
- **Cetak PDF:** `barryvdh/laravel-dompdf`
- **Chart Dashboard:** Chart.js / ApexCharts
- **Hosting:** VPS dengan PHP 8.3 + MySQL 8 + proses queue worker aktif (mis. via Supervisor)

---

## 11. Roadmap / Fase Rilis (Revisi)

| Fase | Cakupan |
|---|---|
| **MVP (Fase 1)** | Multi-sekolah (1 akun/sekolah), master data standar kecamatan, **RKAS per bulan**, **import Excel per bulan via queue (satu tombol)**, input realisasi, dashboard sisa anggaran per sekolah & per kecamatan, cetak kwitansi, log import |
| **Fase 2** | Multi-user per sekolah + approval berjenjang, notifikasi sisa anggaran, rekap pajak, notifikasi real-time status import |
| **Fase 3** | Analitik lanjutan (tren belanja antar bulan/tahun/sekolah), aplikasi mobile companion, otomasi backup ke cloud |

---

## 12. Risiko & Mitigasi (Revisi)

| Risiko | Mitigasi |
|---|---|
| Format & kode rekening BOSP berubah tiap tahun ajaran | Master Kode Rekening/Program fleksibel, dikelola terpusat oleh Admin Kecamatan |
| Format file Excel RKAS berbeda antar sekolah/rilis | Sediakan 1 template import standar wajib dipakai semua sekolah + validasi baris saat upload |
| File besar / banyak sekolah import bersamaan membebani server | Proses import via **queue + chunk reading**, bukan proses langsung (synchronous) |
| Re-import bulan yang sama menyebabkan data ganda | Idempotensi: hapus/replace data bulan tersebut sebelum insert ulang, dibungkus transaksi DB |
| Data bocor antar sekolah (multi-tenant) | Global scope wajib di semua model transaksional berdasarkan `sekolah_id` user login |
| 1 akun per sekolah rentan disalahgunakan (tidak ada cek-silang) | Audit trail tetap aktif; approval berjenjang jadi prioritas Fase 2 begitu kebutuhan multi-user muncul |
| Resistensi pengguna (bendahara terbiasa Excel) | UI dibuat semirip mungkin alur Excel (kwitansi, BKU), pelatihan singkat per sekolah |

---

## 13. Metrik Keberhasilan (Success Metrics)

- Waktu pencarian "sisa anggaran item tertentu" turun jadi < 5 detik (search di dashboard).
- 100% transaksi realisasi tercatat terhubung ke item RKAS bulan yang benar (tidak ada transaksi "orphan").
- Proses import RKAS bulanan tidak membuat halaman aplikasi freeze/timeout, walau file berisi ratusan baris.
- Admin Kecamatan bisa melihat status realisasi seluruh sekolah dalam satu dashboard tanpa perlu buka data tiap sekolah satu-satu.

---

## 14. Instruksi Penyesuaian Kode Lokal (Existing) — Migrasi Aman, Minim Error

Bagian ini khusus untuk project Laravel yang **sudah setengah jadi di lokal** dengan asumsi awal (1 sekolah, per Tahap). Prinsip utama: **additive dulu, destruktif belakangan** — jangan langsung ubah/hapus kolom lama sebelum kode baru terbukti jalan.

### 14.1 Backup dulu sebelum apa pun
```bash
mysqldump -u root -p nama_database > backup_sebelum_migrasi_kecamatan.sql
```
Jangan skip langkah ini — semua langkah di bawah mengubah struktur tabel inti.

### 14.2 Urutan migrasi yang disarankan (jangan dibalik)

**Langkah 1 — Tambah struktur multi-sekolah (additive, tidak menghapus apa pun)**
1. Buat migration baru (jangan edit migration lama yang sudah pernah `migrate`):
   ```bash
   php artisan make:migration create_kecamatan_table
   php artisan make:migration add_sekolah_columns_to_transactional_tables
   ```
2. Tambah kolom `sekolah_id` sebagai **nullable** dulu di `rkas_item`, `transaksi_bku`, `users`:
   ```php
   $table->foreignId('sekolah_id')->nullable()->constrained('profil_sekolah');
   ```
   Nullable di awal supaya migration tidak gagal karena data lama belum punya nilai ini.
3. Jalankan migration, lalu **backfill** data lama dengan sekolah pertama (sekolah yang sudah eksis di project lokal Anda):
   ```php
   // jalankan sekali via tinker atau seeder khusus
   \App\Models\RkasItem::whereNull('sekolah_id')->update(['sekolah_id' => 1]);
   \App\Models\TransaksiBku::whereNull('sekolah_id')->update(['sekolah_id' => 1]);
   \App\Models\User::whereNull('sekolah_id')->update(['sekolah_id' => 1]);
   ```
4. **Baru setelah backfill selesai dan dicek**, buat migration lanjutan untuk ubah kolom jadi `NOT NULL`:
   ```php
   $table->foreignId('sekolah_id')->nullable(false)->change();
   ```
   (butuh package `doctrine/dbal` untuk `->change()` di Laravel 11 kalau belum terpasang: `composer require doctrine/dbal`)

**Langkah 2 — Tambah Global Scope, jangan langsung wajibkan di semua tempat**
- Tambahkan scope di Model (`RkasItem`, `TransaksiBku`, dst), tapi **bungkus dengan pengecekan auth** supaya tidak error saat dipanggil dari command line/seeder/testing (di luar konteks HTTP request tidak ada `auth()->user()`):
  ```php
  protected static function booted()
  {
      static::addGlobalScope('sekolah', function (Builder $query) {
          if (auth()->check() && auth()->user()->sekolah_id) {
              $query->where('sekolah_id', auth()->user()->sekolah_id);
          }
      });
  }
  ```
- Setelah scope aktif, **cek ulang semua Controller lama** yang query `RkasItem::all()`, `TransaksiBku::latest()`, dsb — pastikan tidak ada query eksplisit yang sudah menambahkan filter sekolah secara manual (supaya tidak double filter atau malah konflik).

**Langkah 3 — Ubah Tahap → Bulanan (paling berisiko, lakukan paling akhir)**
1. Buat tabel baru `rkas_item_bulan` **tanpa menghapus** kolom `rencana_tahap1`/`rencana_tahap2` di `rkas_item` dulu.
2. Migrasikan data lama ke bentuk baru lewat script sekali-jalan (bukan migration schema, tapi data migration):
   ```php
   foreach (\App\Models\RkasItem::all() as $item) {
       // Contoh pemetaan sederhana: Tahap 1 → bulan 1-6, Tahap 2 → bulan 7-12
       // Sesuaikan logika ini dengan kebutuhan riil sekolah Anda
       if ($item->rencana_tahap1 > 0) {
           $item->bulanRencana()->create(['bulan' => 1, 'rencana' => $item->rencana_tahap1]);
       }
       if ($item->rencana_tahap2 > 0) {
           $item->bulanRencana()->create(['bulan' => 7, 'rencana' => $item->rencana_tahap2]);
       }
   }
   ```
   > Catatan: pemetaan Tahap→Bulan di atas hanya contoh kasar. Karena data historis Tahap 1/2 tidak otomatis tahu bulan pastinya, sebaiknya untuk **data tahun berjalan yang sudah ada**, biarkan tetap tercatat sebagai arsip lama (read-only), dan mulai pencatatan bulanan yang baru dari bulan berjalan ke depan saja. Ini jauh lebih aman daripada memaksakan konversi otomatis yang bisa salah.
3. Setelah fitur bulanan berjalan normal dan sudah dites minimal 1 bulan penuh, **baru pertimbangkan** drop kolom `rencana_tahap1`/`rencana_tahap2` di migration terpisah — jangan digabung dengan migration penambahan fitur baru.

**Langkah 4 — Setup Queue (sebelum aktifkan fitur import bulanan)**
```bash
php artisan queue:table
php artisan migrate
```
Di `.env`, ubah:
```
QUEUE_CONNECTION=database
```
Jalankan worker di terminal terpisah saat development:
```bash
php artisan queue:work
```
**Uji dulu dengan file kecil** (5-10 baris) sebelum mencoba file RKAS asli yang penuh, supaya kalau ada error mapping kolom, gampang dilacak.

### 14.3 Checklist "cari-ganti" di kode existing
Sebelum menganggap migrasi selesai, grep seluruh project untuk memastikan tidak ada bagian kode lama yang masih berasumsi 1 sekolah/1 tahap:
```bash
grep -rn "rencana_tahap1\|rencana_tahap2" app/ resources/
grep -rn "RkasItem::" app/Http/Controllers/
```
Periksa satu per satu: Controller, Blade view (terutama form input & dashboard), Factory/Seeder (kalau ada), dan Request validation class — pastikan semua sudah mengarah ke struktur baru (`sekolah_id`, `rkas_item_bulan`) dan tidak ada referensi tersisa ke kolom lama yang sudah dihapus.

### 14.4 Strategi testing sebelum deploy ke sekolah lain
1. Jalankan seluruh perubahan ini dulu di **copy database lokal**, bukan langsung di data sekolah yang sudah dipakai bendahara.
2. Gunakan `php artisan migrate --pretend` untuk melihat SQL yang akan dijalankan tanpa benar-benar mengeksekusi — cek dulu sebelum migrate sungguhan di data asli.
3. Setelah 1 sekolah (yang sudah ada datanya) berhasil berjalan normal dengan struktur baru, **baru** tambahkan sekolah-sekolah lain di kecamatan satu per satu — jangan sekaligus semua di percobaan pertama.

### 14.5 Rencana rollback
Karena semua langkah di atas additive (tidak menghapus data lama sampai tahap akhir), rollback cukup dengan:
```bash
php artisan migrate:rollback --step=1   # untuk migration terakhir
```
atau restore dari `backup_sebelum_migrasi_kecamatan.sql` bila terjadi kegagalan besar.

---

*Dokumen ini adalah revisi 2.0. Detail wireframe/mockup UI, spesifikasi kelas Job/Import Laravel, dan skema migrasi penuh dapat disusun pada tahap berikutnya.*
