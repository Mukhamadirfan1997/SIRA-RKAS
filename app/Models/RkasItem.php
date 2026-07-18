<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

/**
 * @property int $id
 * @property string $no_urut
 * @property string $uraian
 * @property float $jumlah
 * @property float $tarif
 * @property string|null $satuan
 * @property int $program_id
 * @property int $kode_rekening_id
 * @property int $sumber_dana_id
 * @property int $sekolah_id
 * @property int $tahun_anggaran_id
 * @property float $realisasi_sum
 * @property array<int, float> $realisasi_per_bulan
 * @property float $total_realisasi
 * @property float $total_rencana
 * @property float $sisa_bulan
 * @property float $persen
 * @property float $rencana_bulan
 * @property float $realisasi_bulan
 * @property string $nama
 * @property float $m0
 * @property float $m1
 * @property float $m2
 * @property float $dynamic_realisasi
 * @property float $dynamic_rencana
 * @property float $dynamic_sisa
 * @property float $dynamic_rencana_volume
 * @property float $dynamic_realisasi_volume
 * @property float $dynamic_sisa_volume
 * @property float $sisa
 * @property float $total
 * @property float $persentase
 * @use HasFactory<\Database\Factories\RkasItemFactory>
 */
class RkasItem extends Model
{
    /** @use HasFactory<\Database\Factories\RkasItemFactory> */
    use HasFactory;

    protected $table = 'rkas_item';

    protected $fillable = [
        'sekolah_id',
        'tahun_anggaran_id',
        'no_urut',
        'uraian',
        'program_id',
        'kode_rekening_id',
        'sumber_dana_id',
        'volume',
        'satuan',
        'tarif',
        'jumlah'
    ];

    protected static function booted()
    {
        static::addGlobalScope('sekolah', function (Builder $query) {
            if (auth()->check() && auth()->user()->sekolah_id) {
                $query->where('sekolah_id', auth()->user()->sekolah_id);
            }
        });
    }

    /** @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\TahunAnggaran, $this> */
    public function tahunAnggaran(): BelongsTo
    {
        return $this->belongsTo(TahunAnggaran::class);
    }

    /** @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\MasterProgram, $this> */
    public function program(): BelongsTo
    {
        return $this->belongsTo(MasterProgram::class, 'program_id');
    }

    /** @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\MasterKodeRekening, $this> */
    public function kodeRekening(): BelongsTo
    {
        return $this->belongsTo(MasterKodeRekening::class, 'kode_rekening_id');
    }

    /** @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\SumberDana, $this> */
    public function sumberDana(): BelongsTo
    {
        return $this->belongsTo(SumberDana::class, 'sumber_dana_id');
    }

    /** @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\ProfilSekolah, $this> */
    public function sekolah(): BelongsTo
    {
        return $this->belongsTo(ProfilSekolah::class, 'sekolah_id');
    }

    /** @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\TransaksiBku, $this> */
    public function transaksiBkus(): HasMany
    {
        return $this->hasMany(TransaksiBku::class);
    }

    /** @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\RkasItemBulan, $this> */
    public function bulanRencana(): HasMany
    {
        return $this->hasMany(RkasItemBulan::class, 'rkas_item_id');
    }

}
