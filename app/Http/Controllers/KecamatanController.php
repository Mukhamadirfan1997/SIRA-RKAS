<?php

namespace App\Http\Controllers;

use App\Models\Kecamatan;
use Illuminate\Http\Request;

class KecamatanController extends Controller
{
    public function index(): \Illuminate\View\View
    {
        $kecamatans = Kecamatan::paginate(50);
        return view('kecamatan.index', compact('kecamatans'));
    }

    public function create(): \Illuminate\View\View
    {
        return view('kecamatan.create');
    }

    public function store(Request $request): \Illuminate\Http\RedirectResponse
    {
        $validated = $request->validate([
            'nama' => 'required|string|max:255',
            'kabupaten' => 'nullable|string|max:255',
            'provinsi' => 'nullable|string|max:255',
        ]);
        Kecamatan::create($validated);
        return redirect()->route('kecamatan.index')->with('success', 'Kecamatan berhasil ditambahkan.');
    }

    public function edit(Kecamatan $kecamatan): \Illuminate\View\View
    {
        return view('kecamatan.edit', compact('kecamatan'));
    }

    public function update(Request $request, Kecamatan $kecamatan): \Illuminate\Http\RedirectResponse
    {
        $validated = $request->validate([
            'nama' => 'required|string|max:255',
            'kabupaten' => 'nullable|string|max:255',
            'provinsi' => 'nullable|string|max:255',
        ]);
        $kecamatan->update($validated);
        return redirect()->route('kecamatan.index')->with('success', 'Kecamatan berhasil diupdate.');
    }

    public function destroy(Kecamatan $kecamatan): \Illuminate\Http\RedirectResponse
    {
        $kecamatan->delete();
        return back()->with('success', 'Kecamatan berhasil dihapus.');
    }
}
