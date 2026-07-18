<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $sekolah_id
 * @property int $tahun_anggaran_id
 * @property int $bulan
 * @property string $status
 * @property int|null $baris_berhasil
 * @property string|null $file_path
 * @use HasFactory<\Database\Factories\ImportLogFactory>
 */
class ImportLog extends Model
{
    /** @use HasFactory<\Database\Factories\ImportLogFactory> */
    use HasFactory;
    protected $table = 'import_log';

    protected $fillable = [
        'sekolah_id',
        'tahun_anggaran_id',
        'bulan',
        'sumber_dana_id',
        'file_name',
        'file_path',
        'status',
        'total_baris',
        'baris_berhasil',
        'baris_gagal',
        'error_detail',
        'uploaded_by',
        'finished_at',
    ];

    protected $casts = [
        'error_detail' => 'array',
        'finished_at' => 'datetime',
    ];

    protected static function booted()
    {
        static::addGlobalScope('sekolah', function (Builder $query) {
            if (auth()->check() && auth()->user()->sekolah_id) {
                $query->where('sekolah_id', auth()->user()->sekolah_id);
            }
        });
    }

    /** @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\ProfilSekolah, $this> */
    public function sekolah(): BelongsTo
    {
        return $this->belongsTo(ProfilSekolah::class, 'sekolah_id');
    }

    /** @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\SumberDana, $this> */
    public function sumberDana(): BelongsTo
    {
        return $this->belongsTo(SumberDana::class);
    }

    /** @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\TahunAnggaran, $this> */
    public function tahunAnggaran(): BelongsTo
    {
        return $this->belongsTo(TahunAnggaran::class);
    }

    /** @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\User, $this> */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
