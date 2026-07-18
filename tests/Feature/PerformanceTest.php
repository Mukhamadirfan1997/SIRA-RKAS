<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\TahunAnggaran;
use App\Models\ProfilSekolah;
use App\Models\MasterProgram;
use App\Models\MasterKodeRekening;
use App\Models\JenisBelanja;
use App\Models\SumberDana;
use App\Models\RkasItem;
use App\Models\RkasItemBulan;
use App\Models\TransaksiBku;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

/** @group performance */
class PerformanceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        RkasItem::unsetEventDispatcher();
        TransaksiBku::unsetEventDispatcher();

        $this->seedData();

        $user = User::where('email', 'sekolah-test@test-data.local')->first();
        if ($user) {
            $this->actingAs($user);
        }
    }

    private function seedData(): void
    {
        Role::firstOrCreate(['name' => 'sekolah', 'guard_name' => 'web']);

        $profil = ProfilSekolah::factory()->create([
            'npsn' => '0000000000',
            'nama' => 'Sekolah Test Performance',
        ]);

        $user = User::factory()->sekolah($profil)->create([
            'email' => 'sekolah-test@test-data.local',
        ]);

        $jenisBelanjas = JenisBelanja::factory()->count(5)->create();

        $sumberDanas = SumberDana::factory()->count(2)->create();

        $programs = MasterProgram::factory()->count(5)->create();

        $kodeRekenings = MasterKodeRekening::factory()
            ->count(10)
            ->sequence(fn($seq) => ['jenis_belanja_id' => $jenisBelanjas->get($seq->index % 5)->id])
            ->create();

        $tahunAnggaran = TahunAnggaran::factory()->create([
            'tahun' => 2026,
            'status' => true,
        ]);

        $rkasItems = RkasItem::factory()
            ->count(100)
            ->sequence(fn($seq) => [
                'sekolah_id' => $profil->id,
                'tahun_anggaran_id' => $tahunAnggaran->id,
                'no_urut' => $seq->index + 1,
                'program_id' => $programs->get($seq->index % 5)->id,
                'kode_rekening_id' => $kodeRekenings->get($seq->index % 10)->id,
                'sumber_dana_id' => $sumberDanas->get($seq->index % 2)->id,
                'volume' => rand(1, 50),
                'tarif' => rand(5000, 100000),
            ])
            ->create();

        $rkasItems->each(function ($item) {
            $jumlahPerBulan = $item->jumlah / 12;
            foreach (range(1, 12) as $bulan) {
                RkasItemBulan::factory()->create([
                    'rkas_item_id' => $item->id,
                    'bulan' => $bulan,
                    'rencana' => round($jumlahPerBulan * (1 + rand(-20, 20) / 100), 2),
                ]);
            }
        });

        $saldo = 0;
        $rkasItems->each(function ($item) use (&$saldo, $tahunAnggaran, $profil, $user, $sumberDanas) {
            foreach (range(1, min(2, $item->id % 5 + 1)) as $i) {
                $jumlah = min(rand(10000, 500000), $item->jumlah * 0.8);
                $saldo += $jumlah;
                $bulan = rand(1, 12);
                TransaksiBku::factory()->create([
                    'sekolah_id' => $profil->id,
                    'tahun_anggaran_id' => $tahunAnggaran->id,
                    'rkas_item_id' => $item->id,
                    'tanggal' => "2026-{$bulan}-" . rand(1, 28),
                    'bulan' => $bulan,
                    'no_bukti' => 'BKU-2026-' . str_pad((string)($item->id * 10 + $i), 5, '0', STR_PAD_LEFT),
                    'jenis' => 'pengeluaran',
                    'jumlah' => $jumlah,
                    'toko_penerima' => 'CV. Test',
                    'metode_pengadaan' => $i % 2 ? 'siplah' : 'non_siplah',
                    'sumber_dana_id' => $sumberDanas->random()->id,
                    'uraian' => 'Pembayaran ' . $item->uraian,
                    'status_lunas' => true,
                    'saldo_berjalan' => $saldo,
                    'created_by' => $user->id,
                ]);
            }
        });
    }

    /**
     * @param array<mixed> $params
     * @return array{0: int, 1: float}
     */
    private function timeRoute(string $method, string $uri, array $params = []): array
    {
        $start = microtime(true);
        $response = $this->call($method, $uri, $params);
        $elapsed = round((microtime(true) - $start) * 1000, 1);
        $code = $response->getStatusCode();
        return [$code, $elapsed];
    }

    /** @test */
    public function measure_all_page_times(): void
    {
        $routes = [
            'GET /dashboard'                 => ['GET', '/dashboard', []],
            'GET /rkas (bulan=1)'            => ['GET', '/rkas', ['bulan' => 1]],
            'GET /transaksi-bku (bulan=1)'   => ['GET', '/transaksi-bku', ['bulan' => 1]],
            'GET /laporan'                   => ['GET', '/laporan', []],
            'GET /laporan/bku (bulan=1)'     => ['GET', '/laporan/bku', ['bulan' => 1, 'tahun' => 2026]],
            'GET /laporan/rekap-rekening'    => ['GET', '/laporan/rekap-rekening', ['bulan' => 1, 'tahun' => 2026]],
            'GET /laporan/rekap-kuartal'     => ['GET', '/laporan/rekap-kuartal', ['tahun' => 2026]],
            'GET /laporan/rekap-siplah'      => ['GET', '/laporan/rekap-siplah', ['tahun' => 2026, 'periode' => 'all']],
        ];

        echo "\n" . str_pad("Route", 50) . str_pad("Status", 10) . "Time\n";
        echo str_repeat("-", 70) . "\n";

        foreach ($routes as $label => [$method, $uri, $params]) {
            [$code, $elapsed] = $this->timeRoute($method, $uri, $params);
            echo str_pad($label, 50) . str_pad((string) $code, 10) . $elapsed . " ms\n";
        }

        echo "\n--- LOGIN TEST ---\n";
        [$code, $elapsed] = $this->timeRoute('POST', '/login', [
            'email' => 'sekolah-test@test-data.local',
            'password' => 'password',
        ]);
        echo str_pad("POST /login", 50) . str_pad((string) $code, 10) . $elapsed . " ms\n";

        $this->assertNotEmpty($routes);
    }
}
