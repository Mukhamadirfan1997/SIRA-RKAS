<section class="space-y-6">
    <header>
        <h2 class="text-lg font-semibold text-red-700">Hapus Akun</h2>
        <p class="mt-1 text-sm text-slate-500">
            Setelah akun Anda dihapus, semua sumber daya dan datanya akan dihapus secara permanen. Sebelum menghapus akun Anda, harap unduh data atau informasi yang ingin Anda simpan.
        </p>
    </header>

    <button
        type="button"
        x-data=""
        x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')"
        class="btn btn-danger"
    >
        Hapus Akun
    </button>

    <x-modal name="confirm-user-deletion" :show="$errors->userDeletion->isNotEmpty()" focusable>
        <form method="post" action="{{ route('profile.destroy') }}" class="p-8">
            @csrf
            @method('delete')

            <h2 class="text-xl font-bold text-slate-800 mb-2">Apakah Anda yakin ingin menghapus akun?</h2>
            <p class="text-sm text-slate-500 mb-6">
                Setelah akun Anda dihapus, semua sumber daya dan datanya akan dihapus secara permanen. Mohon masukkan password Anda untuk mengonfirmasi bahwa Anda ingin menghapus akun Anda secara permanen.
            </p>

            <div class="mb-6">
                <label class="form-label">Password</label>
                <input id="password" name="password" type="password" placeholder="Masukkan password" class="form-input" />
                @error('password')
                    <p class="text-red-600 text-xs mt-2">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex items-center justify-end gap-4">
                <button type="button" x-on:click="$dispatch('close')" class="btn btn-secondary">Batal</button>
                <button type="submit" class="btn btn-danger">Hapus Akun</button>
            </div>
        </form>
    </x-modal>
</section>
