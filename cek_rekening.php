<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== JENIS BELANJA ===\n";
foreach(App\Models\JenisBelanja::all() as $j) {
    echo $j->id . " | " . $j->nama . "\n";
}

echo "\n=== KODE REKENING ===\n";
foreach(App\Models\MasterKodeRekening::with('jenisBelanja')->get() as $r) {
    $jenis = $r->jenisBelanja ? $r->jenisBelanja->nama : 'NULL (belum terpetakan)';
    echo $r->kode . " | " . $r->nama . " => " . $jenis . "\n";
}
