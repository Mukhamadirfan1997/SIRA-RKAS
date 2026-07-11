<?php

namespace App\Observers;

use App\Models\TransaksiBku;
use App\Models\AuditLog;

class TransaksiBkuObserver
{
    public function created(TransaksiBku $transaksi): void
    {
        if (auth()->check()) {
            AuditLog::create([
                'user_id' => auth()->id(),
                'tabel' => 'transaksi_bku',
                'aksi' => 'create',
                'data_baru' => $transaksi->toArray(),
            ]);
        }
    }

    public function updated(TransaksiBku $transaksi): void
    {
        if (auth()->check()) {
            AuditLog::create([
                'user_id' => auth()->id(),
                'tabel' => 'transaksi_bku',
                'aksi' => 'update',
                'data_lama' => $transaksi->getOriginal(),
                'data_baru' => $transaksi->getChanges(),
            ]);
        }
    }

    public function deleted(TransaksiBku $transaksi): void
    {
        if (auth()->check()) {
            AuditLog::create([
                'user_id' => auth()->id(),
                'tabel' => 'transaksi_bku',
                'aksi' => 'delete',
                'data_lama' => $transaksi->toArray(),
            ]);
        }
    }
}
