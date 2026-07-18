<?php

namespace App\Http\Controllers;

use App\Models\ProfilSekolah;
use App\Models\Kecamatan;
use Illuminate\Http\Request;

class ProfilSekolahController extends Controller
{
    public function index(Request $request): \Illuminate\View\View
    {
        $query = ProfilSekolah::with('kecamatanRef');

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('nama', 'LIKE', "%{$search}%")
                  ->orWhere('npsn', 'LIKE', "%{$search}%");
            });
        }

        $profils = $query->paginate(50);
        return view('profil-sekolah.index', compact('profils'));
    }

    public function create(): \Illuminate\View\View
    {
        $kecamatans = Kecamatan::all();
        return view('profil-sekolah.create', compact('kecamatans'));
    }

    public function store(Request $request): \Illuminate\Http\RedirectResponse
    {
        $validated = $request->validate([
            'npsn' => 'required|string|unique:profil_sekolah',
            'nama' => 'required|string|max:255',
            'kecamatan_id' => 'nullable|exists:kecamatan,id',
            'alamat' => 'nullable|string',
            'nama_kepsek' => 'nullable|string',
            'nip_kepsek' => 'nullable|string',
            'nama_bendahara' => 'nullable|string',
            'nip_bendahara' => 'nullable|string',
        ]);
        ProfilSekolah::create($validated);
        return redirect()->route('profil-sekolah.index')->with('success', 'Profil Sekolah berhasil ditambahkan.');
    }

    public function edit(ProfilSekolah $profil_sekolah): \Illuminate\View\View
    {
        $kecamatans = Kecamatan::all();
        return view('profil-sekolah.edit', ['profil' => $profil_sekolah, 'kecamatans' => $kecamatans]);
    }

    public function update(Request $request, ProfilSekolah $profil_sekolah): \Illuminate\Http\RedirectResponse
    {
        $validated = $request->validate([
            'npsn' => 'required|string|unique:profil_sekolah,npsn,' . $profil_sekolah->id,
            'nama' => 'required|string|max:255',
            'kecamatan_id' => 'nullable|exists:kecamatan,id',
            'alamat' => 'nullable|string',
            'nama_kepsek' => 'nullable|string',
            'nip_kepsek' => 'nullable|string',
            'nama_bendahara' => 'nullable|string',
            'nip_bendahara' => 'nullable|string',
        ]);
        $profil_sekolah->update($validated);
        return redirect()->route('profil-sekolah.index')->with('success', 'Profil Sekolah berhasil diupdate.');
    }

    public function destroy(ProfilSekolah $profil_sekolah): \Illuminate\Http\RedirectResponse
    {
        $profil_sekolah->delete();
        return back()->with('success', 'Profil Sekolah berhasil dihapus.');
    }
}
