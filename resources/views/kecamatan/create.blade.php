<x-app-layout>
    <x-slot name="header">
        <div class="page-title">Tambah Kecamatan</div>
    </x-slot>

    <div class="max-w-2xl mx-auto">
        <div class="card">
            <div class="card-header">
                <span class="card-title">Form Kecamatan Baru</span>
                <a href="{{ route('kecamatan.index') }}" class="btn btn-secondary btn-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    Kembali
                </a>
            </div>
            <div class="card-body">
                <form action="{{ route('kecamatan.store') }}" method="POST">
                    @csrf
                    <div class="mb-5">
                        <label for="nama" class="form-label">Nama Kecamatan</label>
                        <input type="text" name="nama" id="nama" class="form-input" required>
                    </div>
                    <div class="mb-5">
                        <label for="kabupaten" class="form-label">Kabupaten/Kota</label>
                        <input type="text" name="kabupaten" id="kabupaten" class="form-input">
                    </div>
                    <div class="mb-6">
                        <label for="provinsi" class="form-label">Provinsi</label>
                        <input type="text" name="provinsi" id="provinsi" class="form-input">
                    </div>
                    <div class="flex items-center justify-end gap-2 pt-4 border-t border-slate-100">
                        <a href="{{ route('kecamatan.index') }}" class="btn btn-secondary">Batal</a>
                        <button type="submit" class="btn-primary">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            Simpan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
