<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MasterKodeRekening extends Model
{
    use HasFactory;
    protected $table = 'master_kode_rekening';

    protected $fillable = [
        'kode',
        'nama',
        'jenis_belanja_id'
    ];

    public function jenisBelanja(): BelongsTo
    {
        return $this->belongsTo(JenisBelanja::class);
    }

    public function rkasItems(): HasMany
    {
        return $this->hasMany(RkasItem::class, 'kode_rekening_id');
    }
}
