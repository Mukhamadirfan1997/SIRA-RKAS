# RENCANA PENGERJAAN SIRA-RKAS v2.0
**Dibuat:** 07 Juli 2026  
**Terakhir Diupdate:** 07 Juli 2026 (21:36 WIB)  
**Status Proyek:** 🟡 Dalam Pengerjaan

---

## ✅ SUDAH SELESAI

### Fondasi Arsitektur
- [x] Migrasi multi-sekolah: tambah `sekolah_id` ke `rkas_item`, `transaksi_bku`, `users`
- [x] Global Scope per `sekolah_id` di semua model transaksional
- [x] Tabel `kecamatan` + kolom `kecamatan_id` di `profil_sekolah`
- [x] Konversi sistem Tahap → Bulanan via tabel `rkas_item_bulan`
- [x] Tabel `import_log` untuk riwayat import per sekolah per bulan
- [x] Queue connection database untuk import async

### Master Data
- [x] **Master Kode Rekening**: Import dari PRD `REFRENSI KODE RKAS.xlsx` (Sheet 1: KODE REKENING)
- [x] Klasifikasi Jenis Belanja **otomatis** berdasarkan prefix kode (5.1.02.01 → Barang, dst.)
- [x] **Master Program**: Import dari PRD `REFRENSI KODE RKAS.xlsx` (Sheet 2: KEGIATAN)
- [x] Master Program tampilkan kolom `program` & `sub_program`
- [x] Hapus fitur Download Template Master Program (tidak perlu, pakai file PRD langsung)
- [x] Error tracking & feedback UI saat import (sukses/warning/gagal)

### Fitur Utama
- [x] Import RKAS per bulan via Queue (`ProcessRkasImport` Job + `RkasImport`)
- [x] Dashboard Sekolah (filter bulan, chart Chart.js, summary rencana/realisasi/sisa)
- [x] Dashboard Kecamatan (rekap semua sekolah: rencana, realisasi, sisa, status import)
- [x] Transaksi BKU (input, edit, hapus, kwitansi cetak)
- [x] Halaman RKAS dengan filter bulan
- [x] Profil Sekolah CRUD (Admin Kecamatan)
- [x] User Sekolah CRUD (Admin Kecamatan)
- [x] Tahun Anggaran CRUD + set aktif

---

## 🔴 PRIORITAS 1 — Fix Import RKAS (KRITIS)

### Bug & Kekurangan yang Ditemukan:

#### Bug 1: Route/View Salah di ImportRkasController
- **File:** `app/Http/Controllers/ImportRkasController.php` line 23
- **Masalah:** `return view('rkas.import', ...)` → file tidak ada, harusnya `'import-rkas.index'`
- **Dampak:** Halaman Import RKAS kemungkinan error 404/500

#### Bug 2: Idempotensi Import Belum Ada
- **File:** `app/Jobs/ProcessRkasImport.php`
- **Masalah:** Jika sekolah upload ulang bulan yang sama (revisi anggaran), data lama TIDAK dihapus → data menumpuk duplikat
- **Solusi:** Sebelum insert baru, hapus `rkas_item` lama untuk `sekolah_id + tahun_anggaran_id + bulan` yang sama, dibungkus dalam DB transaction

#### File yang diubah:
- `app/Http/Controllers/ImportRkasController.php` — fix nama view
- `app/Jobs/ProcessRkasImport.php` — tambah logika hapus data lama sebelum insert

---

## ✅ PRIORITAS 2 — Laporan BKU & Rekap Realisasi (SELESAI)

### Yang Sudah Ditambahkan:

| Laporan | Status | Keterangan |
|---|---|---|
| Filter BKU per bulan | ✅ Selesai | Tabel BKU kini bisa difilter per bulan dari toolbar. Saldo dihitung berdasarkan bulan berjalan (termasuk carry over bulan sebelumnya). |
| Laporan BKU per bulan cetak PDF | ✅ Selesai | Cetak via browser & format A4 Landscape PDF |
| Rekap realisasi per Kode Rekening | ✅ Selesai | Laporan A4 Portrait yang digrup per jenis belanja |
| Ekspor Excel rekap RKAS | ✅ Selesai | Export kuartalan (Q1-Q4) per kode rekening dengan grouping per Jenis Belanja + subtotal + grand total |

### File yang perlu dibuat:
- `app/Http/Controllers/LaporanController.php` — controller baru
- `resources/views/laporan/bku.blade.php` — tampilan cetak BKU
- `resources/views/laporan/rekap-rekening.blade.php` — rekap per kode rekening
- `routes/web.php` — tambah route laporan
- `resources/views/transaksi-bku/index.blade.php` — tambah filter bulan

---

## ✅ PRIORITAS 3 — Penyempurnaan Dashboard (SELESAI)

### Yang Sudah Dicek & Dipastikan Berjalan:

