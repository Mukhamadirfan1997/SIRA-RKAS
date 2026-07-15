<?php

namespace Database\Seeders;

use App\Models\Kecamatan;
use App\Models\ProfilSekolah;
use App\Models\TahunAnggaran;
use App\Models\User;
use App\Models\SumberDana;
use App\Models\JenisBelanja;
use App\Models\MasterProgram;
use App\Models\MasterKodeRekening;
use App\Models\RkasItem;
use App\Models\RkasItemBulan;
use App\Models\TransaksiBku;
use App\Models\Kwitansi;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Carbon\Carbon;

class TestDataSeeder extends Seeder
{
    private int $sekolahId;
    private int $userId;
    private array $programIds;
    private array $kodeRekeningIds;
    private array $sumberDanaIds;

    private const TAHUN = [2024, 2025, 2026];

    private const BARANG = [
        'ATK', 'Alat Kebersihan', 'Bahan Praktik', 'Media Pembelajaran',
        'Peralatan Kantor', 'Komputer', 'Meja', 'Kursi', 'Papan Tulis', 'Buku',
    ];

    private const KEGIATAN = [
        'Pembelajaran', 'Administrasi', 'Rapat', 'Kegiatan Siswa', 'Pelatihan',
        'Workshop', 'UKS', 'Pramuka', 'Olahraga', 'Kesenian',
    ];

    private const SATUANS = ['buah', 'paket', 'kali', 'set', 'lembar'];

    private const TOKO = [
        'CV. Maju Jaya', 'UD. Sejahtera', 'Toko ABC', 'PT. Karya Mandiri',
        'CV. Berkah Abadi', 'UD. Barokah', 'CV. Sinar Terang', 'PT. Bina Usaha',
    ];

    public function run(): void
    {
        $start = microtime(true);

        if ($this->isPurgeMode()) {
            $this->purge();
            return;
        }

        if (!app()->environment('local', 'testing')) {
            $this->command->warn('Lingkungan: ' . app()->environment());
        }

        $totalEstimasi = count(self::TAHUN) * 725000;
        $this->command->info("Generating ~" . number_format($totalEstimasi) . " test data records (" . count(self::TAHUN) . " tahun)...");

        $this->createReferenceData();
        $this->ensureMasterData();

        $dispatchers = [];
        foreach ([RkasItem::class, RkasItemBulan::class, TransaksiBku::class, Kwitansi::class] as $model) {
            $dispatchers[$model] = $model::getEventDispatcher();
            $model::unsetEventDispatcher();
        }

        foreach (self::TAHUN as $tahun) {
            $tahunAnggaran = TahunAnggaran::firstOrCreate(
                ['tahun' => $tahun],
                ['status' => $tahun === 2026]
            );

            $this->command->info("--- Tahun {$tahun} (ID: {$tahunAnggaran->id}) ---");

            DB::beginTransaction();
            try {
                DB::statement('SET FOREIGN_KEY_CHECKS=0');
                DB::statement('SET UNIQUE_CHECKS=0');

                $itemsJumlah = [];
                $this->generateRkasItems($tahunAnggaran->id, $itemsJumlah);
                $this->generateRkasItemBulan($itemsJumlah);
                $this->generateTransaksiBku($tahunAnggaran->id, $tahun, $itemsJumlah);

                DB::statement('SET FOREIGN_KEY_CHECKS=1');
                DB::statement('SET UNIQUE_CHECKS=1');
                DB::commit();
                $this->command->info("   Tahun {$tahun} selesai.");
            } catch (\Throwable $e) {
                DB::rollBack();
                DB::statement('SET FOREIGN_KEY_CHECKS=1');
                DB::statement('SET UNIQUE_CHECKS=1');
                $this->command->error("GAGAL tahun {$tahun}: " . $e->getMessage());
                throw $e;
            }
        }

        DB::beginTransaction();
        try {
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
            DB::statement('SET UNIQUE_CHECKS=0');
            $this->generateKwitansi();
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
            DB::statement('SET UNIQUE_CHECKS=1');
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
            DB::statement('SET UNIQUE_CHECKS=1');
            $this->command->error('GAGAL kwitansi: ' . $e->getMessage());
            throw $e;
        }

        foreach ([RkasItem::class, RkasItemBulan::class, TransaksiBku::class, Kwitansi::class] as $model) {
            if ($dispatchers[$model] !== null) {
                $model::setEventDispatcher($dispatchers[$model]);
            }
        }

        $elapsed = round(microtime(true) - $start, 2);
        $this->command->info("SELESAI dalam {$elapsed} detik");

        $this->printSummary();
    }

