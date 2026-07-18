# Sesi 18 Jul 2026 — PHPStan Level 6: 704→0 errors

## Goal
Fix all PHPStan/Larastan errors at level 6 in app/ and database/ code.

## Summary
704 → 0 errors. All fixes are in PHPDoc annotations and minor code refinements—zero behavioural changes, zero `@phpstan-ignore` comments.

## Changes by Category

### Model `@property` annotations (15 models)
Added `@property` for database columns and appended/ dynamic attributes:
- `ProfilSekolah` — id, npsn, nama, total_rencana, total_realisasi, status_import, sisa, persentase
- `TransaksiBku` — total_penerimaan, total_pengeluaran, label, jenis, jumlah, volume, satuan, rkas_item_id, sekolah_id, tahun_anggaran_id, bulan, no_bukti, metode_pengadaan, uraian, sumber_dana_id, toko_penerima, saldo_berjalan, keterangan, total, siplah, non_siplah, jenis_belanja
- `RkasItem` — id, no_urut, uraian, jumlah, tarif, satuan (nullable), program_id, kode_rekening_id, sumber_dana_id, sekolah_id, tahun_anggaran_id, realisasi_sum, realisasi_per_bulan, total_realisasi, total_rencana, sisa_bulan, persen, rencana_bulan, realisasi_bulan, nama, m0, m1, m2, dynamic_*, sisa
- Other models (RkasItemBulan, MasterProgram, MasterKodeRekening, JenisBelanja, Kecamatan, User, SumberDana, ImportLog, ExportJob, TahunAnggaran, AuditLog, Kwitansi)

### `@use HasFactory<...>` generics (14 models)
Added `@use HasFactory<\Database\Factories\XFactory>` to each model using the trait.

### Relation return type generics (15 models)
Updated BelongsTo/HasMany/BelongsToMany `@return` to include generic parameters, e.g.:
```php
@return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\User, $this>
```

### Controller return types (16 controllers)
Added `: \Illuminate\View\View` / `: \Illuminate\Http\RedirectResponse` / `: \Illuminate\Http\JsonResponse` / `: \Symfony\Component\HttpFoundation\BinaryFileResponse` to every public method.

### Bug fixes

| File | Issue | Fix |
|---|---|---|
| `User.php` | Missing `MustVerifyEmail` interface | Added `implements MustVerifyEmail` |
| `UserFactory.php` | `sekolah(ProfilSekolah $profil)` — `factory()->create()` returns `Model` | Changed param to `Model $profil`, use `$profil->getKey()` |
| `BkuExport.php` | `prepend()`/`push()` with `stdClass` | Changed to `new TransaksiBku()` with `setAttribute()` |
| `RekapSiplahExport.php` | Same pattern | Same fix |
| `LaporanController.php` | `foreach` on `Builder` (non-iterable) | Assigned `->get()` to variable first |
| `NotifyBackupTelegram.php` | Non-existent `BackupDestinationStatus::diskName()` etc. | Replaced with `backupDestination()` proxy calls |

### Import classes (4 files)
Added missing `int` / `string` / `array` type hints on properties, parameters, and return types.

### Jobs & Listeners
- `GenerateExportJob` — typed `$exportParams` as `array<string, mixed>`
- `ProcessRkasImport` — typed properties & parameters
- `SendTelegramNotificationJob` — typed `$backoff`, `$context`, `$extra`
- `TelegramLogHandler` — typed `$sensitiveKeys`, `sanitize()` types

### Factories (13 files)
Added `@extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\X>` to each factory class.

### Test files (9 files)
Fixed dispatch arg types, removed unused properties, replaced `assertTrue(true)` with meaningful assertions, fixed Collection covariant issues, added missing return/param types, fixed `str_pad` int→string cast.

## Key Patterns

- **Factory creation in tests**: `Model::factory()->create()` returns `Model`, not the specific model. Use `UserFactory::sekolah(Model $profil): static` to accept the generic return type.
- **Dynamic model properties**: Always declare `@property` on the model for both DB columns and runtime-appended attributes.
- **`@use HasFactory<...>`**: Required on every model using the `HasFactory` trait so PHPStan knows which factory class to associate.
- **`@extends Factory<Model>`**: Required on every factory class.
- **`$request->validate()` returns `array<string, mixed>`**: Extract to typed local variables instead of directly accessing `$validated['key']`.
- **`auth()->user()` can return null**: Guard with `if ($user === null) { ... }` or use `auth()->id()`.

---

# Sesi 18 Jul 2026 (part 2) — Sisa Volume di RKAS + TransaksiBku

## Goal
Menampilkan sisa volume/satuan di kolom Sisa halaman RKAS, dengan melacak volume realisasi di TransaksiBku.

## Summary
- Kolom Sisa RKAS sekarang menampilkan `Rp X (sisa Y satuan)`
- Volume realisasi disimpan per transaksi BKU

## Changes

### Migration
- `2026_07_18_200001_add_volume_satuan_to_transaksi_bku.php` — tambah `volume` (decimal 15,2) dan `satuan` (varchar 50) ke `transaksi_bku`

### Model
- `TransaksiBku` — `@property float|null $volume`, `@property string|null $satuan`, tambah ke `$fillable`

### Factory
- `TransaksiBkuFactory` — generate `volume` (1–100) dan `satuan` random (buah/paket/lembar/dll)

### Controller
- `TransaksiBkuController::store/update` — validasi `volume`, `satuan`; auto-fill `satuan` dari `RkasItem`
- `RkasItemController::select2` — fix closure type `Builder` (bukan `Relation`), fix null-safe `optional()` untuk program/kodeRekening/sumberDana

### Views
- `transaksi-bku/create.blade.php` + `edit.blade.php` — hidden field `volume` + `satuan`, diisi otomatis oleh JS kalkulator
- `transaksi-bku/index.blade.php` — kolom baru Volume + Satuan
- `rkas/index.blade.php` — sisa volume dihitung dari `item.volume - sum(transaksi.volume)`, ditampilkan sebagai `(sisa Y satuan)`

### Files Modified
10 files + 1 migration baru
