<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $nama
 * @use HasFactory<\Database\Factories\JenisBelanjaFactory>
 */
class JenisBelanja extends Model
{
    /** @use HasFactory<\Database\Factories\JenisBelanjaFactory> */
    use HasFactory;
    protected $table = 'jenis_belanja';

    protected $fillable = [
        'nama'
    ];

    /** @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\MasterKodeRekening, $this> */
    public function masterKodeRekenings(): HasMany
    {
        return $this->hasMany(MasterKodeRekening::class);
    }
}
