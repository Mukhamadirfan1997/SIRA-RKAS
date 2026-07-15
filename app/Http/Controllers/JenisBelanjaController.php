<?php

namespace App\Http\Controllers;

use App\Models\JenisBelanja;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class JenisBelanjaController extends Controller
{
    public function index()
    {
        $jenisBelanjas = JenisBelanja::paginate(50);
        return view('jenis-belanja.index', compact('jenisBelanjas'));
    }

    public function create()
    {
        return view('jenis-belanja.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama' => 'required|unique:jenis_belanja,nama',
        ]);

        JenisBelanja::create($validated);

        Cache::forget('jenis_belanjas');

        return redirect()->route('jenis-belanja.index')->with('success', 'Jenis Belanja berhasil ditambahkan.');
    }

    public function edit(JenisBelanja $jenisBelanja)
    {
        return view('jenis-belanja.edit', compact('jenisBelanja'));
    }

    public function update(Request $request, JenisBelanja $jenisBelanja)
    {
        $validated = $request->validate([
            'nama' => 'required|unique:jenis_belanja,nama,' . $jenisBelanja->id,
        ]);

        $jenisBelanja->update($validated);

        Cache::forget('jenis_belanjas');

        return redirect()->route('jenis-belanja.index')->with('success', 'Jenis Belanja berhasil diupdate.');
    }

    public function destroy(JenisBelanja $jenisBelanja)
    {
        $jenisBelanja->delete();
        Cache::forget('jenis_belanjas');
        return back()->with('success', 'Jenis Belanja berhasil dihapus.');
    }
}
