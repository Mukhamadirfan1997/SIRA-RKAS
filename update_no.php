<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$txs = App\Models\TransaksiBku::orderBy('tanggal')->orderBy('id')->get();
$bbu = 1;
$bpu = 1;
$prof = App\Models\ProfilSekolah::first();
$npsn = $prof ? $prof->npsn : '00000000';

foreach($txs as $t) {
    $date = \Carbon\Carbon::parse($t->tanggal);
    $m = $date->format('m');
    $y = $date->format('Y');
    
    if(strtolower($t->jenis) == 'penerimaan') {
        $t->no_bukti = sprintf('BBU%03d/%s/%s/%s', $bbu++, $npsn, $m, $y);
    } else {
        $t->no_bukti = sprintf('BPU%03d/%s/%s/%s', $bpu++, $npsn, $m, $y);
    }
    $t->save();
}

echo "Berhasil update format nomor bukti ke 3 digit (BBU001 / BPU001).\n";
