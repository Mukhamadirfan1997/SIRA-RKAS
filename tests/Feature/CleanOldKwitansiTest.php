<?php

namespace Tests\Feature;

use App\Console\Commands\CleanOldKwitansi;
use App\Models\Kwitansi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CleanOldKwitansiTest extends TestCase
{
    use RefreshDatabase;

    public function test_no_old_kwitansi_shows_info_message(): void
    {
        $this->artisan('kwitansi:clean', ['years' => 2])
            ->expectsOutput('Tidak ada kwitansi yang perlu dibersihkan.')
            ->assertSuccessful();
    }

    public function test_deletes_old_records_and_files(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('kwitansi/old.pdf', 'pdf content');
        Storage::disk('public')->put('kwitansi/recent.pdf', 'pdf content');

        Kwitansi::factory()->create([
            'created_at' => now()->subYears(3),
            'file_pdf_path' => 'kwitansi/old.pdf',
        ]);

        Kwitansi::factory()->create([
            'created_at' => now()->subMonths(6),
            'file_pdf_path' => 'kwitansi/recent.pdf',
        ]);

        $this->artisan('kwitansi:clean', ['years' => 2])
            ->expectsOutput('Dibersihkan: 1 record kwitansi, 1 file PDF (>2 tahun).')
            ->assertSuccessful();

        $this->assertEquals(1, Kwitansi::count());
        $this->assertDatabaseMissing('kwitansi', ['file_pdf_path' => 'kwitansi/old.pdf']);
        $this->assertDatabaseHas('kwitansi', ['file_pdf_path' => 'kwitansi/recent.pdf']);

        Storage::disk('public')->assertMissing('kwitansi/old.pdf');
        Storage::disk('public')->assertExists('kwitansi/recent.pdf');
    }

    public function test_handles_missing_file_gracefully(): void
    {
        Storage::fake('public');

        Kwitansi::factory()->create([
            'created_at' => now()->subYears(3),
            'file_pdf_path' => 'kwitansi/nonexistent.pdf',
        ]);

        $this->artisan('kwitansi:clean', ['years' => 2])
            ->expectsOutput('Dibersihkan: 1 record kwitansi, 0 file PDF (>2 tahun).')
            ->assertSuccessful();

        $this->assertEquals(0, Kwitansi::count());
    }

    public function test_uses_default_years_argument(): void
    {
        Storage::fake('public');

        Kwitansi::factory()->create([
            'created_at' => now()->subYears(2)->subDay(),
            'file_pdf_path' => null,
        ]);

        Kwitansi::factory()->create([
            'created_at' => now()->subYears(2)->addDay(),
            'file_pdf_path' => null,
        ]);

        $this->artisan('kwitansi:clean')
            ->assertSuccessful();

        $this->assertEquals(1, Kwitansi::count());
    }
}
