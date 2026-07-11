<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TahunAnggaran extends Model
{
    use HasFactory;
    protected $table = 'tahun_anggaran';

    protected $fillable = [
        'tahun',
        'status'
    ];

    protected function casts(): array
    {
        return [
            'status' => 'boolean'
        ];
    }

    public function rkasItems(): HasMany
    {
        return $this->hasMany(RkasItem::class);
    }

    public function importLogs(): HasMany
    {
        return $this->hasMany(ImportLog::class);
    }
}
