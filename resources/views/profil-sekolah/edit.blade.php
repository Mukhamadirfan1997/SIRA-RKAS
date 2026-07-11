<x-app-layout>
    <x-slot name="header">
        <div class="page-title">Edit Profil Sekolah</div>
    </x-slot>

    <div class="max-w-3xl mx-auto">
        <div class="card">
            <div class="card-header">
                <span class="card-title">Form Edit Profil Sekolah</span>
                <a href="{{ route('profil-sekolah.index') }}" class="btn btn-secondary btn-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    Kembali
                </a>
            </div>
            <div class="card-body">
                <form action="{{ route('profil-sekolah.update', $profil) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div>
                            <label for="npsn" class="form-label">NPSN</label>
                            <input type="text" name="npsn" id="npsn" value="{{ old('npsn', $profil->npsn) }}" class="form-input" required>
                            @error('npsn') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="nama" class="form-label">Nama Sekolah</label>
                            <input type="text" name="nama" id="nama" value="{{ old('nama', $profil->nama) }}" class="form-input" required>
                            @error('nama') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div class="md:col-span-2">
                            <label for="alamat" class="form-label">Alamat</label>
                            <textarea name="alamat" id="alamat" rows="2" class="form-input">{{ old('alamat', $profil->alamat) }}</textarea>
                        </div>
                        <div>
                            <label for="kecamatan_id" class="form-label">Kecamatan</label>
                            <select name="kecamatan_id" id="kecamatan_id" class="form-select">
                                <option value="">Pilih Kecamatan (Opsional)</option>
                                @foreach($kecamatans as $kecamatan)
                                    <option value="{{ $kecamatan->id }}" {{ $profil->kecamatan_id == $kecamatan->id ? 'selected' : '' }}>{{ $kecamatan->nama }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="kecamatan" class="form-label">Kecamatan (Teks Pendek)</label>
                            <input type="text" name="kecamatan" id="kecamatan" value="{{ old('kecamatan', $profil->kecamatan) }}" class="form-input">
                        </div>
                        <div>
                            <label for="kabupaten" class="form-label">Kabupaten</label>
                            <input type="text" name="kabupaten" id="kabupaten" value="{{ old('kabupaten', $profil->kabupaten) }}" class="form-input">
                        </div>
                        <div>
                            <label for="provinsi" class="form-label">Provinsi</label>
                            <input type="text" name="provinsi" id="provinsi" value="{{ old('provinsi', $profil->provinsi) }}" class="form-input">
                        </div>
                        <div>
                            <label for="nama_kepsek" class="form-label">Nama Kepala Sekolah</label>
                            <input type="text" name="nama_kepsek" id="nama_kepsek" value="{{ old('nama_kepsek', $profil->nama_kepsek) }}" class="form-input">
                        </div>
                        <div>
                            <label for="nip_kepsek" class="form-label">NIP Kepala Sekolah</label>
                            <input type="text" name="nip_kepsek" id="nip_kepsek" value="{{ old('nip_kepsek', $profil->nip_kepsek) }}" class="form-input">
                        </div>
                        <div>
                            <label for="nama_bendahara" class="form-label">Nama Bendahara</label>
                            <input type="text" name="nama_bendahara" id="nama_bendahara" value="{{ old('nama_bendahara', $profil->nama_bendahara) }}" class="form-input">
                        </div>
                        <div>
                            <label for="nip_bendahara" class="form-label">NIP Bendahara</label>
                            <input type="text" name="nip_bendahara" id="nip_bendahara" value="{{ old('nip_bendahara', $profil->nip_bendahara) }}" class="form-input">
                        </div>
                    </div>
                    <div class="flex items-center justify-end gap-2 mt-6 pt-4 border-t border-slate-100">
                        <a href="{{ route('profil-sekolah.index') }}" class="btn btn-secondary">Batal</a>
                        <button type="submit" class="btn-primary">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            Update
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
