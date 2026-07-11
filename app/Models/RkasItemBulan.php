<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RkasItemBulan extends Model
{
    protected $table = 'rkas_item_bulan';

    protected $fillable = [
        'rkas_item_id',
        'bulan',
        'rencana'
    ];

    public function rkasItem(): BelongsTo
    {
        return $this->belongsTo(RkasItem::class);
    }
}
