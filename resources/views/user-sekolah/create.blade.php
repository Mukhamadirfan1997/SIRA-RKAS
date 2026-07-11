<x-app-layout>
    <x-slot name="header">
        <div class="page-title">Tambah User Akun</div>
    </x-slot>

    <div class="max-w-2xl mx-auto">
        <div class="card">
            <div class="card-header">
                <span class="card-title">Form User Baru</span>
                <a href="{{ route('user-sekolah.index') }}" class="btn btn-secondary btn-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    Kembali
                </a>
            </div>
            <div class="card-body">
                @if($errors->any())
                    <div class="alert-error mb-6">
                        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        <ul class="list-disc list-inside text-sm">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form action="{{ route('user-sekolah.store') }}" method="POST" id="userForm">
                    @csrf

                    <div class="mb-5">
                        <label class="form-label">Nama Lengkap</label>
                        <input type="text" name="name" value="{{ old('name') }}" required class="form-input">
                    </div>

                    <div class="mb-5">
                        <label class="form-label">Email (untuk Login)</label>
                        <input type="email" name="email" value="{{ old('email') }}" required class="form-input">
                    </div>

                    <div class="mb-5">
                        <label class="form-label">Password</label>
                        <input type="password" name="password" required class="form-input">
                    </div>

                    <div class="mb-5">
                        <label class="form-label">Konfirmasi Password</label>
                        <input type="password" name="password_confirmation" required class="form-input">
                    </div>

                    <div class="mb-5">
                        <label class="form-label">Role / Hak Akses</label>
                        <select name="role" id="roleSelect" required onchange="handleRoleChange()" class="form-select">
                            <option value="">-- Pilih Role --</option>
                            <option value="sekolah" {{ old('role') == 'sekolah' ? 'selected' : '' }}>Sekolah (Bendahara/Kepsek)</option>
                            <option value="admin-kecamatan" {{ old('role') == 'admin-kecamatan' ? 'selected' : '' }}>Admin Kecamatan</option>
                        </select>
                    </div>

                    <div class="mb-6" id="sekolahField">
                        <label class="form-label">
                            Terkait Sekolah <span id="sekolahRequired" class="text-red-500">*</span>
                        </label>
                        <select name="sekolah_id" id="sekolahSelect" class="form-select">
                            <option value="">-- Pilih Sekolah --</option>
                            @foreach($sekolahs as $sekolah)
                                <option value="{{ $sekolah->id }}" {{ old('sekolah_id') == $sekolah->id ? 'selected' : '' }}>
                                    {{ $sekolah->nama }} ({{ $sekolah->npsn }})
                                </option>
                            @endforeach
                        </select>
                        @error('sekolah_id')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                        <p class="mt-1 text-xs text-slate-400" id="sekolahHint">Wajib diisi jika role Sekolah.</p>
                    </div>

                    <div class="flex justify-end gap-2 pt-4 border-t border-slate-100">
                        <a href="{{ route('user-sekolah.index') }}" class="btn btn-secondary">Batal</a>
                        <button type="submit" class="btn-primary">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            Simpan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function handleRoleChange() {
            const role = document.getElementById('roleSelect').value;
            const sekolahField = document.getElementById('sekolahField');
            const sekolahSelect = document.getElementById('sekolahSelect');
            const hint = document.getElementById('sekolahHint');

            if (role === 'admin-kecamatan') {
                sekolahField.style.opacity = '0.4';
                sekolahSelect.disabled = true;
                sekolahSelect.value = '';
                hint.textContent = 'Admin Kecamatan tidak perlu terkait sekolah.';
            } else {
                sekolahField.style.opacity = '1';
                sekolahSelect.disabled = false;
                hint.textContent = 'Wajib diisi jika role Sekolah.';
            }
        }
        document.addEventListener('DOMContentLoaded', handleRoleChange);
    </script>
</x-app-layout>
