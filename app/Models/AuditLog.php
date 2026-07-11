<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    protected $table = 'audit_log';

    protected $fillable = [
        'user_id',
        'tabel',
        'aksi',
        'data_lama',
        'data_baru'
    ];

    protected function casts(): array
    {
        return [
            'data_lama' => 'array',
            'data_baru' => 'array'
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
