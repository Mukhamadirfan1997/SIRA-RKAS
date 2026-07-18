<section>
    <header class="mb-6">
        <h2 class="text-lg font-semibold text-slate-800">Informasi Profil</h2>
        <p class="mt-1 text-sm text-slate-500">Perbarui informasi profil dan alamat email akun Anda.</p>
    </header>

    <form method="post" action="{{ route('profile.update') }}" class="space-y-5">
        @csrf
        @method('patch')

        <div>
            <label class="form-label">Nama</label>
            <input id="name" name="name" type="text" value="{{ old('name', $user->name) }}" required autofocus autocomplete="name" class="form-input" />
            @error('name')
                <p class="text-red-600 text-xs mt-2">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label class="form-label">Email</label>
            <input id="email" name="email" type="email" value="{{ old('email', $user->email) }}" required autocomplete="username" class="form-input" />
            @error('email')
                <p class="text-red-600 text-xs mt-2">{{ $message }}</p>
            @enderror
        </div>

        <div class="flex items-center gap-4">
            <button type="submit" class="btn-primary">Simpan</button>
            @if (session('status') === 'profile-updated')
                <p class="text-sm text-emerald-600">✓ Tersimpan</p>
            @endif
        </div>
    </form>
</section>
