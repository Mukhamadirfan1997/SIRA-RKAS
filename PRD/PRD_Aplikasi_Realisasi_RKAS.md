# PRD (Product Requirements Document)
# Aplikasi Realisasi RKAS Sekolah

**Nama Produk:** SIRA-RKAS (Sistem Informasi Realisasi Anggaran RKAS)
**Studi kasus rujukan:** UPT SDN Toyaning I Rejoso, Kab. Pasuruan (NPSN 20519260) — TA 2026
**Tech Stack:** Laravel 11 + MySQL 8
**Versi Dokumen:** 1.0
**Tanggal:** 03 Juli 2026

---

## 1. Latar Belakang

Sekolah saat ini mengelola dua dokumen terpisah:

1. **RKAS Per Tahap** (PDF/hasil export ARKAS) — berisi rencana anggaran per item, dipecah ke **Tahap 1** dan **Tahap 2**, dengan struktur berjenjang: Kode Program → Sub Program → Kegiatan → Sub Kegiatan → Rincian Item, masing-masing punya **Kode Rekening** (contoh: `5.2.05.01.01.0001` untuk Belanja Modal Buku, `5.1.02.01.01.0026` untuk Belanja Barang Cetak/Fotokopi).
2. **Buku Kas Umum (BKU) & Kwitansi** dalam file Excel (`KWITANSI Rev.2 2026.xlsm`) — mencatat realisasi transaksi harian (tanggal, kode kegiatan, kode rekening, no bukti, uraian, toko/penerima, penerimaan, pengeluaran, saldo berjalan) dan mencetak kwitansi otomatis via `VLOOKUP`.

Aplikasi yang dirancang dalam PRD ini bersifat **internal sekolah (single-tenant)**. Pelaporan resmi tetap dilakukan melalui aplikasi **ARKAS** milik Kemendikbud sebagaimana biasa — aplikasi ini hanya alat bantu internal agar bendahara/kepala sekolah bisa memantau sisa anggaran secara cepat dan rapi, tanpa perlu terhubung ke sistem Dinas.

**Masalah utama:**
- Tidak ada cara cepat melihat **sisa anggaran per item RKAS** (rencana dikurangi total realisasi), sehingga bendahara harus mencocokkan manual antara sheet RKAS dan BKU.
- Tidak ada **pengelompokan otomatis** antara Belanja Barang, Belanja Jasa, Belanja Modal, dan Belanja Modal Aset Tetap Lainnya (yang sebenarnya bisa dibedakan dari pola Kode Rekening, mis. prefix `5.1.` = Barang/Jasa, `5.2.` = Modal).
- Rekap per **Kode Kegiatan**, **Kode Program**, dan **Kode Rekening** dilakukan manual lewat pivot Excel, rawan salah rumus dan sulit di-maintain lintas tahun anggaran.
- Data tidak terpusat (file `.xlsm` mudah rusak, tidak ada histori perubahan/audit trail, tidak multi-user aman).

---

## 2. Tujuan Produk

1. Menyediakan **satu sumber data** untuk rencana anggaran (RKAS) dan realisasi (BKU/Kwitansi) yang saling terhubung otomatis.
2. Menampilkan **sisa anggaran real-time** per item, per kode rekening, per kegiatan, per program, dan per tahap.
3. Mengklasifikasikan otomatis setiap transaksi ke kategori: **Barang**, **Jasa**, **Modal Peralatan & Mesin**, **Modal Buku**, **Modal Aset Tetap Lainnya**, dll — berbasis master Kode Rekening yang bisa dikonfigurasi.
4. Mereplikasi & merapikan alur kerja Excel yang sudah berjalan (cetak kwitansi, saldo berjalan BKU, rekap per tahap) agar transisi pengguna (bendahara) tidak berat.
5. Menyediakan laporan siap cetak/ekspor yang sesuai kebutuhan pelaporan BOSP/ARKAS.

