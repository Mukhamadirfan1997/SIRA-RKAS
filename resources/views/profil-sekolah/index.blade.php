<x-app-layout>
    <x-slot name="header">
        <div class="page-title">Data Profil Sekolah</div>
    </x-slot>

    @if(session('success'))
        <div class="alert-success mb-6">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            {{ session('success') }}
        </div>
    @endif

    <div class="card">
        <div class="card-header">
            <span class="card-title">Daftar Sekolah</span>
            <a href="{{ route('profil-sekolah.create') }}" class="btn-primary">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Tambah Sekolah
            </a>
        </div>
        <div class="overflow-x-auto">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>NPSN</th>
                        <th>Nama Sekolah</th>
                        <th>Kecamatan</th>
                        <th>Kepala Sekolah</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($profils as $item)
                    <tr>
                        <td class="font-mono font-medium text-slate-700">{{ $item->npsn }}</td>
                        <td class="font-medium text-slate-800">{{ $item->nama }}</td>
                        <td class="text-slate-600">{{ $item->kecamatanRef->nama ?? '-' }}</td>
                        <td class="text-slate-600">{{ $item->nama_kepsek }}</td>
                        <td class="text-center">
                            <div class="flex items-center justify-center gap-2">
                                <a href="{{ route('profil-sekolah.edit', $item) }}" class="btn btn-secondary btn-sm">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                    Edit
                                </a>
                                <form action="{{ route('profil-sekolah.destroy', $item) }}" method="POST" class="inline">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Yakin ingin menghapus?')">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
