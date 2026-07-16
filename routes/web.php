<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RkasController;
use App\Http\Controllers\TahunAnggaranController;
use App\Http\Controllers\TransaksiBkuController;
use App\Http\Controllers\SumberDanaController;
use App\Http\Controllers\JenisBelanjaController;
use App\Http\Controllers\MasterProgramController;
use App\Http\Controllers\MasterKodeRekeningController;
use App\Http\Controllers\ImportRkasController;
use App\Http\Controllers\KecamatanController;
use App\Http\Controllers\ProfilSekolahController;
use App\Http\Controllers\LaporanController;
use App\Http\Controllers\UserSekolahController;
use App\Http\Controllers\RkasItemController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('dashboard');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('laporan', [LaporanController::class, 'index'])->name('laporan.index');

    Route::get('exports/{exportJob}/download', [\App\Http\Controllers\ExportController::class, 'download'])->name('exports.download');
    Route::get('exports/{exportJob}/status', [\App\Http\Controllers\ExportController::class, 'status'])->name('exports.status');

    Route::get('rkas-items/select2', [RkasItemController::class, 'select2'])->name('rkas-items.select2');

    // -- Admin Kecamatan Routes --
    Route::middleware(['role:admin-kecamatan'])->group(function() {
        Route::get('/dashboard/kecamatan', [DashboardController::class, 'kecamatan'])->name('dashboard.kecamatan');
        
        Route::resource('kecamatan', KecamatanController::class);
        Route::resource('profil-sekolah', ProfilSekolahController::class);
        Route::resource('user-sekolah', UserSekolahController::class);
        Route::post('user-sekolah/{user_sekolah}/reset-password', [UserSekolahController::class, 'resetPassword'])->name('user-sekolah.reset-password');
        Route::post('user-sekolah/{user_sekolah}/toggle-active', [UserSekolahController::class, 'toggleActive'])->name('user-sekolah.toggle-active');

        Route::resource('tahun-anggaran', TahunAnggaranController::class);
        Route::post('/tahun-anggaran/{tahunAnggaran}/set-active', [TahunAnggaranController::class, 'setActive'])->name('tahun-anggaran.set-active');

        Route::resource('sumber-dana', SumberDanaController::class);
        Route::resource('jenis-belanja', JenisBelanjaController::class);
        
        Route::resource('master-program', MasterProgramController::class);
        Route::post('master-program/import', [MasterProgramController::class, 'import'])->name('master-program.import');
        
        Route::resource('master-kode-rekening', MasterKodeRekeningController::class);
        Route::get('master-kode-rekening/download-template', [MasterKodeRekeningController::class, 'downloadTemplate'])->name('master-kode-rekening.download-template');
        Route::post('master-kode-rekening/import', [MasterKodeRekeningController::class, 'import'])->name('master-kode-rekening.import');

        Route::get('laporan/{sekolah}/bku', [LaporanController::class, 'adminBku'])->name('admin.laporan.bku');
        Route::get('laporan/{sekolah}/rekap-rekening', [LaporanController::class, 'adminRekapRekening'])->name('admin.laporan.rekap-rekening');
        Route::get('laporan/{sekolah}/rekap-kuartal', [LaporanController::class, 'adminRekapKuartal'])->name('admin.laporan.rekap-kuartal');
        Route::get('laporan/{sekolah}/rekap-siplah', [LaporanController::class, 'adminRekapSiplah'])->name('admin.laporan.rekap-siplah');

        Route::get('laporan/{sekolah}/bku/export-excel', [LaporanController::class, 'adminBkuExportExcel'])->name('admin.laporan.bku.export-excel');
        Route::get('laporan/{sekolah}/rekap-rekening/export-excel', [LaporanController::class, 'adminRekapRekeningExportExcel'])->name('admin.laporan.rekap-rekening.export-excel');
        Route::get('laporan/{sekolah}/rekap-kuartal/export-excel', [LaporanController::class, 'adminRekapKuartalExportExcel'])->name('admin.laporan.rekap-kuartal.export-excel');
        Route::get('laporan/{sekolah}/rekap-siplah/export-excel', [LaporanController::class, 'adminRekapSiplahExportExcel'])->name('admin.laporan.rekap-siplah.export-excel');
    });

    // -- RKAS Routes (sekolah & admin-kecamatan) --
    Route::middleware(['role:sekolah|admin-kecamatan'])->group(function() {
        Route::get('/rkas', [RkasController::class, 'index'])->name('rkas.index');
        Route::get('/rkas/{rkasItem}/edit', [RkasController::class, 'edit'])->name('rkas.edit');
        Route::put('/rkas/{rkasItem}', [RkasController::class, 'update'])->name('rkas.update');
        Route::delete('/rkas/{rkasItem}', [RkasController::class, 'destroy'])->name('rkas.destroy');
    });

    // -- Akun Sekolah Routes --
    Route::middleware(['role:sekolah'])->group(function() {
        Route::get('import-rkas', [ImportRkasController::class, 'index'])->name('import-rkas.index');
        Route::post('import-rkas', [ImportRkasController::class, 'store'])->name('import-rkas.store');
        Route::get('import-rkas/status', [ImportRkasController::class, 'status'])->name('import-rkas.status');

        Route::resource('transaksi-bku', TransaksiBkuController::class);
        Route::get('transaksi-bku/{transaksiBku}/cetak-kwitansi', [TransaksiBkuController::class, 'cetakKwitansi'])->name('transaksi-bku.cetak-kwitansi');
        Route::post('transaksi-bku/cetak-kwitansi-batch', [TransaksiBkuController::class, 'cetakKwitansiBatch'])->name('transaksi-bku.cetak-kwitansi-batch');

        Route::get('laporan/bku', [\App\Http\Controllers\LaporanController::class, 'bku'])->name('laporan.bku');
        Route::get('laporan/bku/export-excel', [\App\Http\Controllers\LaporanController::class, 'bkuExportExcel'])->name('laporan.bku.export-excel');
        Route::get('laporan/rekap-rekening', [\App\Http\Controllers\LaporanController::class, 'rekapRekening'])->name('laporan.rekap-rekening');
        Route::get('laporan/rekap-rekening/export-excel', [\App\Http\Controllers\LaporanController::class, 'rekapRekeningExportExcel'])->name('laporan.rekap-rekening.export-excel');

        Route::get('laporan/rekap-kuartal', [\App\Http\Controllers\LaporanController::class, 'rekapKuartal'])->name('laporan.rekap-kuartal');
        Route::get('laporan/rekap-kuartal/export-excel', [\App\Http\Controllers\LaporanController::class, 'rekapKuartalExportExcel'])->name('laporan.rekap-kuartal.export-excel');

        Route::get('laporan/rekap-siplah', [\App\Http\Controllers\LaporanController::class, 'rekapSiplah'])->name('laporan.rekap-siplah');
        Route::get('laporan/rekap-siplah/export-excel', [\App\Http\Controllers\LaporanController::class, 'rekapSiplahExportExcel'])->name('laporan.rekap-siplah.export-excel');

        Route::get('laporan/bku/preview', [LaporanController::class, 'bkuWeb'])->name('laporan.bku.preview');
        Route::get('laporan/rekap-rekening/preview', [LaporanController::class, 'rekapRekeningWeb'])->name('laporan.rekap-rekening.preview');
        Route::get('laporan/rekap-kuartal/preview', [LaporanController::class, 'rekapKuartalWeb'])->name('laporan.rekap-kuartal.preview');
        Route::get('laporan/rekap-siplah/preview', [LaporanController::class, 'rekapSiplahWeb'])->name('laporan.rekap-siplah.preview');
    });
});

require __DIR__ . '/auth.php';