## 3. Target Pengguna

| Role | Deskripsi | Hak Akses Utama |
|---|---|---|
| **Admin/Operator Sekolah** | Staf yang input data RKAS & transaksi harian | CRUD RKAS, input transaksi BKU, cetak kwitansi |
| **Bendahara Sekolah** | Penanggung jawab keuangan | Semua hak Operator + approve transaksi, lihat laporan, tanda tangan kwitansi |
| **Kepala Sekolah** | Pengesah | Lihat dashboard & laporan, approve/tanda tangan, tidak input transaksi |
| **Komite/Pengawas (opsional)** | Pemantau internal sekolah | Read-only dashboard & laporan |

> **Catatan cakupan:** Aplikasi ini murni untuk kebutuhan **internal sekolah** (satu sekolah, single-tenant). Pelaporan resmi ke Dinas Pendidikan tetap mengacu pada aplikasi **ARKAS** milik Kemendikbud — aplikasi ini **tidak** menggantikan atau terintegrasi dengan ARKAS, hanya membantu sekolah memantau realisasi & sisa anggaran secara internal sebelum/sesudah data diinput ke ARKAS.

## 4. Ruang Lingkup

### In-Scope (MVP)
- Master data: Tahun Anggaran, Sekolah, Sumber Dana, Kode Program (hierarkis), Kode Rekening + Jenis Belanja.
- Import RKAS dari Excel (format sama seperti dokumen RKAS Per Tahap) untuk isi rencana anggaran per item beserta Tahap 1/Tahap 2.
- Input & kelola transaksi BKU (realisasi), dengan link wajib ke item RKAS terkait.
- Perhitungan otomatis: **Sisa = Rencana − Total Realisasi**, per item, per rekening, per kegiatan, per program, per tahap.
- Dashboard klasifikasi Barang/Jasa/Modal/Aset Tetap Lainnya.
- Cetak kwitansi (PDF) mengikuti format & penomoran seperti sheet "CETAK" (No Bukti, Kegiatan, Program, Sub Program, Uraian, Terima Dari, Sebesar, tanda tangan Kepala Sekolah/Bendahara/Penerima).
- Laporan BKU per bulan/tahap dan rekap realisasi per kode rekening (setara sheet "REKAP BKU TB1/TB2").
- Manajemen user & role dasar.

### Out-of-Scope (Tidak Dibutuhkan)
- Integrasi/API ke aplikasi ARKAS atau sistem Dinas Pendidikan — pelaporan ke Dinas tetap manual melalui ARKAS seperti biasa.
- Multi-sekolah/multi-tenant — aplikasi didedikasikan untuk **satu sekolah** saja.

### Out-of-Scope (Fase Lanjutan, opsional)
- Modul pajak otomatis (PPh/PPN) — bisa direplikasi dari sheet "perhitungan pajak" di fase 2.
- Aplikasi mobile native (cukup responsive web di MVP).

---

## 5. Struktur Data Sumber (Hasil Analisis Dokumen)

### 5.1 RKAS Per Tahap
Setiap baris rincian punya kolom: `No Urut`, `Uraian`, `Volume`, `Satuan`, `Tarif/Harga`, `Jumlah`, `Tahap 1`, `Tahap 2`, `Kode Rekening`, `Kode Program`.

Struktur kode berjenjang (contoh dari dokumen):
```
03.               Standar Proses
 03.03.           Pelaksanaan Kegiatan Pembelajaran dan Ekstrakurikuler
  03.03.06.        Pelaksanaan Ekstrakurikuler Kepramukaan
   06.             Honor Pembina Pramuka  → Kode Rekening: 5.1.02.02.01.03.03.0003
```
Pola ini menunjukkan **Kode Program** adalah path hierarkis (bisa disimpan sebagai adjacency list/self-referencing table), sedangkan **Kode Rekening** adalah kode akun standar (mirip Bagan Akun Standar BOSP) yang menentukan **jenis belanja**:

| Prefix Kode Rekening | Jenis Belanja (contoh yang teridentifikasi di data) |
|---|---|
| `5.1.02.01.01.x` | Belanja Barang Persediaan (ATK, cetak, bahan habis pakai) |
| `5.1.02.02.01.x` | Belanja Jasa (honor, sewa, daya & jasa) |
| `5.1.02.03.02.x` / `5.1.02.03.03.x` | Belanja Jasa Pemeliharaan (peralatan/bangunan) |
| `5.1.02.04.01.x` | Belanja Perjalanan Dinas |
| `5.2.02.10.01.x` / `5.2.02.10.02.x` | Belanja Modal Peralatan & Mesin (laptop, scanner) |
| `5.2.05.01.01.x` | Belanja Modal Buku |
| `5.2.02.05.01.x` | Belanja Modal Aset Tetap Lainnya (mis. rak arsip) |

> Karena kode ini bisa berubah/berbeda tiap juknis BOSP tahun berjalan, aplikasi **tidak boleh hardcode** aturan ini — harus jadi **master data yang bisa diimpor & diedit admin**, dengan pemetaan prefix → Jenis Belanja.

### 5.2 BKU (Buku Kas Umum)
Kolom: `Tanggal`, `Kode Kegiatan`, `Kode Rekening`, `No. Bukti`, `Uraian`, `Saldo Tahun Lalu`, `Penerimaan`, `Pengeluaran`, `Saldo` (berjalan), `Toko/Penerima`, `Lunas Bayar`, `Uraian Lengkap`.
Saldo dihitung berjalan (running balance) per baris — pola ini akan direplikasi lewat trigger/kalkulasi di level aplikasi (bukan Excel formula).

### 5.3 Kwitansi (Bukti Pembayaran)
Field tercetak: `No` (format `BPUxxx/NPSN/BB/TTTT`), `Kegiatan`, `Program`, `Sub Program`, `Uraian`, `Terima Dari`, `Sebesar` (angka & terbilang), `Untuk` (deskripsi item), tempat/tanggal, kolom tanda tangan Kepala Sekolah, Bendahara, Yang Menerima/Toko.

---

## 6. Model Data (Skema Database — Ringkasan ERD)

```
tahun_anggaran         (id, tahun, status)
profil_sekolah         (id, npsn, nama, alamat, kecamatan, kabupaten, provinsi,
                         nama_kepsek, nip_kepsek, nama_bendahara, nip_bendahara)
                        -- single-tenant: cukup 1 baris data (pengaturan sekolah)
sumber_dana            (id, kode, nama)                         -- ex: BOSP Reguler

master_program         (id, kode, nama, parent_id, level)       -- self-referencing, hierarkis
master_kode_rekening   (id, kode, nama, jenis_belanja_id)
jenis_belanja          (id, nama)                                -- Barang, Jasa, Modal Peralatan,
                                                                  -- Modal Buku, Modal Aset Tetap Lainnya, dll

rkas_item              (id, tahun_anggaran_id, no_urut, uraian,
                         program_id, kode_rekening_id,
                         volume, satuan, tarif, jumlah,
                         rencana_tahap1, rencana_tahap2)

transaksi_bku          (id, rkas_item_id, tanggal, no_bukti,
                         jenis (penerimaan/pengeluaran), jumlah,
                         toko_penerima, uraian, tahap, status_lunas,
                         saldo_berjalan, created_by, approved_by, approved_at)

kwitansi                (id, transaksi_bku_id, nomor, dicetak_pada, file_pdf_path)

users                   (id, name, email, role_id)
roles                   (id, nama)                               -- via spatie/laravel-permission
audit_log               (id, user_id, tabel, aksi, data_lama, data_baru, created_at)
```

