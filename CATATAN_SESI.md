# CATATAN SESI SIRA-RKAS

## 1. Fix Import RKAS (0 baris)
- Removed `WithChunkReading`, `ShouldQueue` dari `RkasImport`
- Fix index kolom: Maatwebsite 0-indexed (A=0, B=1, ...)
- `logError()` pakai PHP array append, bukan `JSON_ARRAY_APPEND`
- `model()` return `null` (cegah double-save oleh Maatwebsite)
- Hapus file setelah import sukses

## 2. Sumber Dana
- Migration menambah `sumber_dana_id` ke `import_log` & `rkas_item`
- Form upload ada dropdown sumber dana
- Controller validasi & simpan `sumber_dana_id`
- `RkasImport` terima & teruskan `sumberDanaId` ke `RkasItem::create()`

## 3. SQLite → MySQL (Laragon)
- MySQL via Laragon: root / no password / db `sira_rkas`
- Fix migrasi: rename `2026_07_07_090829_create_kecamatan_table` → `090828` (FK order)
- Queue kembali aktif (`dispatch` bukan `dispatchSync`)
- Config: WAL mode + busy timeout (fallback SQLite untuk test)

## 4. Waktu Indonesia + Locale
- Timezone: `Asia/Jakarta`
- `APP_LOCALE=id`, `APP_FAKER_LOCALE=id_ID`
- `translatedFormat('F')` otomatis output "Januari", dll
- Hapus hardcoded `$bulan_id` di kwitansi, ganti `translatedFormat`

## 5. Tampilan Daftar RKAS
- Kolom pake `table-fixed` + lebar tetap (tidak berantakan)
- Program, Kode Rekening, Uraian: wrap normal, tidak di-truncate
- **Fix Realisasi**: kolom sebelumnya salah nampilin rencana, sekarang realisasi dari transaksi BKU
- **Sisa**: dihitung dari `jumlah - realisasi`
- **Status**: Over, Hampir Habis, %, Koreksi, Belum — pakai badge warna
- **Aksi**: icon button lebih kecil
- `transaksiBkus` di-eager-load (hindari N+1)

## 6. Tampilan Sisa Anggaran (Dashboard)
- No urut ganti `$item->no_urut` → `$loop->iteration` (rapi tanpa loncatan)

## 7. Kwitansi
- Program & Sub Program baca dari kolom `program` / `sub_program` (bukan relasi `parent_id`)
- Tampil kode + nama semua level (Program, Sub Program, Kegiatan)

## 8. Tests
- 61 tests / 121 assertions — semua **PASS**
- Test pakai SQLite in-memory (tidak terpengaruh MySQL)

## 9. Sistem Laporan
- **BKU Bulanan**: web preview (`bku-web`), cetak PDF, export Excel
- **Rekap Realisasi per Kode Rekening**: web preview, PDF, Excel
- **Rekap Kuartal** (Q1-Q4): web preview, PDF, Excel
- **Rekap SIPLAH** (SIPLAH vs Non-SIPLAH): web preview, PDF, Excel
- Semua laporan ada versi **Admin Kecamatan** (bisa lihat laporan tiap sekolah)
- Helper methods: `prepareBkuData()`, `prepareRekapRekeningData()`, `prepareRekapKuartalData()`, `prepareRekapSiplahData()`
- Global scope `sekolah` di `TransaksiBku` bisa di-bypass via `withoutGlobalScope()` untuk admin

## 10. Metode Pengadaan (SIPLAH)
- Migration: tambah kolom `metode_pengadaan` (enum: `siplah`, `non_siplah`) ke `transaksi_bku`
- Form create/edit transaksi ada dropdown metode pengadaan
- Tabel BKU tampilkan badge SIPLAH / Non-SIPLAH
- Laporan Rekap SIPLAH hitung proporsi SIPLAH vs Non-SIPLAH per jenis belanja
- Validasi store/update: field `metode_pengadaan` nullable, opsional

## 11. Pengingat Akhir Bulan (BKU)
- Banner `alert-warning` di `layouts/app.blade.php` (global, semua halaman)
- Muncul otomatis tanggal 22 ke atas setiap bulan
- Pesan: mengingatkan segera input transaksi BKU
- Pakai `Carbon::now()->day >= 22` + `translatedFormat('F')` untuk nama bulan Indonesia