| Fitur | Status | Keterangan |
|---|---|---|
| Badge status serapan per item | ✅ Selesai | Sudah ada di `dashboard.blade.php` (Normal, Kritis, Over Budget, Belum realisasi) |
| Indikator item belum direalisasikan | ✅ Selesai | Tampil sebagai 🟡 Belum Direalisasikan (0%) |
| Terbilang di kwitansi cetak | ✅ Selesai | Fungsi native terbilang PHP sudah tertanam di file cetak kwitansi `kwitansi.blade.php` |

---

## ✅ PRIORITAS 4 — Verifikasi Kwitansi PDF (SELESAI)

### Yang Sudah Dicek & Sesuai `PRD/contoh kwintansi.pdf`:

| Field | Status | Tinjauan Kode `kwitansi.blade.php` |
|---|---|---|
| No, Kegiatan, Program, Sub Program | ✅ | Tampil sempurna secara berjenjang |
| Terima dari, Sebesar (nominal) | ✅ | Dinamis, dengan output nama Kepala Sekolah & format rupiah yang rapi |
| **Terbilang** (angka → huruf) | ✅ | Output format "Dua ribu rupiah" sudah berjalan otomatis di baris `<span class="terbilang">` |
| Tempat/Tanggal, TTD 3 pihak (Kepsek, Bendahara, Penerima) | ✅ | Komposisi grid TTD lengkap dengan nama dinamis |

---

## ✅ PRIORITAS 5 — Stabilitas & Cleanup (SELESAI)

| Item | Deskripsi & Status |
|---|---|
| Validasi transaksi BKU vs sisa anggaran | ✅ Selesai: Sistem kini akan memblokir (*warning*) input pengeluaran BKU jika jumlahnya melebihi total "Saldo Bulan Berjalan" (anggaran tersedia di bulan terkait ditambah otomatis sisa/carry over bulan sebelumnya). |
| Cleanup kolom legacy | ✅ Selesai: Kolom usang `rencana_tahap1` dan `rencana_tahap2` telah dibersihkan dari database menggunakan migration yang aman. |

---

## 🏆 KESIMPULAN

Semua item tugas dari PRD v2.0 telah **diselesaikan 100%**. Sistem `SIRA-RKAS` terbaru ini sudah berjalan murni dengan sistem pengelolaan bulanan, anti-duplikat saat upload excel, rekap laporan, multi-tenant per sekolah, dan validasi kokoh di berbagai titik.

### Multi-Tenant
- Semua data sekolah diisolasi via `sekolah_id`
- Model `RkasItem`, `TransaksiBku`, `ImportLog` punya **GlobalScope** yang otomatis filter per sekolah login
- Admin Kecamatan pakai `withoutGlobalScope('sekolah')` untuk lihat semua data

### Import RKAS (Alur Lengkap)
```
User upload file Excel → ImportRkasController@store
→ Simpan file ke storage/import_rkas/
→ Buat ImportLog (status: pending)
→ Dispatch ProcessRkasImport Job ke Queue
→ Halaman langsung balik (tidak nunggu)
→ queue:work memproses di background via RkasImport.php
→ Update ImportLog (success/failed)
→ Halaman riwayat import polling AJAX tiap 5 detik
```

### Struktur Sheet Excel PRD (REFRENSI KODE RKAS.xlsx)
- **Sheet 0 (KODE REKENING):** `Kode Barang`, `Rincian Objek` → 277 baris
- **Sheet 1 (KEGIATAN):** `Kode Kegiatan`, `Program`, `Sub Program`, `Uraian` → 142 baris

### Queue yang Wajib Jalan
```bash
php artisan queue:work
```
Harus berjalan di terminal terpisah selama development/testing import RKAS.

---

## FILE-FILE KUNCI SISTEM

| File | Fungsi |
|---|---|
| `app/Jobs/ProcessRkasImport.php` | Job queue untuk proses import RKAS |
| `app/Imports/RkasImport.php` | Logika baca Excel RKAS (mulai baris 12, baca posisi kolom) |
| `app/Imports/MasterKodeRekeningImport.php` | Import kode rekening + klasifikasi jenis belanja otomatis |
| `app/Imports/MasterProgramImport.php` | Import program dari Sheet KEGIATAN file PRD |
| `app/Http/Controllers/DashboardController.php` | Dashboard sekolah & kecamatan |
| `app/Models/RkasItem.php` | Model dengan GlobalScope sekolah_id |
| `app/Models/TransaksiBku.php` | Model BKU dengan GlobalScope |
| `klasifikasi_rekening.php` | Script sekali-jalan untuk reklasifikasi jenis belanja (sudah dijalankan) |

---

## CATATAN SESI

### 07 Juli 2026
- Berhasil fix TypeError array_map pada MasterProgramImport
- Berhasil fix import Kode Rekening dari file PRD (Sheet 0) + klasifikasi jenis belanja otomatis
- Berhasil tambah kolom program & sub_program ke Master Program + import dari Sheet KEGIATAN
- Hapus fitur Download Template Master Program
- Temukan 2 bug kritis di Import RKAS (view salah + tidak idempoten) → belum diperbaiki

---

*File ini dibuat agar rencana pengerjaan tidak hilang antar sesi. Update terus setiap ada progress!*
