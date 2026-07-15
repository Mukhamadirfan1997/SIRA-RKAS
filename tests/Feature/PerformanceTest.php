<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\TahunAnggaran;
use Illuminate\Support\Facades\DB;

/** @group performance */
class PerformanceTest extends TestCase
{
    private array $timings = [];

    protected function setUp(): void
    {
        parent::setUp();
        $user = User::where('email', 'sekolah-test@test-data.local')->first();
        if ($user) {
            $this->actingAs($user);
        }
    }

    private function timeRoute(string $method, string $uri, array $params = []): array
    {
        $start = microtime(true);
        $response = $this->call($method, $uri, $params);
        $elapsed = round((microtime(true) - $start) * 1000, 1);
        $code = $response->getStatusCode();
        return [$code, $elapsed];
    }

    /** @test */
    public function measure_all_page_times()
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
            $this->timings[] = [$label, $code, $elapsed];
            echo str_pad($label, 50) . str_pad($code, 10) . $elapsed . " ms\n";
        }

        echo "\n--- LOGIN TEST ---\n";
        [$code, $elapsed] = $this->timeRoute('POST', '/login', [
            'email' => 'sekolah-test@test-data.local',
            'password' => 'password',
        ]);
        echo str_pad("POST /login", 50) . str_pad($code, 10) . $elapsed . " ms\n";

        echo "\n--- EXPORT/PDF TEST (skip — memory exhaustive for 50k items) ---\n";

        echo "\n=== SLOW QUERY LOG ===\n";
        $slowLog = DB::select("SHOW VARIABLES LIKE 'slow_query_log_file'");
        echo "Location: " . $slowLog[0]->Value . "\n";

        $this->assertTrue(true);
    }
}
