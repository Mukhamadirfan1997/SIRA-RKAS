<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Kecamatan extends Model
{
    protected $table = 'kecamatan';

    protected $fillable = [
        'nama',
        'kabupaten',
        'provinsi',
    ];

    public function profilSekolahs(): HasMany
    {
        return $this->hasMany(ProfilSekolah::class);
    }
}
