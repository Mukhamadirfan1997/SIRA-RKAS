<x-app-layout>
    <x-slot name="header">
        <div class="page-title">Edit Transaksi BKU</div>
    </x-slot>

    <div class="w-full">
        <div class="card">
            <div class="card-header">
                <span class="card-title">Form Edit Transaksi</span>
                <a href="{{ route('transaksi-bku.index') }}" class="btn btn-secondary btn-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    Kembali
                </a>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('transaksi-bku.update', $transaksiBku) }}">
                    @csrf
                    @method('PUT')

                    {{-- Section 1: Info Dasar --}}
                    <div class="mb-2">
                        <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-3">Informasi Transaksi</h3>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                        <div>
                            <label for="tanggal" class="form-label">Tanggal</label>
                            <input type="date" name="tanggal" id="tanggal" value="{{ old('tanggal', $transaksiBku->tanggal) }}" class="form-input" required>
                            @error('tanggal')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label for="jenis" class="form-label">Jenis Transaksi</label>
                            <select name="jenis" id="jenis" class="form-select" required>
                                <option value="penerimaan" {{ old('jenis', strtolower($transaksiBku->jenis)) == 'penerimaan' ? 'selected' : '' }}>Penerimaan</option>
                                <option value="pengeluaran" {{ old('jenis', strtolower($transaksiBku->jenis)) == 'pengeluaran' ? 'selected' : '' }}>Pengeluaran</option>
                            </select>
                            @error('jenis')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label for="no_bukti" class="form-label">No Bukti</label>
                            <input type="text" name="no_bukti" id="no_bukti" value="{{ old('no_bukti', $transaksiBku->no_bukti) }}" class="form-input font-mono text-sm" required>
                            @error('no_bukti')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    {{-- Section 2: Item RKAS --}}
                    <div id="row_rkas_item">
                        <div class="mb-2">
                            <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-3">Item RKAS</h3>
                        </div>
                        <div class="mb-3">
                            <select name="rkas_item_id" id="rkas_item_id" class="form-select">
                                <option value="" data-tarif="0" data-program="" data-kode="" data-satuan="" data-sisa="">-- Pilih Item RKAS --</option>
                                @foreach($rkasItems as $item)
                                    <option value="{{ $item->id }}"
                                        data-tarif="{{ $item->tarif }}"
                                        data-program="{{ $item->program->nama ?? '-' }}"
                                        data-kode="{{ $item->kodeRekening->kode ?? '-' }}"
                                        data-satuan="{{ $item->satuan }}"
                                        data-sisa="{{ $item->sisa }}"
                                        {{ old('rkas_item_id', $transaksiBku->rkas_item_id) == $item->id ? 'selected' : '' }}>
                                        {{ $item->no_urut }}. {{ $item->uraian }} (Sisa: Rp {{ number_format($item->sisa, 0, ',', '.') }})
                                    </option>
                                @endforeach
                            </select>
                            @error('rkas_item_id')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div id="rkas_detail_card" class="hidden mb-6 p-4 bg-indigo-50 border border-indigo-200 rounded-xl">
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-3 text-xs">
                                <div>
                                    <span class="text-slate-500 font-medium">Program</span>
                                    <p class="text-slate-800 font-semibold mt-0.5" id="detail_program">-</p>
                                </div>
                                <div>
                                    <span class="text-slate-500 font-medium">Kode Rekening</span>
                                    <p class="text-slate-800 font-semibold mt-0.5" id="detail_kode">-</p>
                                </div>
                                <div>
                                    <span class="text-slate-500 font-medium">Tarif / Satuan</span>
                                    <p class="text-slate-800 font-semibold mt-0.5" id="detail_tarif">-</p>
                                </div>
                                <div>
                                    <span class="text-slate-500 font-medium">Sisa Anggaran</span>
                                    <p class="text-emerald-700 font-bold mt-0.5" id="detail_sisa">-</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Section 3: Kalkulator --}}
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

                    {{-- Section 4: Nominal & Rincian --}}
                    <div class="mb-2">
                        <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-3">Nominal & Rincian</h3>
                    </div>
                    <div class="mb-5">
                        <label for="jumlah" class="form-label">Jumlah Nominal (Rp)</label>
                        <input type="number" name="jumlah" id="jumlah" value="{{ old('jumlah', $transaksiBku->jumlah) }}" class="form-input text-lg font-bold" step="0.01" required>
                        @error('jumlah')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-5">
                        <div>
                            <label for="toko_penerima" class="form-label">Toko / Penerima / Sumber Dana</label>
                            <input type="text" name="toko_penerima" id="toko_penerima" value="{{ old('toko_penerima', $transaksiBku->toko_penerima) }}" class="form-input">
                            @error('toko_penerima')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div id="row_metode_pengadaan">
                            <label for="metode_pengadaan" class="form-label">Metode Pengadaan</label>
                            <select name="metode_pengadaan" id="metode_pengadaan" class="form-select">
                                <option value="">-- Pilih --</option>
                                <option value="siplah" {{ old('metode_pengadaan', $transaksiBku->metode_pengadaan) == 'siplah' ? 'selected' : '' }}>SIPLAH</option>
                                <option value="non_siplah" {{ old('metode_pengadaan', $transaksiBku->metode_pengadaan) == 'non_siplah' ? 'selected' : '' }}>Non-SIPLAH</option>
                            </select>
                            @error('metode_pengadaan')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="mb-6">
                        <label for="uraian" class="form-label">Uraian</label>
                        <textarea name="uraian" id="uraian" rows="3" class="form-input" placeholder="Keterangan tambahan (opsional)">{{ old('uraian', $transaksiBku->uraian) }}</textarea>
                        @error('uraian')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-4 border-t border-slate-100">
                        <a href="{{ route('transaksi-bku.index') }}" class="btn btn-secondary">Batal</a>
                        <button type="submit" class="btn-primary">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            Update
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

            const rowRkas = document.getElementById('row_rkas_item');
            const rowKalkulator = document.getElementById('row_kalkulator');
            const rowMetodePengadaan = document.getElementById('row_metode_pengadaan');

            const detailCard = document.getElementById('rkas_detail_card');
            const detailProgram = document.getElementById('detail_program');
            const detailKode = document.getElementById('detail_kode');
            const detailTarif = document.getElementById('detail_tarif');
            const detailSisa = document.getElementById('detail_sisa');

            let isInitialLoad = true;

            function toggleVisibility() {
                if (jenisSelect.value === 'penerimaan') {
                    rowRkas.style.display = 'none';
                    rowKalkulator.style.display = 'none';
                    rowMetodePengadaan.style.display = 'none';
                    if (!isInitialLoad) {
                        rkasSelect.value = '';
                        hargaInput.value = '';
                        hargaInput.dataset.val = 0;
                        volumeInput.value = '';
                    }
                    hideDetailCard();
                } else {
                    rowRkas.style.display = 'block';
                    rowKalkulator.style.display = 'block';
                    rowMetodePengadaan.style.display = 'block';
                }
            }

            function showDetailCard(opt) {
                const program = opt.getAttribute('data-program');
                const kode = opt.getAttribute('data-kode');
                const tarif = parseFloat(opt.getAttribute('data-tarif')) || 0;
                const satuan = opt.getAttribute('data-satuan') || '-';
                const sisa = parseFloat(opt.getAttribute('data-sisa')) || 0;
                if (!program && !kode) { hideDetailCard(); return; }
                detailProgram.textContent = program || '-';
                detailKode.textContent = kode || '-';
                detailTarif.textContent = tarif > 0 ? 'Rp ' + new Intl.NumberFormat('id-ID').format(tarif) + ' / ' + satuan : '-';
                detailSisa.textContent = 'Rp ' + new Intl.NumberFormat('id-ID').format(sisa);
                detailCard.classList.remove('hidden');
            }

            function hideDetailCard() {
                detailCard.classList.add('hidden');
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
                if (selectedOption && selectedOption.value) {
                    showDetailCard(selectedOption);
                } else {
                    hideDetailCard();
                }
            }

            function kalkulasiJumlah() {
                if (isInitialLoad) return;
                const tarif = parseFloat(hargaInput.dataset.val) || 0;
                const volume = parseFloat(volumeInput.value) || 0;
                if (tarif > 0 && volume > 0 && jenisSelect.value === 'pengeluaran') {
                    jumlahInput.value = (tarif * volume).toFixed(2);
                }
            }

            jenisSelect.addEventListener('change', function() {
                isInitialLoad = false;
                toggleVisibility();
            });
            rkasSelect.addEventListener('change', function() {
                isInitialLoad = false;
                updateHarga();
            });
            volumeInput.addEventListener('input', function() {
                isInitialLoad = false;
                kalkulasiJumlah();
            });

            toggleVisibility();
            if(rkasSelect.value) {
                const selectedOption = rkasSelect.options[rkasSelect.selectedIndex];
                const tarif = selectedOption ? parseFloat(selectedOption.getAttribute('data-tarif')) || 0 : 0;
                if (tarif > 0) {
                    hargaInput.value = 'Rp ' + new Intl.NumberFormat('id-ID').format(tarif);
                    hargaInput.dataset.val = tarif;
                }
                showDetailCard(selectedOption);
            }
        });
    </script>
</x-app-layout>
