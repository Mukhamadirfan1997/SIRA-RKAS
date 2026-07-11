<section>
    <header class="mb-6">
        <h2 class="text-lg font-semibold text-slate-800">Perbarui Password</h2>
        <p class="mt-1 text-sm text-slate-500">Pastikan akun Anda menggunakan password yang panjang dan acak untuk tetap aman.</p>
    </header>

    <form method="post" action="{{ route('password.update') }}" class="space-y-5">
        @csrf
        @method('put')

        <div>
            <label class="form-label">Password Saat Ini</label>
            <input id="update_password_current_password" name="current_password" type="password" autocomplete="current-password" class="form-input" />
            @error('current_password')
                <p class="text-red-600 text-xs mt-2">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label class="form-label">Password Baru</label>
            <input id="update_password_password" name="password" type="password" autocomplete="new-password" class="form-input" />
            @error('password')
                <p class="text-red-600 text-xs mt-2">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label class="form-label">Konfirmasi Password Baru</label>
            <input id="update_password_password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" class="form-input" />
            @error('password_confirmation')
                <p class="text-red-600 text-xs mt-2">{{ $message }}</p>
            @enderror
        </div>

        <div class="flex items-center gap-4">
            <button type="submit" class="btn-primary">Simpan</button>
            @if (session('status') === 'password-updated')
                <p class="text-sm text-emerald-600">✓ Tersimpan</p>
            @endif
        </div>
    </form>
</section>
