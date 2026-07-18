<?php

namespace Tests\Feature\Laporan;

use App\Models\JenisBelanja;
use App\Models\MasterProgram;
use App\Models\MasterKodeRekening;
use App\Models\ProfilSekolah;
use App\Models\RkasItem;
use App\Models\RkasItemBulan;
use App\Models\SumberDana;
use App\Models\TahunAnggaran;
use App\Models\TransaksiBku;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LaporanTest extends TestCase
{
    use RefreshDatabase;

    private ProfilSekolah $sekolah;
    private User $user;
    private TahunAnggaran $tahunAnggaran;
    private SumberDana $sumberDana;
    private RkasItem $rkasItem;

    protected function setUp(): void
    {
        parent::setUp();

        $this->sekolah = ProfilSekolah::factory()->create();
        $this->user = User::factory()->sekolah($this->sekolah)->create();
        $this->tahunAnggaran = TahunAnggaran::factory()->create(['status' => true]);
        $this->sumberDana = SumberDana::factory()->create();
        $this->rkasItem = RkasItem::factory()->create([
            'sekolah_id' => $this->sekolah->id,
            'tahun_anggaran_id' => $this->tahunAnggaran->id,
            'sumber_dana_id' => $this->sumberDana->id,
        ]);

        RkasItemBulan::factory()->create([
            'rkas_item_id' => $this->rkasItem->id,
            'bulan' => 1,
            'rencana' => 1000000,
        ]);

        TransaksiBku::factory()->create([
            'sekolah_id' => $this->sekolah->id,
            'tahun_anggaran_id' => $this->tahunAnggaran->id,
            'rkas_item_id' => $this->rkasItem->id,
            'sumber_dana_id' => $this->sumberDana->id,
            'bulan' => 1,
            'jenis' => 'pengeluaran',
            'jumlah' => 500000,
            'uraian' => 'Belanja ATK',
            'metode_pengadaan' => 'siplah',
            'tanggal' => '2025-01-15',
        ]);
    }

    // =================== ACCESS CONTROL ===================

    public function test_guest_cannot_access_laporan_index(): void
    {
        $response = $this->get('/laporan');
        $response->assertRedirect('/login');
    }

    public function test_guest_cannot_access_laporan_bku(): void
    {
        $response = $this->get('/laporan/bku');
        $response->assertRedirect('/login');
    }

    public function test_sekolah_user_can_access_laporan_index(): void
    {
        $response = $this->actingAs($this->user)->get('/laporan');
        $response->assertStatus(200);
    }

    public function test_admin_kecamatan_can_access_laporan_index(): void
    {
        $admin = User::factory()->adminKecamatan()->create();
        $response = $this->actingAs($admin)->get('/laporan');
        $response->assertStatus(200);
    }

    // =================== LAPORAN BKU ===================

    public function test_sekolah_can_view_laporan_bku(): void
    {
        $response = $this->actingAs($this->user)->get('/laporan/bku?bulan=1');
        $response->assertStatus(200);
        $response->assertSee('Belanja ATK');
    }

    public function test_sekolah_can_view_laporan_bku_preview(): void
    {
        $response = $this->actingAs($this->user)->get('/laporan/bku/preview?bulan=1');
        $response->assertStatus(200);
    }

    public function test_sekolah_can_export_laporan_bku_excel(): void
    {
        $response = $this->actingAs($this->user)->get('/laporan/bku/export-excel?bulan=1');
        $response->assertStatus(302);
        $response->assertSessionHas('info');
    }

    // =================== LAPORAN REKAP REKENING ===================

    public function test_sekolah_can_view_laporan_rekap_rekening(): void
    {
        $response = $this->actingAs($this->user)->get('/laporan/rekap-rekening?bulan=1');
        $response->assertStatus(200);
    }

    public function test_sekolah_can_view_laporan_rekap_rekening_preview(): void
    {
        $response = $this->actingAs($this->user)->get('/laporan/rekap-rekening/preview?bulan=1');
        $response->assertStatus(200);
    }

    public function test_sekolah_can_export_laporan_rekap_rekening_excel(): void
    {
        $response = $this->actingAs($this->user)->get('/laporan/rekap-rekening/export-excel?bulan=1');
        $response->assertStatus(302);
        $response->assertSessionHas('info');
    }

    // =================== LAPORAN REKAP KUARTAL ===================

    public function test_sekolah_can_view_laporan_rekap_kuartal(): void
    {
        $response = $this->actingAs($this->user)->get('/laporan/rekap-kuartal?bulan=2');
        $response->assertStatus(200);
    }

    public function test_sekolah_can_view_laporan_rekap_kuartal_preview(): void
    {
        $response = $this->actingAs($this->user)->get('/laporan/rekap-kuartal/preview?bulan=2');
        $response->assertStatus(200);
    }

    public function test_sekolah_can_export_laporan_rekap_kuartal_excel(): void
    {
        $response = $this->actingAs($this->user)->get('/laporan/rekap-kuartal/export-excel?bulan=2');
        $response->assertStatus(302);
        $response->assertSessionHas('info');
    }

    // =================== LAPORAN REKAP SIPLAH ===================

    public function test_sekolah_can_view_laporan_rekap_siplah(): void
    {
        $response = $this->actingAs($this->user)->get('/laporan/rekap-siplah?periode=all');
        $response->assertStatus(200);
    }

    public function test_sekolah_can_view_laporan_rekap_siplah_preview(): void
    {
        $response = $this->actingAs($this->user)->get('/laporan/rekap-siplah/preview?periode=all');
        $response->assertStatus(200);
    }

    public function test_sekolah_can_export_laporan_rekap_siplah_excel(): void
    {
        $response = $this->actingAs($this->user)->get('/laporan/rekap-siplah/export-excel?periode=all');
        $response->assertStatus(302);
        $response->assertSessionHas('info');
    }

    // =================== ADMIN KECAMATAN - LAPORAN BKU ===================

    public function test_admin_can_view_laporan_bku_for_sekolah(): void
    {
        $admin = User::factory()->adminKecamatan()->create();
        $response = $this->actingAs($admin)->get("/laporan/{$this->sekolah->id}/bku?bulan=1");
        $response->assertStatus(200);
        $response->assertSee('Belanja ATK');
    }

    public function test_admin_can_export_laporan_bku_excel_for_sekolah(): void
    {
        $admin = User::factory()->adminKecamatan()->create();
        $response = $this->actingAs($admin)->get("/laporan/{$this->sekolah->id}/bku/export-excel?bulan=1");
        $response->assertStatus(302);
        $response->assertSessionHas('info');
    }

    public function test_admin_can_view_laporan_rekap_rekening_for_sekolah(): void
    {
        $admin = User::factory()->adminKecamatan()->create();
        $response = $this->actingAs($admin)->get("/laporan/{$this->sekolah->id}/rekap-rekening?bulan=1");
        $response->assertStatus(200);
    }

    public function test_admin_can_export_laporan_rekap_rekening_excel_for_sekolah(): void
    {
        $admin = User::factory()->adminKecamatan()->create();
        $response = $this->actingAs($admin)->get("/laporan/{$this->sekolah->id}/rekap-rekening/export-excel?bulan=1");
        $response->assertStatus(302);
        $response->assertSessionHas('info');
    }

    public function test_admin_can_view_laporan_rekap_kuartal_for_sekolah(): void
    {
        $admin = User::factory()->adminKecamatan()->create();
        $response = $this->actingAs($admin)->get("/laporan/{$this->sekolah->id}/rekap-kuartal?bulan=2");
        $response->assertStatus(200);
    }

    public function test_admin_can_export_laporan_rekap_kuartal_excel_for_sekolah(): void
    {
        $admin = User::factory()->adminKecamatan()->create();
        $response = $this->actingAs($admin)->get("/laporan/{$this->sekolah->id}/rekap-kuartal/export-excel?bulan=2");
        $response->assertStatus(302);
        $response->assertSessionHas('info');
    }

    public function test_admin_can_view_laporan_rekap_siplah_for_sekolah(): void
    {
        $admin = User::factory()->adminKecamatan()->create();
        $response = $this->actingAs($admin)->get("/laporan/{$this->sekolah->id}/rekap-siplah?periode=all");
        $response->assertStatus(200);
    }

    public function test_admin_can_export_laporan_rekap_siplah_excel_for_sekolah(): void
    {
        $admin = User::factory()->adminKecamatan()->create();
        $response = $this->actingAs($admin)->get("/laporan/{$this->sekolah->id}/rekap-siplah/export-excel?periode=all");
        $response->assertStatus(302);
        $response->assertSessionHas('info');
    }

    // =================== ACCESS RESTRICTIONS ===================

    public function test_sekolah_cannot_access_admin_laporan_routes(): void
    {
        $response = $this->actingAs($this->user)->get("/laporan/{$this->sekolah->id}/bku?bulan=1");
        $response->assertStatus(403);
    }

    public function test_sekolah_cannot_access_admin_laporan_export_routes(): void
    {
        $response = $this->actingAs($this->user)->get("/laporan/{$this->sekolah->id}/bku/export-excel?bulan=1");
        $response->assertStatus(403);
    }

    public function test_laporan_bku_returns_pdf_when_cetak_param(): void
    {
        $response = $this->actingAs($this->user)->get('/laporan/bku?bulan=1&cetak=pdf');
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_laporan_rekap_rekening_returns_pdf_when_cetak_param(): void
    {
        $response = $this->actingAs($this->user)->get('/laporan/rekap-rekening?bulan=1&cetak=pdf');
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_laporan_rekap_kuartal_returns_pdf_when_cetak_param(): void
    {
        $response = $this->actingAs($this->user)->get('/laporan/rekap-kuartal?bulan=2&cetak=pdf');
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_laporan_rekap_siplah_returns_pdf_when_cetak_param(): void
    {
        $response = $this->actingAs($this->user)->get('/laporan/rekap-siplah?periode=all&cetak=pdf');
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
    }

    // =================== LAPORAN EMPTY / FILTERS ===================

    public function test_laporan_bku_with_sumber_dana_filter(): void
    {
        $response = $this->actingAs($this->user)->get('/laporan/bku?bulan=1&sumber_dana_id=' . $this->sumberDana->id);
        $response->assertStatus(200);
    }

    public function test_laporan_bku_with_different_tahun(): void
    {
        $tahunLain = TahunAnggaran::factory()->create(['tahun' => 2024]);
        $response = $this->actingAs($this->user)->get('/laporan/bku?bulan=1&tahun=2024');
        $response->assertStatus(200);
    }
}
