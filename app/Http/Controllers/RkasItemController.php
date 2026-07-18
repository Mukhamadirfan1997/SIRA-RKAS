<?php

namespace App\Http\Controllers;

use App\Models\RkasItem;
use App\Models\SumberDana;
use Illuminate\Http\Request;

class RkasItemController extends Controller
{
    /**
     * @return \Illuminate\Http\JsonResponse
     */
    public function select2(Request $request): \Illuminate\Http\JsonResponse
    {
        $searchRaw = $request->input('q');
        $search = is_string($searchRaw) ? $searchRaw : '';
        $excludeIds = $request->input('exclude', []);

        $query = RkasItem::with('program', 'kodeRekening', 'sumberDana')
            ->withSum(['transaksiBkus as realisasi_sum' => fn(\Illuminate\Database\Eloquent\Relations\Relation $q) => $q->where('jenis', 'pengeluaran')], 'jumlah')
            ->orderBy('no_urut');

        if ($search !== '') {
            $query->where(function (\Illuminate\Database\Eloquent\Builder $q) use ($search) {
                $q->where('no_urut', 'LIKE', "%{$search}%")
                  ->orWhere('uraian', 'LIKE', "%{$search}%");
            });
        }

        if (!empty($excludeIds)) {
            $query->whereNotIn('id', (array) $excludeIds);
        }

        $items = $query->paginate(20);

        $results = $items->map(function (RkasItem $item): array { return [
            'id' => $item->id,
            'text' => $item->no_urut . '. ' . $item->uraian . ' — ' . ($item->sumberDana->kode ?? '-') . ' (Sisa: Rp ' . number_format($item->jumlah - $item->realisasi_sum, 0, ',', '.') . ')',
            'tarif' => $item->tarif,
            'program' => $item->program->nama ?? '-',
            'kode' => $item->kodeRekening->kode ?? '-',
            'satuan' => $item->satuan ?? '',
            'sisa' => $item->jumlah - $item->realisasi_sum,
        ]; });

        return response()->json([
            'results' => $results,
            'pagination' => [
                'more' => $items->hasMorePages(),
            ],
        ]);
    }
}
