<?php

namespace App\Http\Controllers;

use App\Models\RkasItem;
use App\Models\SumberDana;
use Illuminate\Http\Request;

class RkasItemController extends Controller
{
    public function select2(Request $request)
    {
        $search = $request->get('q');
        $excludeIds = $request->get('exclude', []);

        $query = RkasItem::with('program', 'kodeRekening', 'sumberDana')
            ->withSum(['transaksiBkus as realisasi_sum' => fn($q) => $q->where('jenis', 'pengeluaran')], 'jumlah')
            ->orderBy('no_urut');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('no_urut', 'LIKE', "%{$search}%")
                  ->orWhere('uraian', 'LIKE', "%{$search}%");
            });
        }

        if (!empty($excludeIds)) {
            $query->whereNotIn('id', (array) $excludeIds);
        }

        $items = $query->paginate(20);

        $results = $items->map(fn($item) => [
            'id' => $item->id,
            'text' => $item->no_urut . '. ' . $item->uraian . ' — ' . ($item->sumberDana?->kode ?? '-') . ' (Sisa: Rp ' . number_format($item->jumlah - ($item->realisasi_sum ?? 0), 0, ',', '.') . ')',
            'tarif' => $item->tarif,
            'program' => $item->program?->nama ?? '-',
            'kode' => $item->kodeRekening?->kode ?? '-',
            'satuan' => $item->satuan ?? '',
            'sisa' => $item->jumlah - ($item->realisasi_sum ?? 0),
        ]);

        return response()->json([
            'results' => $results,
            'pagination' => [
                'more' => $items->hasMorePages(),
            ],
        ]);
    }
}