    private function isPurgeMode(): bool
    {
        if ($this->command && $this->command->option('force')) {
            return true;
        }
        return false;
    }

    private function createReferenceData(): void
    {
        $this->command->info('1/4 Reference data...');

        $kec = Kecamatan::firstOrCreate(
            ['nama' => 'Test Kecamatan'],
            ['kabupaten' => 'Test Kabupaten', 'provinsi' => 'Test Provinsi']
        );

        $sekolah = ProfilSekolah::firstOrCreate(
            ['npsn' => '0000000000'],
            [
                'nama' => 'Sekolah Test Performance',
                'kecamatan_id' => $kec->id,
                'kecamatan' => $kec->nama,
                'kabupaten' => $kec->kabupaten,
                'provinsi' => $kec->provinsi,
                'nama_kepsek' => 'Kepala Sekolah Test',
                'nip_kepsek' => '1234567890',
                'nama_bendahara' => 'Bendahara Test',
                'nip_bendahara' => '0987654321',
            ]
        );
        $this->sekolahId = $sekolah->id;

        Role::firstOrCreate(['name' => 'admin-kecamatan', 'guard_name' => 'web']);
        $sekolahRole = Role::firstOrCreate(['name' => 'sekolah', 'guard_name' => 'web']);

        $user = User::firstOrCreate(
            ['email' => 'sekolah-test@test-data.local'],
            [
                'name' => 'User Sekolah Test',
                'password' => Hash::make('password'),
                'sekolah_id' => $this->sekolahId,
            ]
        );
        if (!$user->hasRole('sekolah')) {
            $user->assignRole($sekolahRole);
        }
        $this->userId = $user->id;

        if (SumberDana::count() === 0) {
            SumberDana::insert([
                ['kode' => 'BOSP-REG', 'nama' => 'BOSP Reguler'],
                ['kode' => 'BOSP-KIN', 'nama' => 'BOSP Kinerja'],
            ]);
        }
        $this->sumberDanaIds = SumberDana::pluck('id')->toArray();

        if (JenisBelanja::count() === 0) {
            JenisBelanja::insert([
                ['nama' => 'Belanja Pegawai'],
                ['nama' => 'Belanja Barang'],
                ['nama' => 'Belanja Modal'],
                ['nama' => 'Belanja Pemeliharaan'],
                ['nama' => 'Belanja Lainnya'],
            ]);
        }
    }

