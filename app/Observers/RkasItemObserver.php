<?php

namespace App\Observers;

use App\Models\RkasItem;
use App\Models\AuditLog;

class RkasItemObserver
{
    public function created(RkasItem $item): void
    {
        if (auth()->check()) {
            AuditLog::create([
                'user_id' => auth()->id(),
                'tabel' => 'rkas_item',
                'aksi' => 'create',
                'data_baru' => $item->toArray(),
            ]);
        }
    }

    public function updated(RkasItem $item): void
    {
        if (auth()->check()) {
            AuditLog::create([
                'user_id' => auth()->id(),
                'tabel' => 'rkas_item',
                'aksi' => 'update',
                'data_lama' => $item->getOriginal(),
                'data_baru' => $item->getChanges(),
            ]);
        }
    }

    public function deleted(RkasItem $item): void
    {
        if (auth()->check()) {
            AuditLog::create([
                'user_id' => auth()->id(),
                'tabel' => 'rkas_item',
                'aksi' => 'delete',
                'data_lama' => $item->toArray(),
            ]);
        }
    }
}
