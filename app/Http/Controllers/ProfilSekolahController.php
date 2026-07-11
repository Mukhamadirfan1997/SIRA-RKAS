<?php

namespace App\Http\Controllers;

use App\Models\ProfilSekolah;
use App\Models\Kecamatan;
use Illuminate\Http\Request;

class ProfilSekolahController extends Controller
{
    public function index()
    {
        $profils = ProfilSekolah::with('kecamatanRef')->get();
        return view('profil-sekolah.index', compact('profils'));
    }

    public function create()
    {
        $kecamatans = Kecamatan::all();
        return view('profil-sekolah.create', compact('kecamatans'));
    }

    public function store(Request $request)
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

    public function edit(ProfilSekolah $profil_sekolah)
    {
        $kecamatans = Kecamatan::all();
        return view('profil-sekolah.edit', ['profil' => $profil_sekolah, 'kecamatans' => $kecamatans]);
    }

    public function update(Request $request, ProfilSekolah $profil_sekolah)
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

    public function destroy(ProfilSekolah $profil_sekolah)
    {
        $profil_sekolah->delete();
        return back()->with('success', 'Profil Sekolah berhasil dihapus.');
    }
}
