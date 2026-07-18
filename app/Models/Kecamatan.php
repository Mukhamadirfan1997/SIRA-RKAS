<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $nama
 * @property string $kabupaten
 * @property string $provinsi
 * @use HasFactory<\Database\Factories\KecamatanFactory>
 */
class Kecamatan extends Model
{
    /** @use HasFactory<\Database\Factories\KecamatanFactory> */
    use HasFactory;
    protected $table = 'kecamatan';

    protected $fillable = [
        'nama',
        'kabupaten',
        'provinsi',
    ];

    /** @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\ProfilSekolah, $this> */
    public function profilSekolahs(): HasMany
    {
        return $this->hasMany(ProfilSekolah::class);
    }
}
