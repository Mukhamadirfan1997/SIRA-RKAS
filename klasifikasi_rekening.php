<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// ===== 1. Buat/Pastikan semua kategori Jenis Belanja dari PRD ada =====
$kategoris = [
    'Belanja Barang Persediaan',        // 5.1.02.01
    'Belanja Jasa',                      // 5.1.02.02
    'Belanja Jasa Pemeliharaan',         // 5.1.02.03
    'Belanja Perjalanan Dinas',          // 5.1.02.04
    'Belanja Modal Peralatan & Mesin',   // 5.2.02.10
    'Belanja Modal Buku',                // 5.2.05.01
    'Belanja Modal Aset Tetap Lainnya',  // 5.2.02.05
    'Belanja Lainnya',                   // fallback
];

$jenisMap = [];
foreach ($kategoris as $nama) {
    $j = App\Models\JenisBelanja::firstOrCreate(['nama' => $nama]);
    $jenisMap[$nama] = $j->id;
    echo "Jenis Belanja: [{$j->id}] {$j->nama}\n";
}

// ===== 2. Aturan klasifikasi berdasarkan PREFIX kode rekening (sesuai PRD) =====
// Aturan diurutkan dari yang paling spesifik ke paling umum
$rules = [
    '5.1.02.01' => 'Belanja Barang Persediaan',
    '5.1.02.02' => 'Belanja Jasa',
    '5.1.02.03' => 'Belanja Jasa Pemeliharaan',
    '5.1.02.04' => 'Belanja Perjalanan Dinas',
    '5.2.02.10' => 'Belanja Modal Peralatan & Mesin',
    '5.2.05'    => 'Belanja Modal Buku',
    '5.2.02.05' => 'Belanja Modal Aset Tetap Lainnya',
    '5.2'       => 'Belanja Modal Peralatan & Mesin',   // fallback modal
    '5.1'       => 'Belanja Lainnya',                  // fallback operasional
];

// ===== 3. Update semua Kode Rekening berdasarkan prefix =====
$semua = App\Models\MasterKodeRekening::all();
$updated = 0;

foreach ($semua as $rek) {
    $kode = $rek->kode;
    $jenisId = null;

    foreach ($rules as $prefix => $namaJenis) {
        if (str_starts_with($kode, $prefix)) {
            $jenisId = $jenisMap[$namaJenis] ?? null;
            break;
        }
    }

    if (!$jenisId) {
        $jenisId = $jenisMap['Belanja Lainnya'];
    }

    if ($rek->jenis_belanja_id !== $jenisId) {
        $rek->jenis_belanja_id = $jenisId;
        $rek->save();
        $updated++;
    }
}

echo "\n=== SELESAI ===\n";
echo "Total kode rekening   : " . $semua->count() . "\n";
echo "Diperbarui            : {$updated}\n";

// ===== 4. Tampilkan ringkasan =====
echo "\n=== REKAP KLASIFIKASI ===\n";
foreach ($kategoris as $nama) {
    $count = App\Models\MasterKodeRekening::whereHas('jenisBelanja', function($q) use ($nama) {
        $q->where('nama', $nama);
    })->count();
    echo "{$nama}: {$count} kode rekening\n";
}
