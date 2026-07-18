<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Builder;

/**
 * @property float $saldo
 * @property float $total
 * @property float $siplah
 * @property float $non_siplah
 * @property string $jenis_belanja
 * @property string $label
 * @property float $belum_diisi
 * @property float $persen_siplah
 * @property float $persen_non_siplah
 * @property float $total_penerimaan
 * @property float $total_pengeluaran
 * @property string $jenis
 * @property float $jumlah
 * @property int $rkas_item_id
 * @property int $sekolah_id
 * @property int $tahun_anggaran_id
 * @property int $bulan
 * @property string $no_bukti
 * @property string $metode_pengadaan
 * @property string $uraian
 * @property int $sumber_dana_id
 * @property string $toko_penerima
 * @property float $saldo_berjalan
 * @property string|null $keterangan
 * @use HasFactory<\Database\Factories\TransaksiBkuFactory>
 */
class TransaksiBku extends Model
{
    /** @use HasFactory<\Database\Factories\TransaksiBkuFactory> */
    use HasFactory;
    protected $table = 'transaksi_bku';

    protected $fillable = [
        'sekolah_id',
        'tahun_anggaran_id',
        'rkas_item_id',
        'tanggal',
        'bulan',
        'no_bukti',
        'jenis',
        'jumlah',
        'toko_penerima',
        'metode_pengadaan',
        'sumber_dana_id',
        'uraian',
        'tahap',
        'status_lunas',
        'saldo_berjalan',
        'created_by',
        'approved_by',
        'approved_at'
    ];

    protected static function booted()
    {
        static::addGlobalScope('sekolah', function (Builder $query) {
            if (auth()->check() && auth()->user()->sekolah_id) {
                $query->where('sekolah_id', auth()->user()->sekolah_id);
            }
        });
    }

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'status_lunas' => 'boolean',
            'approved_at' => 'datetime'
        ];
    }

    /** @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\RkasItem, $this> */
    public function rkasItem(): BelongsTo
    {
        return $this->belongsTo(RkasItem::class);
    }

    /** @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\TahunAnggaran, $this> */
    public function tahunAnggaran(): BelongsTo
    {
        return $this->belongsTo(TahunAnggaran::class);
    }

    /** @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\ProfilSekolah, $this> */
    public function sekolah(): BelongsTo
    {
        return $this->belongsTo(ProfilSekolah::class, 'sekolah_id');
    }

    /** @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\User, $this> */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\User, $this> */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /** @return \Illuminate\Database\Eloquent\Relations\HasOne<\App\Models\Kwitansi, $this> */
    public function kwitansi(): HasOne
    {
        return $this->hasOne(Kwitansi::class);
    }

    /** @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\SumberDana, $this> */
    public function sumberDana(): BelongsTo
    {
        return $this->belongsTo(SumberDana::class);
    }
}
