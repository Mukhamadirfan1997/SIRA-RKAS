<x-guest-layout>
    <div class="text-center mb-8">
        <h2 class="text-2xl font-bold text-slate-800">Konfirmasi Password</h2>
        <p class="text-sm text-slate-500 mt-2">Ini adalah area aman aplikasi. Silakan konfirmasi password Anda sebelum melanjutkan.</p>
    </div>

    <form method="POST" action="{{ route('password.confirm') }}">
        @csrf

        <div class="mb-6">
            <label class="form-label">Password</label>
            <input type="password" name="password" required autocomplete="current-password" class="form-input" />
            @error('password')
                <p class="text-red-600 text-xs mt-2">{{ $message }}</p>
            @enderror
        </div>

        <div class="flex items-center justify-end">
            <button type="submit" class="btn-primary">
                Konfirmasi
            </button>
        </div>
    </form>
</x-guest-layout>