## 12. Pengingat Kelengkapan Data BKU
- **Belum Ada Transaksi Bulan Ini**: banner di dashboard sekolah, cek `TransaksiBku::where('bulan', now()->month)->count() == 0`
- **Metode Pengadaan Belum Diisi**: banner di BKU index, hitung transaksi pengeluaran dengan `metode_pengadaan IS NULL`
- **Kwitansi Belum Dicetak**: banner di BKU index, hitung transaksi pengeluaran yang tidak punya record kwitansi
- **Belum Upload (Admin Kecamatan)**: banner di dashboard kecamatan, tampilkan jumlah sekolah yang belum upload RKAS bulan berjalan
- Semua pakai class `alert-warning`, konsisten dengan pola yang ada

## 13. Last Login (Terakhir Login)
- Migration: tambah kolom `last_login_at` (nullable timestamp) ke tabel `users`
- `User` model: tambah ke `$fillable` + cast `datetime`
- `LoginRequest::authenticate()`: set `last_login_at = now()` saat login berhasil
- Tabel user-sekolah: tambah kolom "Terakhir Login" tampilkan tanggal + `diffForHumans()`
- Jika belum pernah login, tampilkan "Belum pernah login" (italic)

## 14. Perbaikan Tahun Anggaran
- **Guard hapus tahun aktif**: `destroy()` cek `$tahunAnggaran->status`, return error jika aktif
- **DB::transaction** di `setActive()`: bungkus update semua + update satu tahun
- **Flash message informatif**: tampilkan tahun sebelumnya yang dinonaktifkan
- **Konfirmasi Aktifkan**: tambah `onclick confirm()` di tombol Aktifkan
- **Konfirmasi Hapus**: perjelas pesan "Semua data RKAS dan import akan ikut terhapus"
- **Sembunyikan tombol Hapus** untuk tahun yang sedang aktif
- **Input tahun**: tambah `min="2020" max="2099"` + validasi `between:2020,2099`
- **Warning "Tahun Anggaran belum aktif"**: ditambahkan di:
  - Dashboard sekolah (`dashboard.blade.php`)
  - Dashboard kecamatan (`dashboard-kecamatan.blade.php`)
  - Laporan index (`laporan/index.blade.php`)
  - Import RKAS (`import-rkas/index.blade.php`) — form upload juga disembunyikan
- **Model**: tambah relationship `importLogs()` ke `TahunAnggaran`

## 15. Fix Dashboard & Form Transaksi
- **Tren realisasi**: hapus filter `bulan` di `DashboardController` — trend chart selalu tampilkan 12 bulan
- **Carbon::parse()**: ganti `date('n', strtotime(...))` → `Carbon::parse(...)->month` di `TransaksiBkuController::store()` dan `::update()` + `DashboardController`
- **Bulan filter integer**: dropdown bulan di `dashboard.blade.php` ubah `sprintf('%02d')` → integer `$m` (supaya query match)
- **RKAS dropdown compact**: teks `no_urut. uraian (Sisa: Rp X)` — lebih pendek, data attributes untuk program, kode, tarif, satuan, sisa
- **Detail card RKAS**: grid 4 kolom (Program, Kode Rekening, Tarif/Satuan, Sisa Anggaran) muncul saat item dipilih
- **Form layout redesign**: section headers (Informasi Transaksi, Item RKAS, Kalkulator Otomatis, Nominal & Rincian), 3-column top row, `font-mono` no_bukti, `text-lg font-bold` jumlah
- **Eager load**: tambah `->with(['program', 'kodeRekening'])` di `create()` dan `edit()` controller

## 16. Filter Program di Dashboard & RKAS
- **`RkasController::index`**: tambah filter `program_id` dari request, query `MasterProgram` (level atas), `->where('program_id', ...)` jika dipilih
- **`rkas/index.blade.php`**: dropdown "Semua Program" di samping dropdown bulan, auto-submit, seleksi tetap tersimpan
- **`dashboard.blade.php`**: tambah kolom **Program** (kode + nama) di tabel "Daftar Sisa Anggaran (RKAS)"
- **`DashboardController`**: trend chart & transaksi terkini sekarang pakai `$rkasItems->pluck('id')` — ikut filter program, bulan, kode rekening, jenis belanja (sebelumnya selalu query semua item tanpa filter)
