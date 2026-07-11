<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JenisBelanja extends Model
{
    use HasFactory;
    protected $table = 'jenis_belanja';

    protected $fillable = [
        'nama'
    ];

    public function masterKodeRekenings(): HasMany
    {
        return $this->hasMany(MasterKodeRekening::class);
    }
}
