<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProfilSekolah extends Model
{
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

    public function kecamatanRef(): BelongsTo
    {
        return $this->belongsTo(Kecamatan::class, 'kecamatan_id');
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'sekolah_id');
    }

    public function rkasItems(): HasMany
    {
        return $this->hasMany(RkasItem::class, 'sekolah_id');
    }
}
