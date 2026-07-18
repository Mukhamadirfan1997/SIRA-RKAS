<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $npsn
 * @property string $nama
 * @property float $total_rencana
 * @property float $total_realisasi
 * @property string $status_import
 * @property float $sisa
 * @property float $persentase
 * @use HasFactory<\Database\Factories\ProfilSekolahFactory>
 */
class ProfilSekolah extends Model
{
    /** @use HasFactory<\Database\Factories\ProfilSekolahFactory> */
    use HasFactory;
    protected $table = 'profil_sekolah';

    protected $fillable = [
        'npsn',
        'nama',
        'alamat',
        'kecamatan_id',
        'kecamatan',
        'kabupaten',
        'provinsi',
        'nama_kepsek',
        'nip_kepsek',
        'nama_bendahara',
        'nip_bendahara'
    ];

    /** @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\Kecamatan, $this> */
    public function kecamatanRef(): BelongsTo
    {
        return $this->belongsTo(Kecamatan::class, 'kecamatan_id');
    }

    /** @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\User, $this> */
    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'sekolah_id');
    }

    /** @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\RkasItem, $this> */
    public function rkasItems(): HasMany
    {
        return $this->hasMany(RkasItem::class, 'sekolah_id');
    }
}
