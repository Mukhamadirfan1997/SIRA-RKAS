<?php
// Script migrasi data: backfill sekolah_id + konversi tahap → bulanan
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\RkasItem;
use App\Models\RkasItemBulan;
use App\Models\TransaksiBku;
use App\Models\User;

// 0. Buat ProfilSekolah dummy jika belum ada (untuk menghindari foreign key constraint error)
$dummyProfil = \App\Models\ProfilSekolah::find(1);
if (!$dummyProfil) {
    $dummyProfil = \App\Models\ProfilSekolah::create([
        'id' => 1,
        'npsn' => '00000000',
        'nama' => 'Sekolah Default (Data Migrasi)',
    ]);
    echo "Dibuat ProfilSekolah default (ID: 1)." . PHP_EOL;
}

// 1. Backfill sekolah_id = 1 untuk semua data yang belum punya
$r = RkasItem::whereNull('sekolah_id')->update(['sekolah_id' => 1]);
$t = TransaksiBku::whereNull('sekolah_id')->update(['sekolah_id' => 1]);
$u = User::whereNull('sekolah_id')->update(['sekolah_id' => 1]);
echo "Backfill sekolah_id: rkas_item={$r}, transaksi_bku={$t}, users={$u}" . PHP_EOL;

// 2. Backfill bulan dari tanggal untuk transaksi_bku yang belum ada bulan
$bkuUpdated = 0;
TransaksiBku::whereNull('bulan')->each(function($t) use (&$bkuUpdated) {
    $t->update(['bulan' => (int) date('n', strtotime($t->tanggal))]);
    $bkuUpdated++;
});
echo "Backfill bulan transaksi_bku: {$bkuUpdated} record" . PHP_EOL;

// 3. Konversi rencana_tahap1 → rkas_item_bulan bulan=1, rencana_tahap2 → bulan=7
$converted = 0;
$skipped = 0;
RkasItem::all()->each(function($item) use (&$converted, &$skipped) {
    $created = false;
    if ($item->rencana_tahap1 > 0) {
        RkasItemBulan::updateOrCreate(
            ['rkas_item_id' => $item->id, 'bulan' => 1],
            ['rencana' => $item->rencana_tahap1]
        );
        $created = true;
    }
    if ($item->rencana_tahap2 > 0) {
        RkasItemBulan::updateOrCreate(
            ['rkas_item_id' => $item->id, 'bulan' => 7],
            ['rencana' => $item->rencana_tahap2]
        );
        $created = true;
    }
    $created ? $converted++ : $skipped++;
});
echo "Konversi tahap→bulan selesai: {$converted} item dikonversi, {$skipped} item dilewati (nilai 0)" . PHP_EOL;
echo "Selesai! Data siap untuk v2.0" . PHP_EOL;