    private function ensureMasterData(): void
    {
        $this->command->info('2/4 Master data (fallback)...');

        $this->programIds = MasterProgram::pluck('id')->toArray();
        if (empty($this->programIds)) {
            $this->command->warn('Master Program kosong, membuat 30 fallback...');
            $ids = [];
            $names = ['Pendidikan Karakter', 'Literasi', 'Numerasi', 'Kewirausahaan', 'Kesehatan Sekolah'];
            foreach ($names as $i => $name) {
                $pid = DB::table('master_program')->insertGetId([
                    'kode' => 'TST-P' . str_pad($i + 1, 2, '0', STR_PAD_LEFT),
                    'nama' => $name,
                    'program' => $name,
                    'sub_program' => null,
                    'parent_id' => null,
                    'level' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $ids[] = $pid;

                for ($j = 1; $j <= 5; $j++) {
                    $ids[] = DB::table('master_program')->insertGetId([
                        'kode' => 'TST-P' . str_pad($i + 1, 2, '0', STR_PAD_LEFT) . '.' . $j,
                        'nama' => "Sub {$name} {$j}",
                        'program' => $name,
                        'sub_program' => "Sub {$name} {$j}",
                        'parent_id' => $pid,
                        'level' => 2,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
            $this->programIds = $ids;
        }

        $this->kodeRekeningIds = MasterKodeRekening::pluck('id')->toArray();
        if (empty($this->kodeRekeningIds)) {
            $this->command->warn('Master Kode Rekening kosong, membuat 50 fallback...');
            $jenisIds = JenisBelanja::pluck('id')->toArray();
            $records = [];
            $c = 0;
            for ($i = 0; $i < 5; $i++) {
                $jb = $jenisIds[$i % count($jenisIds)];
                for ($j = 1; $j <= 10; $j++) {
                    $c++;
                    $records[] = [
                        'kode' => '5.TST.' . str_pad($i + 1, 2, '0', STR_PAD_LEFT) . '.' . str_pad($j, 2, '0', STR_PAD_LEFT) . '.' . str_pad($c, 3, '0', STR_PAD_LEFT),
                        'nama' => 'Belanja TST ' . $c,
                        'jenis_belanja_id' => $jb,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }
            foreach (array_chunk($records, 50) as $chunk) {
                DB::table('master_kode_rekening')->insert($chunk);
            }
            $this->kodeRekeningIds = MasterKodeRekening::pluck('id')->toArray();
        }
    }

    private function generateRkasItems(int $tahunAnggaranId, array &$itemsJumlah): void
    {
        $this->command->info('   rkas_item (50.000)...');

        $total = 50000;
        $batchSize = 1000;
        $startId = DB::table('rkas_item')->max('id') ?? 0;
        $now = now();

        $batch = [];
        for ($i = 1; $i <= $total; $i++) {
            $volume = rand(1, 100);
            $tarif = rand(1000, 100000);
            $jumlah = $volume * $tarif;
            $bidx = ($i - 1) % count(self::BARANG);
            $kidx = (int)(($i - 1) / count(self::BARANG)) % count(self::KEGIATAN);

            $batch[] = [
                'id' => $startId + $i,
                'sekolah_id' => $this->sekolahId,
                'tahun_anggaran_id' => $tahunAnggaranId,
                'no_urut' => $i,
                'uraian' => 'Pembelian ' . self::BARANG[$bidx] . ' untuk ' . self::KEGIATAN[$kidx],
                'program_id' => $this->programIds[array_rand($this->programIds)],
                'kode_rekening_id' => $this->kodeRekeningIds[array_rand($this->kodeRekeningIds)],
                'sumber_dana_id' => $this->sumberDanaIds[array_rand($this->sumberDanaIds)],
                'volume' => $volume,
                'satuan' => self::SATUANS[array_rand(self::SATUANS)],
                'tarif' => $tarif,
                'jumlah' => $jumlah,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            $itemsJumlah[$startId + $i] = $jumlah;

            if (count($batch) >= $batchSize) {
                DB::table('rkas_item')->insert($batch);
                $batch = [];
            }
        }

        if (!empty($batch)) {
            DB::table('rkas_item')->insert($batch);
        }
    }

    private function generateRkasItemBulan(array &$itemsJumlah): void
    {
        $this->command->info('   rkas_item_bulan (600.000)...');

        $startId = DB::table('rkas_item_bulan')->max('id') ?? 0;
        $itemIds = array_keys($itemsJumlah);
        sort($itemIds);

        $now = now();
        $batch = [];
        $batchSize = 10000;
        $counter = 0;

        foreach ($itemIds as $itemId) {
            $base = $itemsJumlah[$itemId] / 12;

            for ($bulan = 1; $bulan <= 12; $bulan++) {
                $counter++;
                $variasi = 1 + (rand(-20, 20) / 100);
                $batch[] = [
                    'id' => $startId + $counter,
                    'rkas_item_id' => $itemId,
                    'bulan' => $bulan,
                    'rencana' => round($base * $variasi, 2),
                    'created_at' => $now,
                    'updated_at' => $now,
                ];

                if (count($batch) >= $batchSize) {
                    DB::table('rkas_item_bulan')->insert($batch);
                    $batch = [];
                }
            }
        }

        if (!empty($batch)) {
            DB::table('rkas_item_bulan')->insert($batch);
        }
    }

    private function generateTransaksiBku(int $tahunAnggaranId, int $tahun, array &$itemsJumlah): void
    {
        $this->command->info('   transaksi_bku (25.000)...');

        $startId = DB::table('transaksi_bku')->max('id') ?? 0;
        $itemIds = array_keys($itemsJumlah);
        sort($itemIds);

        $now = now();
        $batch = [];
        $batchSize = 1000;
        $usedAmount = [];
        $saldoBerjalan = 0;

        for ($i = 1; $i <= 25000; $i++) {
            $availableItems = array_filter($itemIds, function ($id) use ($usedAmount, $itemsJumlah) {
                return ($itemsJumlah[$id] - ($usedAmount[$id] ?? 0)) > 0;
            });

            if (empty($availableItems)) {
                $this->command->warn("   Hanya bisa generate " . ($i - 1) . " transaksi (anggaran habis)");
                break;
            }

            $itemId = $availableItems[array_rand($availableItems)];
            $available = $itemsJumlah[$itemId] - ($usedAmount[$itemId] ?? 0);
            $jumlah = min(rand(10000, max(10000, (int)$available)), $available);
            $usedAmount[$itemId] = ($usedAmount[$itemId] ?? 0) + $jumlah;
            $saldoBerjalan += $jumlah;

            $tanggal = Carbon::create($tahun, rand(1, 12), rand(1, 28));

            $batch[] = [
                'id' => $startId + $i,
                'sekolah_id' => $this->sekolahId,
                'tahun_anggaran_id' => $tahunAnggaranId,
                'rkas_item_id' => $itemId,
                'tanggal' => $tanggal->format('Y-m-d'),
                'bulan' => $tanggal->month,
                'no_bukti' => 'BKU-' . $tahun . '-' . str_pad($i, 5, '0', STR_PAD_LEFT),
                'jenis' => 'pengeluaran',
                'jumlah' => $jumlah,
                'toko_penerima' => self::TOKO[array_rand(self::TOKO)],
                'metode_pengadaan' => rand(0, 1) ? 'siplah' : 'non_siplah',
                'uraian' => 'Pembayaran belanja',
                'tahap' => 1,
                'status_lunas' => rand(1, 100) <= 80,
                'saldo_berjalan' => $saldoBerjalan,
                'created_by' => $this->userId,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            if (count($batch) >= $batchSize) {
                DB::table('transaksi_bku')->insert($batch);
                $batch = [];
            }
        }

        if (!empty($batch)) {
            DB::table('transaksi_bku')->insert($batch);
        }
    }

    private function generateKwitansi(): void
    {
        $this->command->info('   kwitansi (75.000)...');

        $trans = DB::table('transaksi_bku')
            ->where('sekolah_id', $this->sekolahId)
            ->select('id', 'tanggal')
            ->orderBy('id')
            ->get();

        $startId = DB::table('kwitansi')->max('id') ?? 0;
        $now = now();
        $batch = [];
        $batchSize = 1000;

        foreach ($trans as $idx => $row) {
            $tahun = substr($row->tanggal, 0, 4);
            $batch[] = [
                'id' => $startId + $idx + 1,
                'transaksi_bku_id' => $row->id,
                'sekolah_id' => $this->sekolahId,
                'nomor' => 'KWT-' . $tahun . '-' . str_pad($idx + 1, 5, '0', STR_PAD_LEFT),
                'dicetak_pada' => null,
                'file_pdf_path' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            if (count($batch) >= $batchSize) {
                DB::table('kwitansi')->insert($batch);
                $batch = [];
            }
        }

        if (!empty($batch)) {
            DB::table('kwitansi')->insert($batch);
        }
    }

    private function purge(): void
    {
        $this->command->info('=== PURGE: Hapus semua data test ===');

        if (!$this->command->option('force') && !$this->command->confirm('Yakin hapus semua data test? Data real tidak tersentuh.')) {
            $this->command->info('Dibatalkan.');
            return;
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        DB::table('kwitansi')->truncate();
        DB::table('transaksi_bku')->truncate();
        DB::table('rkas_item_bulan')->truncate();
        DB::table('rkas_item')->truncate();

        User::where('email', 'like', '%@test-data.local')->delete();
        ProfilSekolah::where('npsn', '0000000000')->delete();
        Kecamatan::where('nama', 'Test Kecamatan')->delete();
        MasterProgram::where('kode', 'like', 'TST-%')->delete();
        MasterKodeRekening::where('kode', 'like', '5.TST.%')->delete();

        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $this->command->info('Data test berhasil dihapus.');
    }

    private function printSummary(): void
    {
        $this->command->table(
            ['Tabel', 'Jumlah Record'],
            [
                ['rkas_item', number_format(DB::table('rkas_item')->where('sekolah_id', $this->sekolahId)->count())],
                ['rkas_item_bulan', number_format(DB::table('rkas_item_bulan')
                    ->join('rkas_item', 'rkas_item.id', '=', 'rkas_item_bulan.rkas_item_id')
                    ->where('rkas_item.sekolah_id', $this->sekolahId)
                    ->count())],
                ['transaksi_bku', number_format(DB::table('transaksi_bku')->where('sekolah_id', $this->sekolahId)->count())],
                ['kwitansi', number_format(DB::table('kwitansi')->where('sekolah_id', $this->sekolahId)->count())],
                ['users (test)', number_format(User::where('email', 'like', '%@test-data.local')->count())],
            ]
        );
    }
}
