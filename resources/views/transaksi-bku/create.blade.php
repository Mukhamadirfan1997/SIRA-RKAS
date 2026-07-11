<x-app-layout>
    <x-slot name="header">
        <div class="page-title">Tambah Transaksi BKU</div>
    </x-slot>

    <div class="max-w-3xl mx-auto">
        <div class="card">
            <div class="card-header">
                <span class="card-title">Form Transaksi Baru</span>
                <a href="{{ route('transaksi-bku.index') }}" class="btn btn-secondary btn-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    Kembali
                </a>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('transaksi-bku.store') }}">
                    @csrf

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div>
                            <label for="tanggal" class="form-label">Tanggal</label>
                            <input type="date" name="tanggal" id="tanggal" value="{{ old('tanggal', date('Y-m-d')) }}" class="form-input" required>
                            @error('tanggal')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="no_bukti" class="form-label">No Bukti</label>
                            <input type="text" name="no_bukti" id="no_bukti" value="{{ old('no_bukti') }}" class="form-input bg-slate-50 text-slate-500" readonly required>
                            @error('no_bukti')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="jenis" class="form-label">Jenis</label>
                            <select name="jenis" id="jenis" class="form-select" required>
                                <option value="penerimaan" {{ old('jenis') == 'penerimaan' ? 'selected' : '' }}>Penerimaan</option>
                                <option value="pengeluaran" {{ old('jenis') == 'pengeluaran' ? 'selected' : '' }}>Pengeluaran</option>
                            </select>
                            @error('jenis')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div id="row_rkas_item">
                            <label for="rkas_item_id" class="form-label">Item RKAS (Opsional)</label>
                            <select name="rkas_item_id" id="rkas_item_id" class="form-select">
                                <option value="" data-tarif="0">Pilih Item RKAS</option>
                                @foreach($rkasItems as $item)
                                    <option value="{{ $item->id }}" data-tarif="{{ $item->tarif }}" {{ old('rkas_item_id') == $item->id ? 'selected' : '' }}>
                                        {{ $item->no_urut }} - {{ $item->uraian }} (Sisa: {{ number_format($item->sisa, 0, ',', '.') }})
                                    </option>
                                @endforeach
                            </select>
                            @error('rkas_item_id')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="my-5 p-4 bg-blue-50 border border-blue-200 rounded-xl" id="row_kalkulator">
                        <label class="block text-sm font-semibold text-blue-800 mb-3">Kalkulator Otomatis</label>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="harga_satuan" class="block text-xs font-medium text-slate-600 mb-1">Harga Satuan (Dari Item RKAS)</label>
                                <input type="text" id="harga_satuan" class="form-input bg-slate-100 text-slate-500" readonly placeholder="Pilih item RKAS dulu">
                            </div>
                            <div>
                                <label for="volume_barang" class="block text-xs font-medium text-slate-600 mb-1">Jumlah Barang (Volume)</label>
                                <input type="number" id="volume_barang" class="form-input" placeholder="Contoh: 10" min="0" step="0.01">
                            </div>
                        </div>
                        <p class="text-xs text-blue-600 mt-2">Isi <strong>Jumlah Barang</strong> untuk menghitung otomatis nominal <strong>Jumlah</strong> di bawah.</p>
                    </div>

                    <div class="mb-5">
                        <label for="jumlah" class="form-label">Jumlah Nominal (Rp)</label>
                        <input type="number" name="jumlah" id="jumlah" value="{{ old('jumlah') }}" class="form-input text-lg font-bold" step="0.01" required>
                        @error('jumlah')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-5">
                        <div>
                            <label for="toko_penerima" class="form-label">Toko/Penerima / Sumber Dana</label>
                            <input type="text" name="toko_penerima" id="toko_penerima" value="{{ old('toko_penerima') }}" class="form-input">
                            @error('toko_penerima')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                        <div id="row_metode_pengadaan">
                            <label for="metode_pengadaan" class="form-label">Metode Pengadaan</label>
                            <select name="metode_pengadaan" id="metode_pengadaan" class="form-select">
                                <option value="">-- Pilih --</option>
                                <option value="siplah" {{ old('metode_pengadaan') == 'siplah' ? 'selected' : '' }}>SIPLAH</option>
                                <option value="non_siplah" {{ old('metode_pengadaan') == 'non_siplah' ? 'selected' : '' }}>Non-SIPLAH</option>
                            </select>
                            @error('metode_pengadaan')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="mb-6">
                        <label for="uraian" class="form-label">Uraian</label>
                        <textarea name="uraian" id="uraian" rows="3" class="form-input">{{ old('uraian') }}</textarea>
                        @error('uraian')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-4 border-t border-slate-100">
                        <a href="{{ route('transaksi-bku.index') }}" class="btn btn-secondary">
                            Batal
                        </a>
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
        document.addEventListener('DOMContentLoaded', function() {
            const rkasSelect = document.getElementById('rkas_item_id');
            const hargaInput = document.getElementById('harga_satuan');
            const volumeInput = document.getElementById('volume_barang');
            const jumlahInput = document.getElementById('jumlah');
            const jenisSelect = document.getElementById('jenis');
            const tanggalInput = document.getElementById('tanggal');
            const noBuktiInput = document.getElementById('no_bukti');

            const rowRkas = document.getElementById('row_rkas_item');
            const rowKalkulator = document.getElementById('row_kalkulator');
            const rowMetodePengadaan = document.getElementById('row_metode_pengadaan');

            const npsnCode = "{{ isset($npsn) ? $npsn : '00000000' }}";
            const countPenerimaan = {{ isset($countPenerimaan) ? $countPenerimaan : 1 }};
            const countPengeluaran = {{ isset($countPengeluaran) ? $countPengeluaran : 1 }};

            function generateNoBukti() {
                const dateVal = tanggalInput.value;
                if (!dateVal) return;
                const dateObj = new Date(dateVal);
                const m = String(dateObj.getMonth() + 1).padStart(2, '0');
                const y = dateObj.getFullYear();
                if (jenisSelect.value === 'penerimaan') {
                    const num = String(countPenerimaan).padStart(3, '0');
                    noBuktiInput.value = `BBU${num}/${npsnCode}/${m}/${y}`;
                } else {
                    const num = String(countPengeluaran).padStart(3, '0');
                    noBuktiInput.value = `BPU${num}/${npsnCode}/${m}/${y}`;
                }
            }

            function toggleVisibility() {
                if (jenisSelect.value === 'penerimaan') {
                    rowRkas.style.display = 'none';
                    rowKalkulator.style.display = 'none';
                    rowMetodePengadaan.style.display = 'none';
                    rkasSelect.value = '';
                    hargaInput.value = '';
                    hargaInput.dataset.val = 0;
                    volumeInput.value = '';
                } else {
                    rowRkas.style.display = 'block';
                    rowKalkulator.style.display = 'block';
                    rowMetodePengadaan.style.display = 'block';
                }
                generateNoBukti();
            }

            function updateHarga() {
                const selectedOption = rkasSelect.options[rkasSelect.selectedIndex];
                const tarif = selectedOption ? parseFloat(selectedOption.getAttribute('data-tarif')) || 0 : 0;
                if (tarif > 0) {
                    hargaInput.value = 'Rp ' + new Intl.NumberFormat('id-ID').format(tarif);
                    hargaInput.dataset.val = tarif;
                } else {
                    hargaInput.value = '';
                    hargaInput.dataset.val = 0;
                }
                kalkulasiJumlah();
            }

            function kalkulasiJumlah() {
                const tarif = parseFloat(hargaInput.dataset.val) || 0;
                const volume = parseFloat(volumeInput.value) || 0;
                if (tarif > 0 && volume > 0 && jenisSelect.value === 'pengeluaran') {
                    jumlahInput.value = (tarif * volume).toFixed(2);
                }
            }

            jenisSelect.addEventListener('change', toggleVisibility);
            rkasSelect.addEventListener('change', updateHarga);
            volumeInput.addEventListener('input', kalkulasiJumlah);
            tanggalInput.addEventListener('change', generateNoBukti);

            toggleVisibility();
            generateNoBukti();
            if(rkasSelect.value) updateHarga();
        });
    </script>
</x-app-layout>
