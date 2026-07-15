<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\ProfilSekolah;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;

class UserSekolahController extends Controller
{
    public function index(Request $request)
    {
        $query = User::with('profilSekolah')->orderBy('name');

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('email', 'LIKE', "%{$search}%");
            });
        }

        $users = $query->paginate(50);
        return view('user-sekolah.index', compact('users'));
    }

    public function create()
    {
        $sekolahs = ProfilSekolah::orderBy('nama')->get();
        return view('user-sekolah.create', compact('sekolahs'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'       => 'required|string|max:255',
            'email'      => 'required|string|email|max:255|unique:' . User::class,
            'password'   => ['required', 'confirmed', Rules\Password::defaults()],
            'sekolah_id' => 'nullable|exists:profil_sekolah,id',
            'role'       => 'required|string|in:sekolah,admin-kecamatan',
            'is_active'  => 'nullable|boolean',
        ]);

        // Jika role sekolah, sekolah_id wajib diisi
        if ($validated['role'] === 'sekolah' && empty($validated['sekolah_id'])) {
            return back()->withErrors(['sekolah_id' => 'Profil Sekolah wajib dipilih untuk role Sekolah.'])->withInput();
        }

        $user = User::create([
            'name'       => $validated['name'],
            'email'      => $validated['email'],
            'password'   => Hash::make($validated['password']),
            'sekolah_id' => $validated['role'] === 'admin-kecamatan' ? null : ($validated['sekolah_id'] ?? null),
            'is_active'  => $request->has('is_active') ? 1 : 1, // default aktif
        ]);

        $user->assignRole($validated['role']);

        return redirect()->route('user-sekolah.index')->with('success', 'User berhasil ditambahkan.');
    }

    public function edit(User $user_sekolah)
    {
        $sekolahs = ProfilSekolah::orderBy('nama')->get();
        return view('user-sekolah.edit', ['user' => $user_sekolah, 'sekolahs' => $sekolahs]);
    }

    public function update(Request $request, User $user_sekolah)
    {
        $rules = [
            'name'       => 'required|string|max:255',
            'email'      => 'required|string|email|max:255|unique:users,email,' . $user_sekolah->id,
            'sekolah_id' => 'nullable|exists:profil_sekolah,id',
            'role'       => 'required|string|in:sekolah,admin-kecamatan',
        ];

        if ($request->filled('password')) {
            $rules['password'] = ['confirmed', Rules\Password::defaults()];
        }

        $validated = $request->validate($rules);

        // Jika role sekolah, sekolah_id wajib diisi
        if ($validated['role'] === 'sekolah' && empty($validated['sekolah_id'])) {
            return back()->withErrors(['sekolah_id' => 'Profil Sekolah wajib dipilih untuk role Sekolah.'])->withInput();
        }

        $user_sekolah->name      = $validated['name'];
        $user_sekolah->email     = $validated['email'];
        $user_sekolah->sekolah_id = $validated['role'] === 'admin-kecamatan' ? null : ($validated['sekolah_id'] ?? null);
        $user_sekolah->is_active  = $request->has('is_active');

        if ($request->filled('password')) {
            $user_sekolah->password = Hash::make($validated['password']);
        }

        $user_sekolah->save();
        $user_sekolah->syncRoles([$validated['role']]);

        return redirect()->route('user-sekolah.index')->with('success', 'User berhasil diupdate.');
    }

    public function destroy(User $user_sekolah)
    {
        $user_sekolah->delete();
        return back()->with('success', 'User berhasil dihapus.');
    }

    /**
     * Reset password ke NPSN sekolah user (atau 'sira-rkas' jika tidak punya sekolah)
     */
    public function resetPassword(User $user_sekolah)
    {
        $user_sekolah->load('profilSekolah');
        $npsn = $user_sekolah->profilSekolah?->npsn ?? '';
        $defaultPassword = 'sira-rkas@' . $npsn;
        $user_sekolah->update(['password' => Hash::make($defaultPassword)]);
        return back()->with('success', 'Password ' . $user_sekolah->name . ' telah direset ke: ' . $defaultPassword);
    }

    /**
     * Toggle aktif/nonaktif akun
     */
    public function toggleActive(User $user_sekolah)
    {
        $user_sekolah->update(['is_active' => !$user_sekolah->is_active]);
        $status = $user_sekolah->is_active ? 'diaktifkan' : 'dinonaktifkan';
        return back()->with('success', 'Akun ' . $user_sekolah->name . ' berhasil ' . $status . '.');
    }
}