**Relasi kunci:**
`rkas_item` 1—N `transaksi_bku` → memungkinkan perhitungan:
`sisa_anggaran = rkas_item.jumlah - SUM(transaksi_bku.jumlah WHERE jenis=pengeluaran)`,
begitu pula agregasi per `kode_rekening_id`, `program_id`, dan `tahap`.

---

## 7. Fitur Utama

### A. Master Data
- CRUD Tahun Anggaran, Sumber Dana.
- CRUD Master Program (hierarkis, drag/nest seperti struktur RKAS).
- CRUD Master Kode Rekening + mapping ke Jenis Belanja (bisa import Excel/CSV massal).
- Import RKAS dari file Excel (mapping kolom mengikuti format resmi RKAS Per Tahap ARKAS) — validasi otomatis (jumlah = tahap1 + tahap2).

### B. Perencanaan Anggaran (RKAS)
- Tabel rincian anggaran per tahap, bisa difilter per Program/Kegiatan/Kode Rekening/Jenis Belanja.
- Bandingkan Total Penerimaan vs Total Belanja (validasi keseimbangan seperti di dokumen sumber).

### C. Realisasi & BKU
- Form input transaksi harian (pilih item RKAS dari dropdown pencarian → auto-isi kode program/rekening/kegiatan).
- Perhitungan saldo berjalan otomatis (menggantikan formula Excel manual).
- Validasi: transaksi tidak boleh melebihi sisa anggaran item (dengan opsi override + catatan approval jika memang perlu).
- **Cetak Kwitansi PDF** otomatis (nomor urut auto-generate, terbilang otomatis, tanda tangan digital/gambar), sekaligus opsi cetak banyak (batch) seperti fitur "PRINT BANYAK" di Excel.

### D. Monitoring Sisa Anggaran *(fitur inti yang diminta)*
- **Dashboard Sisa Anggaran**: tabel/list item RKAS dengan kolom Rencana, Realisasi, Sisa, % Terserap.
- Filter & group by: Kode Program, Kode Kegiatan, Kode Rekening, **Jenis Belanja (Barang/Jasa/Modal/Aset Tetap Lainnya)**, Tahap 1/2.
- Badge/highlight: item **belum direalisasikan sama sekali**, item **hampir habis** (>90% terserap), item **over budget**.
- Grafik ringkasan (pie/bar) komposisi realisasi per jenis belanja.

### E. Pelaporan
- Laporan BKU per bulan/tahap (cetak PDF, mirror sheet "BKU" & "CETAK BKU").
- Rekap realisasi per Kode Rekening per Tahap (mirror "REKAP BKU TB1/TB2").
- Rekap Pajak (fase 2, mirror sheet "perhitungan pajak").
- Ekspor Excel/PDF sesuai template pelaporan BOSP.

### F. User Management & Approval
- Role-based access (Operator input → Bendahara approve → Kepala Sekolah sah).
- Audit trail semua perubahan data transaksi & RKAS.

### G. Notifikasi (fase 2)
- Peringatan sisa anggaran menipis.
- Peringatan item RKAS yang belum terealisasi mendekati akhir tahap.

---

## 8. Alur Proses Utama

```
1. Operator/Bendahara sekolah import RKAS Per Tahap (hasil export dari ARKAS, awal tahun / pasca pergeseran)
        ↓
2. Sistem membuat rkas_item per baris, otomatis klasifikasi jenis belanja
   dari prefix Kode Rekening (via master_kode_rekening)
        ↓
3. Operator input transaksi realisasi harian → pilih item RKAS terkait
        ↓
4. Sistem hitung saldo berjalan + update sisa anggaran item secara real-time
        ↓
5. Bendahara cetak kwitansi per transaksi (atau batch)
        ↓
6. Kepala Sekolah/Bendahara pantau dashboard sisa anggaran & laporan
        ↓
7. Akhir tahap/tahun → generate laporan rekap untuk pertanggungjawaban
```

---

## 9. Kebutuhan Non-Fungsional

