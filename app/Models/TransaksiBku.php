<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Builder;

class TransaksiBku extends Model
{
    use HasFactory;
    protected $table = 'transaksi_bku';

    protected $fillable = [
        'sekolah_id',
        'rkas_item_id',
        'tanggal',
        'bulan',
        'no_bukti',
        'jenis',
        'jumlah',
        'toko_penerima',
        'metode_pengadaan',
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

    public function rkasItem(): BelongsTo
    {
        return $this->belongsTo(RkasItem::class);
    }

    public function sekolah(): BelongsTo
    {
        return $this->belongsTo(ProfilSekolah::class, 'sekolah_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function kwitansi(): HasOne
    {
        return $this->hasOne(Kwitansi::class);
    }
}
