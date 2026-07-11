<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MasterProgram extends Model
{
    use HasFactory;
    protected $table = 'master_program';

    protected $fillable = [
        'kode',
        'nama',
        'program',
        'sub_program',
        'parent_id',
        'level'
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(MasterProgram::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(MasterProgram::class, 'parent_id');
    }

    public function rkasItems(): HasMany
    {
        return $this->hasMany(RkasItem::class, 'program_id');
    }
}
