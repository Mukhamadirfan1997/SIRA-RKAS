<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $kode
 * @property string $nama
 * @property int $jenis_belanja_id
 * @use HasFactory<\Database\Factories\MasterKodeRekeningFactory>
 */
class MasterKodeRekening extends Model
{
    /** @use HasFactory<\Database\Factories\MasterKodeRekeningFactory> */
    use HasFactory;
    protected $table = 'master_kode_rekening';

    protected $fillable = [
        'kode',
        'nama',
        'jenis_belanja_id'
    ];

    /** @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\JenisBelanja, $this> */
    public function jenisBelanja(): BelongsTo
    {
        return $this->belongsTo(JenisBelanja::class);
    }

    /** @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\RkasItem, $this> */
    public function rkasItems(): HasMany
    {
        return $this->hasMany(RkasItem::class, 'kode_rekening_id');
    }
}
