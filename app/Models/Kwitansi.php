<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Kwitansi extends Model
{
    use HasFactory;
    protected $table = 'kwitansi';

    protected $fillable = [
        'transaksi_bku_id',
        'sekolah_id',
        'nomor',
        'dicetak_pada',
        'file_pdf_path'
    ];

    protected function casts(): array
    {
        return [
            'dicetak_pada' => 'datetime'
        ];
    }

    protected static function booted()
    {
        static::addGlobalScope('sekolah', function (Builder $query) {
            if (auth()->check() && auth()->user()->sekolah_id) {
                $query->where('sekolah_id', auth()->user()->sekolah_id);
            }
        });
    }

    public function transaksiBku(): BelongsTo
    {
        return $this->belongsTo(TransaksiBku::class);
    }

    public function sekolah(): BelongsTo
    {
        return $this->belongsTo(ProfilSekolah::class, 'sekolah_id');
    }
}
