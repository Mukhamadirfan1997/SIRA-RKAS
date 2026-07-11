<x-app-layout>
    <x-slot name="header">
        <div class="page-title">Data User Akun</div>
    </x-slot>

    @if(session('success'))
        <div class="alert-success mb-6">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            {{ session('success') }}
        </div>
    @endif

    <div class="card">
        <div class="card-header">
            <span class="card-title">Daftar User Akun</span>
            <a href="{{ route('user-sekolah.create') }}" class="btn-primary">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Tambah User
            </a>
        </div>
        <div class="overflow-x-auto">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Nama Akun</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Sekolah (NPSN)</th>
                        <th>Status</th>
                        <th>Terakhir Login</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $item)
                    <tr class="{{ $item->is_active ? '' : 'bg-slate-50/50' }}">
                        <td>
                            <div class="font-medium text-slate-800">{{ $item->name }}</div>
                        </td>
                        <td class="text-slate-600">{{ $item->email }}</td>
                        <td>
                            @if($item->isAdminKecamatan())
                                <span class="badge badge-purple">Admin Kecamatan</span>
                            @else
                                <span class="badge badge-blue">Sekolah</span>
                            @endif
                        </td>
                        <td class="text-slate-700">
                            @if($item->profilSekolah)
                                <div class="font-medium">{{ $item->profilSekolah->nama }}</div>
                                <div class="text-xs text-slate-400">NPSN: {{ $item->profilSekolah->npsn }}</div>
                            @else
                                <span class="text-slate-400 italic">-</span>
                            @endif
                        </td>
                        <td>
                            <form action="{{ route('user-sekolah.toggle-active', $item) }}" method="POST" class="inline-flex items-center gap-1.5">
                                @csrf
                                <label class="toggle-switch">
                                    <input type="checkbox" {{ $item->is_active ? 'checked' : '' }} onchange="this.form.submit()">
                                    <span class="toggle-slider"></span>
                                </label>
                                <span class="text-xs {{ $item->is_active ? 'text-emerald-600 font-medium' : 'text-slate-400' }}">{{ $item->is_active ? 'Aktif' : 'Nonaktif' }}</span>
                            </form>
                        </td>
                        <td class="text-slate-600 whitespace-nowrap text-sm">
                            @if($item->last_login_at)
                                <div>{{ $item->last_login_at->translatedFormat('d M Y') }}</div>
                                <div class="text-xs text-slate-400">{{ $item->last_login_at->diffForHumans() }}</div>
                            @else
                                <span class="text-slate-400 italic">Belum pernah login</span>
                            @endif
                        </td>
                        <td class="text-center">
                            <div class="flex items-center justify-center gap-1">
                                <a href="{{ route('user-sekolah.edit', $item) }}" class="btn btn-secondary btn-sm">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                    Edit
                                </a>
                                <form action="{{ route('user-sekolah.reset-password', $item) }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit" onclick="return confirm('Reset password {{ $item->name }} ke default?')" class="btn btn-warning btn-sm">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
                                        Reset PW
                                    </button>
                                </form>
                                <form action="{{ route('user-sekolah.destroy', $item) }}" method="POST" class="inline">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Hapus permanen akun ini?')">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="py-12 text-center text-slate-400">
                            <svg class="w-12 h-12 mx-auto mb-3 opacity-30" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"/></svg>
                            <p class="text-sm">Belum ada user terdaftar.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
