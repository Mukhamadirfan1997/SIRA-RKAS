<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SumberDana extends Model
{
    use HasFactory;
    protected $table = 'sumber_dana';

    protected $fillable = [
        'kode',
        'nama'
    ];

    public function rkasItems(): HasMany
    {
        return $this->hasMany(RkasItem::class, 'sumber_dana_id');
    }
}
