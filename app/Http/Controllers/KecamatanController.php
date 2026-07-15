<?php

namespace App\Http\Controllers;

use App\Models\Kecamatan;
use Illuminate\Http\Request;

class KecamatanController extends Controller
{
    public function index()
    {
        $kecamatans = Kecamatan::paginate(50);
        return view('kecamatan.index', compact('kecamatans'));
    }

    public function create()
    {
        return view('kecamatan.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama' => 'required|string|max:255',
            'kabupaten' => 'nullable|string|max:255',
            'provinsi' => 'nullable|string|max:255',
        ]);
        Kecamatan::create($validated);
        return redirect()->route('kecamatan.index')->with('success', 'Kecamatan berhasil ditambahkan.');
    }

    public function edit(Kecamatan $kecamatan)
    {
        return view('kecamatan.edit', compact('kecamatan'));
    }

    public function update(Request $request, Kecamatan $kecamatan)
    {
        $validated = $request->validate([
            'nama' => 'required|string|max:255',
            'kabupaten' => 'nullable|string|max:255',
            'provinsi' => 'nullable|string|max:255',
        ]);
        $kecamatan->update($validated);
        return redirect()->route('kecamatan.index')->with('success', 'Kecamatan berhasil diupdate.');
    }

    public function destroy(Kecamatan $kecamatan)
    {
        $kecamatan->delete();
        return back()->with('success', 'Kecamatan berhasil dihapus.');
    }
}
