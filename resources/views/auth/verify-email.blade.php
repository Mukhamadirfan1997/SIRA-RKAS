<x-guest-layout>
    <div class="text-center mb-8">
        <h2 class="text-2xl font-bold text-slate-800">Verifikasi Email</h2>
        <p class="text-sm text-slate-500 mt-2">
            Terima kasih telah mendaftar! Silakan verifikasi alamat email Anda dengan mengklik tautan yang baru saja kami kirimkan. Jika Anda tidak menerima email tersebut, kami akan dengan senang hati mengirimkan yang baru.
        </p>
    </div>

    @if (session('status') == 'verification-link-sent')
        <div class="alert-success">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            Tautan verifikasi baru telah dikirim ke alamat email yang Anda berikan saat pendaftaran.
        </div>
    @endif

    <div class="flex items-center justify-between gap-4 mt-6">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <button type="submit" class="btn-primary">
                Kirim Ulang Email Verifikasi
            </button>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="btn btn-secondary">
                Keluar
            </button>
        </form>
    </div>
</x-guest-layout>