| Aspek | Kebutuhan |
|---|---|
| **Keamanan** | Auth berbasis Laravel (Breeze/Jetstream), role & permission (spatie/laravel-permission), password hashing, HTTPS wajib |
| **Audit** | Semua perubahan RKAS & transaksi tercatat (siapa, kapan, apa) |
| **Backup** | Backup database otomatis harian (mis. `spatie/laravel-backup`) |
| **Performa** | Mendukung ratusan item RKAS & ribuan transaksi per tahun tanpa lag pada dashboard (index database di kolom filter utama) |
| **Kompatibilitas Data** | Import/export Excel (`maatwebsite/excel`) dengan format kolom sama seperti file eksisting sekolah agar transisi mudah |
| **Cetak** | PDF kwitansi & laporan (mis. `barryvdh/laravel-dompdf` atau `spatie/browsershot`), layout menyerupai kwitansi Excel saat ini |
| **Responsif** | UI dapat diakses dari laptop maupun tablet/HP (Bootstrap/Tailwind) |
| **Multi Tahun** | Data RKAS & realisasi per tahun anggaran terpisah tapi bisa dibandingkan historis |

---

## 10. Rekomendasi Tumpukan Teknologi (Tech Stack)

- **Backend:** Laravel 11, PHP 8.3
- **Database:** MySQL 8
- **Frontend:** Blade + Livewire/Alpine.js (cepat dibangun, cocok tim kecil) — alternatif: Vue + Inertia bila tim familiar SPA
- **Auth & Role:** Laravel Breeze + `spatie/laravel-permission`
- **Import/Export Excel:** `maatwebsite/excel`
- **Cetak PDF:** `barryvdh/laravel-dompdf`
- **Chart Dashboard:** Chart.js / ApexCharts
- **Hosting:** Shared hosting/VPS dengan PHP 8.3 + MySQL 8 (sesuai kapasitas sekolah), atau opsi cloud murah (mis. domain `.sch.id`)

---

## 11. Roadmap / Fase Rilis

| Fase | Cakupan |
|---|---|
| **MVP (Fase 1)** | Master data, import RKAS, input realisasi manual, dashboard sisa anggaran per item/rekening/program/jenis belanja, cetak kwitansi |
| **Fase 2** | Approval workflow, notifikasi sisa anggaran, rekap pajak, ekspor laporan sesuai format ARKAS resmi |
| **Fase 3** | Penyempurnaan internal: analitik lanjutan (tren belanja antar tahun), aplikasi mobile companion, otomasi backup ke cloud |

---

## 12. Risiko & Mitigasi

| Risiko | Mitigasi |
|---|---|
| Format & kode rekening BOSP berubah tiap tahun ajaran | Master Kode Rekening dibuat fleksibel (bukan hardcode), bisa diimpor ulang tiap tahun |
| Format hasil export RKAS dari ARKAS berubah antar rilis | Sediakan template import standar + validasi baris saat upload, mapping kolom dapat disesuaikan admin |
| Resistensi pengguna (bendahara terbiasa Excel) | UI dibuat semirip mungkin alur Excel (kwitansi, BKU), sediakan pelatihan singkat |
| Kesalahan input transaksi menyebabkan sisa anggaran salah | Validasi otomatis + audit trail + approval berjenjang |

---

## 13. Metrik Keberhasilan (Success Metrics)

- Waktu pencarian "sisa anggaran item tertentu" turun dari hitungan menit (cek manual Excel) menjadi < 5 detik (search di dashboard).
- 100% transaksi realisasi tercatat terhubung ke item RKAS (tidak ada transaksi "orphan").
- Laporan rekap per tahap bisa digenerate otomatis tanpa edit manual rumus.

---

*Dokumen ini adalah draf awal PRD. Detail wireframe/mockup UI, spesifikasi API, dan skema migrasi Laravel penuh dapat disusun pada tahap berikutnya setelah cakupan fitur MVP disepakati.*
