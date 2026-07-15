<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class RkasItem extends Model
{
    use HasFactory;

    protected $table = 'rkas_item';

    protected $fillable = [
        'sekolah_id',
        'tahun_anggaran_id',
        'no_urut',
        'uraian',
        'program_id',
        'kode_rekening_id',
        'sumber_dana_id',
        'volume',
        'satuan',
        'tarif',
        'jumlah'
    ];

    protected static function booted()
    {
        static::addGlobalScope('sekolah', function (Builder $query) {
            if (auth()->check() && auth()->user()->sekolah_id) {
                $query->where('sekolah_id', auth()->user()->sekolah_id);
            }
        });
    }

    public function tahunAnggaran(): BelongsTo
    {
        return $this->belongsTo(TahunAnggaran::class);
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(MasterProgram::class, 'program_id');
    }

    public function kodeRekening(): BelongsTo
    {
        return $this->belongsTo(MasterKodeRekening::class, 'kode_rekening_id');
    }

    public function sumberDana(): BelongsTo
    {
        return $this->belongsTo(SumberDana::class, 'sumber_dana_id');
    }

    public function sekolah(): BelongsTo
    {
        return $this->belongsTo(ProfilSekolah::class, 'sekolah_id');
    }

    public function transaksiBkus(): HasMany
    {
        return $this->hasMany(TransaksiBku::class);
    }

    public function bulanRencana(): HasMany
    {
        return $this->hasMany(RkasItemBulan::class, 'rkas_item_id');
    }

    public function getRealisasiAttribute()
    {
        return $this->transaksiBkus()->where('jenis', 'pengeluaran')->sum('jumlah');
    }

    public function getSisaAttribute()
    {
        return $this->jumlah - $this->realisasi;
    }
}
