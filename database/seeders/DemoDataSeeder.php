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

class DemoDataSeeder extends Seeder
{
    private int $sekolahId;
    private int $userId;
    private int $tahunAnggaranId;
    private array $programIds = [];
    private array $kodeRekeningIds = [];
    private array $sumberDanaIds = [];

    public function run(): void
    {
        $this->command->info('=== DEMO DATA SEEDER ===');

        $this->createReferenceData();
        $this->ensureMasterProgram();
        $this->ensureKodeRekening();
        $this->createTahunAnggaran();

        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        try {
            $this->createRkasItems();
            $this->createRkasItemBulan();
            $this->createTransaksiBku();
            $this->createKwitansi();
        } catch (\Throwable $e) {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
            throw $e;
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $this->printSummary();
        $this->command->info('Demo data selesai!');
    }

    private function createReferenceData(): void
    {
        $this->command->info('1/6 Reference data...');

        $kec = Kecamatan::firstOrCreate(
            ['nama' => 'Rejoso'],
            ['kabupaten' => 'Pasuruan', 'provinsi' => 'Jawa Timur']
        );

        $sekolah = ProfilSekolah::firstOrCreate(
            ['npsn' => '20519260'],
            [
                'nama' => 'UPT SDN Toyaning I Rejoso',
                'alamat' => 'Desa Toyaning, Kecamatan Rejoso',
                'kecamatan_id' => $kec->id,
                'kecamatan' => 'Rejoso',
                'kabupaten' => 'Pasuruan',
                'provinsi' => 'Jawa Timur',
                'nama_kepsek' => 'Drs. Bambang Supriyadi, M.Pd.',
                'nip_kepsek' => '196805121994031012',
                'nama_bendahara' => 'Siti Nurjanah, S.Pd.',
                'nip_bendahara' => '197503142005012008',
            ]
        );
        $this->sekolahId = $sekolah->id;

        $sekolahRole = Role::firstOrCreate(['name' => 'sekolah', 'guard_name' => 'web']);

        $user = User::firstOrCreate(
            ['email' => 'sekolah@demo.test'],
            [
                'name' => 'Dina Hartati',
                'password' => Hash::make('password'),
                'sekolah_id' => $this->sekolahId,
                'is_active' => true,
            ]
        );
        if (!$user->hasRole('sekolah')) {
            $user->assignRole($sekolahRole);
        }
        $this->userId = $user->id;

        $this->sumberDanaIds = SumberDana::pluck('id', 'kode')->toArray();
    }

    private function ensureMasterProgram(): void
    {
        $this->command->info('2/6 Master program...');

        if (MasterProgram::count() > 0) {
            $this->programIds = MasterProgram::pluck('id')->toArray();
            return;
        }

        $programs = [
            ['kode' => 'P.001', 'nama' => 'Pendidikan Karakter', 'program' => 'Pendidikan Karakter', 'sub_program' => null, 'level' => 1],
            ['kode' => 'P.001.01', 'nama' => 'Pelaksanaan Kegiatan Pramuka', 'program' => 'Pendidikan Karakter', 'sub_program' => 'Kegiatan Pramuka', 'level' => 2],
            ['kode' => 'P.001.02', 'nama' => 'Kegiatan Keagamaan', 'program' => 'Pendidikan Karakter', 'sub_program' => 'Kegiatan Keagamaan', 'level' => 2],
            ['kode' => 'P.001.03', 'nama' => 'Peringatan Hari Besar Nasional', 'program' => 'Pendidikan Karakter', 'sub_program' => 'Hari Besar Nasional', 'level' => 2],
            ['kode' => 'P.002', 'nama' => 'Pengembangan Minat dan Bakat', 'program' => 'Pengembangan Minat dan Bakat', 'sub_program' => null, 'level' => 1],
            ['kode' => 'P.002.01', 'nama' => 'Kegiatan Olahraga', 'program' => 'Pengembangan Minat dan Bakat', 'sub_program' => 'Olahraga', 'level' => 2],
            ['kode' => 'P.002.02', 'nama' => 'Kegiatan Kesenian', 'program' => 'Pengembangan Minat dan Bakat', 'sub_program' => 'Kesenian', 'level' => 2],
            ['kode' => 'P.002.03', 'nama' => 'Lomba Akademik', 'program' => 'Pengembangan Minat dan Bakat', 'sub_program' => 'Lomba Akademik', 'level' => 2],
            ['kode' => 'P.003', 'nama' => 'Peningkatan Mutu Pembelajaran', 'program' => 'Peningkatan Mutu Pembelajaran', 'sub_program' => null, 'level' => 1],
            ['kode' => 'P.003.01', 'nama' => 'Pengadaan Alat Peraga', 'program' => 'Peningkatan Mutu Pembelajaran', 'sub_program' => 'Alat Peraga', 'level' => 2],
            ['kode' => 'P.003.02', 'nama' => 'Pengembangan Perpustakaan', 'program' => 'Peningkatan Mutu Pembelajaran', 'sub_program' => 'Perpustakaan', 'level' => 2],
            ['kode' => 'P.003.03', 'nama' => 'Kegiatan Belajar Mengajar', 'program' => 'Peningkatan Mutu Pembelajaran', 'sub_program' => 'KBM', 'level' => 2],
            ['kode' => 'P.004', 'nama' => 'Manajemen dan Administrasi Sekolah', 'program' => 'Manajemen dan Administrasi Sekolah', 'sub_program' => null, 'level' => 1],
            ['kode' => 'P.004.01', 'nama' => 'Operasional Perkantoran', 'program' => 'Manajemen dan Administrasi Sekolah', 'sub_program' => 'Operasional Kantor', 'level' => 2],
            ['kode' => 'P.004.02', 'nama' => 'Penyusunan Laporan', 'program' => 'Manajemen dan Administrasi Sekolah', 'sub_program' => 'Penyusunan Laporan', 'level' => 2],
            ['kode' => 'P.005', 'nama' => 'Sarana dan Prasarana', 'program' => 'Sarana dan Prasarana', 'sub_program' => null, 'level' => 1],
            ['kode' => 'P.005.01', 'nama' => 'Pemeliharaan Gedung', 'program' => 'Sarana dan Prasarana', 'sub_program' => 'Pemeliharaan Gedung', 'level' => 2],
            ['kode' => 'P.005.02', 'nama' => 'Pemeliharaan Peralatan', 'program' => 'Sarana dan Prasarana', 'sub_program' => 'Pemeliharaan Peralatan', 'level' => 2],
            ['kode' => 'P.005.03', 'nama' => 'Pengadaan Perlengkapan Kelas', 'program' => 'Sarana dan Prasarana', 'sub_program' => 'Perlengkapan Kelas', 'level' => 2],
            ['kode' => 'P.006', 'nama' => 'Kesehatan Lingkungan Sekolah', 'program' => 'Kesehatan Lingkungan Sekolah', 'sub_program' => null, 'level' => 1],
            ['kode' => 'P.006.01', 'nama' => 'UKS', 'program' => 'Kesehatan Lingkungan Sekolah', 'sub_program' => 'UKS', 'level' => 2],
            ['kode' => 'P.006.02', 'nama' => 'Kebersihan Lingkungan', 'program' => 'Kesehatan Lingkungan Sekolah', 'sub_program' => 'Kebersihan', 'level' => 2],
        ];

        foreach ($programs as $p) {
            MasterProgram::create($p);
        }

        $this->programIds = MasterProgram::pluck('id')->toArray();
    }

    private function ensureKodeRekening(): void
    {
        $this->command->info('3/6 Kode rekening...');

        if (MasterKodeRekening::count() > 0) {
            $this->kodeRekeningIds = MasterKodeRekening::pluck('id')->toArray();
            return;
        }

        $jenis = JenisBelanja::pluck('id', 'nama')->toArray();

        $rekenings = [
            ['kode' => '5.1.01.01.001', 'nama' => 'Alat Tulis Kantor', 'jenis_belanja_id' => $jenis['Belanja Barang Persediaan']],
            ['kode' => '5.1.01.01.002', 'nama' => 'Buku dan Modul Pembelajaran', 'jenis_belanja_id' => $jenis['Belanja Barang Persediaan']],
            ['kode' => '5.1.01.01.003', 'nama' => 'Alat Praktik Siswa', 'jenis_belanja_id' => $jenis['Belanja Barang Persediaan']],
            ['kode' => '5.1.01.01.004', 'nama' => 'Bahan Kebersihan', 'jenis_belanja_id' => $jenis['Belanja Barang Persediaan']],
            ['kode' => '5.1.01.01.005', 'nama' => 'Perlengkapan UKS', 'jenis_belanja_id' => $jenis['Belanja Barang Persediaan']],
            ['kode' => '5.1.01.01.006', 'nama' => 'Alat Peraga Pendidikan', 'jenis_belanja_id' => $jenis['Belanja Barang Persediaan']],
            ['kode' => '5.1.01.01.007', 'nama' => 'Kertas dan Perlengkapan Cetak', 'jenis_belanja_id' => $jenis['Belanja Barang Persediaan']],
            ['kode' => '5.1.02.01.001', 'nama' => 'Langganan Listrik', 'jenis_belanja_id' => $jenis['Belanja Jasa']],
            ['kode' => '5.1.02.01.002', 'nama' => 'Langganan Air', 'jenis_belanja_id' => $jenis['Belanja Jasa']],
            ['kode' => '5.1.02.01.003', 'nama' => 'Langganan Internet', 'jenis_belanja_id' => $jenis['Belanja Jasa']],
            ['kode' => '5.1.02.01.004', 'nama' => 'Jasa Telepon', 'jenis_belanja_id' => $jenis['Belanja Jasa']],
            ['kode' => '5.1.02.01.005', 'nama' => 'Transportasi dan Akomodasi', 'jenis_belanja_id' => $jenis['Belanja Jasa']],
            ['kode' => '5.1.02.01.006', 'nama' => 'Jasa Pemeliharaan Gedung', 'jenis_belanja_id' => $jenis['Belanja Jasa']],
            ['kode' => '5.1.02.01.007', 'nama' => 'Jasa Pemeliharaan Peralatan', 'jenis_belanja_id' => $jenis['Belanja Jasa']],
            ['kode' => '5.1.02.01.008', 'nama' => 'Jasa Kebersihan', 'jenis_belanja_id' => $jenis['Belanja Jasa']],
            ['kode' => '5.2.01.01.001', 'nama' => 'Komputer dan Laptop', 'jenis_belanja_id' => $jenis['Belanja Modal Peralatan & Mesin']],
            ['kode' => '5.2.01.01.002', 'nama' => 'Printer dan Scanner', 'jenis_belanja_id' => $jenis['Belanja Modal Peralatan & Mesin']],
            ['kode' => '5.2.01.01.003', 'nama' => 'Proyektor', 'jenis_belanja_id' => $jenis['Belanja Modal Peralatan & Mesin']],
            ['kode' => '5.2.01.01.004', 'nama' => 'Meja dan Kursi', 'jenis_belanja_id' => $jenis['Belanja Modal Peralatan & Mesin']],
            ['kode' => '5.2.01.01.005', 'nama' => 'Lemari Arsip', 'jenis_belanja_id' => $jenis['Belanja Modal Peralatan & Mesin']],
            ['kode' => '5.2.01.02.001', 'nama' => 'Buku Perpustakaan', 'jenis_belanja_id' => $jenis['Belanja Modal Buku']],
            ['kode' => '5.2.01.02.002', 'nama' => 'Buku Bacaan Siswa', 'jenis_belanja_id' => $jenis['Belanja Modal Buku']],
            ['kode' => '5.2.01.02.003', 'nama' => 'Ensiklopedia dan Kamus', 'jenis_belanja_id' => $jenis['Belanja Modal Buku']],
            ['kode' => '5.2.01.03.001', 'nama' => 'Perlengkapan Olahraga', 'jenis_belanja_id' => $jenis['Belanja Modal Aset Tetap Lainnya']],
            ['kode' => '5.2.01.03.002', 'nama' => 'Alat Kesenian', 'jenis_belanja_id' => $jenis['Belanja Modal Aset Tetap Lainnya']],
        ];

        foreach ($rekenings as $r) {
            MasterKodeRekening::create($r);
        }

        $this->kodeRekeningIds = MasterKodeRekening::pluck('id')->toArray();
    }

    private function createTahunAnggaran(): void
    {
        $this->command->info('4/6 Tahun anggaran...');

        $ta2025 = TahunAnggaran::firstOrCreate(
            ['tahun' => 2025],
            ['status' => false]
        );

        $ta2026 = TahunAnggaran::firstOrCreate(
            ['tahun' => 2026],
            ['status' => true]
        );
        $this->tahunAnggaranId = $ta2026->id;
    }

    private function createRkasItems(): void
    {
        $this->command->info('5/6 Rkas items...');

        $items = [
            ['uraian' => 'Pembelian Alat Tulis Kantor (ATK) untuk administrasi sekolah', 'volume' => 12, 'satuan' => 'paket', 'tarif' => 350000],
            ['uraian' => 'Pembelian Buku Modul Ajar Kurikulum Merdeka', 'volume' => 100, 'satuan' => 'buah', 'tarif' => 45000],
            ['uraian' => 'Pembelian Alat Praktik IPA SD', 'volume' => 6, 'satuan' => 'set', 'tarif' => 850000],
            ['uraian' => 'Pembelian Bahan Kebersihan (sabun, pel, sapu, dll)', 'volume' => 12, 'satuan' => 'paket', 'tarif' => 200000],
            ['uraian' => 'Pembelian Perlengkapan UKS (obat-obatan, perban, dll)', 'volume' => 4, 'satuan' => 'paket', 'tarif' => 500000],
            ['uraian' => 'Pembelian Kertas HVS dan Perlengkapan Cetak', 'volume' => 20, 'satuan' => 'rim', 'tarif' => 55000],
            ['uraian' => 'Pembelian Alat Peraga Matematika SD', 'volume' => 5, 'satuan' => 'set', 'tarif' => 750000],
            ['uraian' => 'Pembelian Alat Peraga Bahasa Indonesia', 'volume' => 5, 'satuan' => 'set', 'tarif' => 650000],
            ['uraian' => 'Pembelian Seragam Pramuka Siswa', 'volume' => 50, 'satuan' => 'set', 'tarif' => 180000],
            ['uraian' => 'Pembelian Alat dan Bahan Kegiatan Pramuka', 'volume' => 2, 'satuan' => 'paket', 'tarif' => 1500000],
            ['uraian' => 'Pembelian Alat Kesenian (alat musik tradisional)', 'volume' => 3, 'satuan' => 'set', 'tarif' => 2500000],
            ['uraian' => 'Pembelian Perlengkapan Olahraga (bola, raket, dll)', 'volume' => 5, 'satuan' => 'paket', 'tarif' => 1200000],
            ['uraian' => 'Pembelian Buku Bacaan Perpustakaan', 'volume' => 80, 'satuan' => 'buah', 'tarif' => 55000],
            ['uraian' => 'Pembelian Ensiklopedia dan Kamus Besar', 'volume' => 10, 'satuan' => 'buah', 'tarif' => 150000],
            ['uraian' => 'Pembelian Meja dan Kursi Siswa', 'volume' => 20, 'satuan' => 'set', 'tarif' => 850000],
            ['uraian' => 'Pembelian Lemari Arsip Kelas', 'volume' => 4, 'satuan' => 'buah', 'tarif' => 1200000],
            ['uraian' => 'Pembelian Komputer untuk Administrasi Sekolah', 'volume' => 2, 'satuan' => 'unit', 'tarif' => 5500000],
            ['uraian' => 'Pembelian Printer dan Scanner', 'volume' => 2, 'satuan' => 'unit', 'tarif' => 2500000],
            ['uraian' => 'Pembelian Proyektor untuk Pembelajaran', 'volume' => 1, 'satuan' => 'unit', 'tarif' => 4500000],
            ['uraian' => 'Pembelian Kipas Angin Kelas', 'volume' => 6, 'satuan' => 'unit', 'tarif' => 650000],
            ['uraian' => 'Pembelian Tinta Printer', 'volume' => 10, 'satuan' => 'botol', 'tarif' => 120000],
            ['uraian' => 'Biaya Langganan Listrik', 'volume' => 12, 'satuan' => 'bulan', 'tarif' => 800000],
            ['uraian' => 'Biaya Langganan Air PDAM', 'volume' => 12, 'satuan' => 'bulan', 'tarif' => 150000],
            ['uraian' => 'Biaya Langganan Internet', 'volume' => 12, 'satuan' => 'bulan', 'tarif' => 350000],
            ['uraian' => 'Biaya Transportasi Kegiatan Lomba Siswa', 'volume' => 3, 'satuan' => 'kali', 'tarif' => 500000],
            ['uraian' => 'Biaya Pemeliharaan Ringan Gedung Sekolah', 'volume' => 4, 'satuan' => 'kali', 'tarif' => 2000000],
            ['uraian' => 'Biaya Pemeliharaan Peralatan Elektronik', 'volume' => 3, 'satuan' => 'kali', 'tarif' => 750000],
            ['uraian' => 'Biaya Jasa Kebersihan Sekolah', 'volume' => 12, 'satuan' => 'bulan', 'tarif' => 500000],
            ['uraian' => 'Biaya Kegiatan Peringatan Hari Besar Nasional', 'volume' => 2, 'satuan' => 'kegiatan', 'tarif' => 2000000],
            ['uraian' => 'Biaya Kegiatan Keagamaan (Pesantren Kilat, PHBI)', 'volume' => 3, 'satuan' => 'kegiatan', 'tarif' => 1500000],
        ];

        $now = now();
        $sumberDanaIds = array_values($this->sumberDanaIds);

        foreach ($items as $i => $item) {
            $program = $this->programIds[array_rand($this->programIds)];
            $kodeRekening = $this->kodeRekeningIds[array_rand($this->kodeRekeningIds)];
            $sumberDana = $sumberDanaIds[array_rand($sumberDanaIds)];

            RkasItem::create([
                'sekolah_id' => $this->sekolahId,
                'tahun_anggaran_id' => $this->tahunAnggaranId,
                'no_urut' => $i + 1,
                'uraian' => $item['uraian'],
                'program_id' => $program,
                'kode_rekening_id' => $kodeRekening,
                'sumber_dana_id' => $sumberDana,
                'volume' => $item['volume'],
                'satuan' => $item['satuan'],
                'tarif' => $item['tarif'],
                'jumlah' => $item['volume'] * $item['tarif'],
            ]);
        }
    }

    private function createRkasItemBulan(): void
    {
        $this->command->info('   Rkas item bulan (rencana per bulan)...');

        $items = RkasItem::where('sekolah_id', $this->sekolahId)->get();

        foreach ($items as $item) {
            $perBulan = $item->jumlah / 12;

            for ($bulan = 1; $bulan <= 12; $bulan++) {
                $variasi = 1 + (rand(-15, 15) / 100);
                RkasItemBulan::create([
                    'rkas_item_id' => $item->id,
                    'bulan' => $bulan,
                    'rencana' => round($perBulan * $variasi, 2),
                ]);
            }
        }
    }

    private function createTransaksiBku(): void
    {
        $this->command->info('   Transaksi BKU...');

        $items = RkasItem::where('sekolah_id', $this->sekolahId)->get();
        $usedAmount = [];
        $saldoBerjalan = 0;
        $now = now();

        $transaksiData = [];

        $descriptions = [
            'Pembayaran ',
            'Pembelian ',
            'Biaya ',
            'Pelunasan ',
        ];

        $noUrut = 0;

        foreach ($items as $item) {
            $maxTransaksi = min(rand(1, 3), 5);
            $sisa = $item->jumlah;

            for ($t = 0; $t < $maxTransaksi && $sisa > 0; $t++) {
                $noUrut++;
                $persen = $t === $maxTransaksi - 1 ? 1 : rand(20, 70) / 100;
                $jumlah = (int)round($sisa * $persen);
                $jumlah = max(1000, min($jumlah, $sisa));
                $sisa -= $jumlah;

                $bulan = rand(1, 6);
                $tanggal = Carbon::create(2026, $bulan, rand(1, 28));
                $deskripsi = $descriptions[array_rand($descriptions)] . strtolower(substr($item->uraian, 0, 40));

                $transaksiData[] = [
                    'sekolah_id' => $this->sekolahId,
                    'tahun_anggaran_id' => $this->tahunAnggaranId,
                    'rkas_item_id' => $item->id,
                    'tanggal' => $tanggal->format('Y-m-d'),
                    'bulan' => $bulan,
                    'no_bukti' => 'BKU-2026-' . str_pad($noUrut, 4, '0', STR_PAD_LEFT),
                    'jenis' => 'pengeluaran',
                    'jumlah' => $jumlah,
                    'toko_penerima' => $this->randomVendor(),
                    'metode_pengadaan' => rand(0, 1) ? 'siplah' : 'non_siplah',
                    'sumber_dana_id' => $item->sumber_dana_id,
                    'uraian' => $deskripsi,
                    'tahap' => 1,
                    'status_lunas' => rand(1, 100) <= 85,
                    'saldo_berjalan' => 0,
                    'created_by' => $this->userId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        usort($transaksiData, fn($a, $b) => $a['tanggal'] <=> $b['tanggal']);

        foreach ($transaksiData as &$t) {
            $saldoBerjalan += $t['jumlah'];
            $t['saldo_berjalan'] = $saldoBerjalan;
        }
        unset($t);

        foreach (array_chunk($transaksiData, 50) as $chunk) {
            DB::table('transaksi_bku')->insert($chunk);
        }

        $this->command->info('   Total ' . count($transaksiData) . ' transaksi BKU');
    }

    private function createKwitansi(): void
    {
        $this->command->info('   Kwitansi...');

        $transaksi = TransaksiBku::where('sekolah_id', $this->sekolahId)
            ->where('status_lunas', true)
            ->get();

        $now = now();
        $batch = [];

        foreach ($transaksi as $idx => $t) {
            $batch[] = [
                'transaksi_bku_id' => $t->id,
                'sekolah_id' => $this->sekolahId,
                'nomor' => 'KWT-2026-' . str_pad($idx + 1, 4, '0', STR_PAD_LEFT),
                'dicetak_pada' => rand(0, 1) ? Carbon::parse($t->tanggal)->addDays(rand(1, 7)) : null,
                'file_pdf_path' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        foreach (array_chunk($batch, 100) as $chunk) {
            DB::table('kwitansi')->insert($chunk);
        }

        $this->command->info('   Total ' . count($batch) . ' kwitansi');
    }

    private function randomVendor(): string
    {
        $vendors = [
            'CV. Maju Jaya', 'UD. Sejahtera Abadi', 'Toko Sinar Harapan',
            'CV. Karya Mandiri', 'UD. Barokah', 'CV. Sinar Terang',
            'PT. Bina Usaha Sejahtera', 'Toko Rejeki', 'CV. Pendidikan Bangsa',
            'UD. Sumber Rejeki', 'CV. Sahabat Sekolah', 'Toko Alat Tama',
            'CV. Berkah Abadi', 'UD. Mitra Edukasi', 'PT. Sarana Belajar',
        ];
        return $vendors[array_rand($vendors)];
    }

    private function printSummary(): void
    {
        $this->command->table(
            ['Tabel', 'Jumlah'],
            [
                ['RkasItem', number_format(RkasItem::where('sekolah_id', $this->sekolahId)->count())],
                ['RkasItemBulan', number_format(RkasItemBulan::whereIn('rkas_item_id',
                    RkasItem::where('sekolah_id', $this->sekolahId)->pluck('id'))->count())],
                ['TransaksiBku', number_format(TransaksiBku::where('sekolah_id', $this->sekolahId)->count())],
                ['Kwitansi', number_format(Kwitansi::where('sekolah_id', $this->sekolahId)->count())],
            ]
        );
    }
}
