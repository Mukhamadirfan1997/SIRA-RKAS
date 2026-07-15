<x-app-layout>
    <x-slot name="header">
        <div class="page-title">Tambah Transaksi BKU</div>
    </x-slot>

    <div class="w-full">
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

                    {{-- Section 1: Info Dasar --}}
                    <div class="mb-2">
                        <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-3">Informasi Transaksi</h3>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                        <div>
                            <label for="tanggal" class="form-label">Tanggal</label>
                            <input type="date" name="tanggal" id="tanggal" value="{{ old('tanggal', date('Y-m-d')) }}" class="form-input" required>
                            @error('tanggal')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label for="jenis" class="form-label">Jenis Transaksi</label>
                            <select name="jenis" id="jenis" class="form-select" required>
                                <option value="penerimaan" {{ old('jenis') == 'penerimaan' ? 'selected' : '' }}>Penerimaan</option>
                                <option value="pengeluaran" {{ old('jenis') == 'pengeluaran' ? 'selected' : '' }}>Pengeluaran</option>
                            </select>
                            @error('jenis')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label for="no_bukti" class="form-label">No Bukti</label>
                            <input type="text" name="no_bukti" id="no_bukti" value="{{ old('no_bukti') }}" class="form-input bg-slate-50 text-slate-500 font-mono text-sm" readonly required>
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
                            <select name="rkas_item_id" id="rkas_item_id" class="form-select" style="width:100%">
                                <option value="">-- Cari Item RKAS --</option>
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
                        <input type="number" name="jumlah" id="jumlah" value="{{ old('jumlah') }}" class="form-input text-lg font-bold" step="0.01" required>
                        @error('jumlah')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-5">
                        <div>
                            <label for="toko_penerima" class="form-label">Toko / Penerima / Sumber Dana</label>
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
                        <textarea name="uraian" id="uraian" rows="3" class="form-input" placeholder="Keterangan tambahan (opsional)">{{ old('uraian') }}</textarea>
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
            const rkasSelect = $('#rkas_item_id');
            const hargaInput = document.getElementById('harga_satuan');
            const volumeInput = document.getElementById('volume_barang');
            const jumlahInput = document.getElementById('jumlah');
            const jenisSelect = document.getElementById('jenis');
            const tanggalInput = document.getElementById('tanggal');
            const noBuktiInput = document.getElementById('no_bukti');

            const rowRkas = document.getElementById('row_rkas_item');
            const rowKalkulator = document.getElementById('row_kalkulator');
            const rowMetodePengadaan = document.getElementById('row_metode_pengadaan');

            const detailCard = document.getElementById('rkas_detail_card');
            const detailProgram = document.getElementById('detail_program');
            const detailKode = document.getElementById('detail_kode');
            const detailTarif = document.getElementById('detail_tarif');
            const detailSisa = document.getElementById('detail_sisa');

            const npsnCode = "{{ isset($npsn) ? $npsn : '00000000' }}";
            const countPenerimaan = {{ isset($countPenerimaan) ? $countPenerimaan : 1 }};
            const countPengeluaran = {{ isset($countPengeluaran) ? $countPengeluaran : 1 }};

            const rkasData = {};

            rkasSelect.select2({
                ajax: {
                    url: '{{ route("rkas-items.select2") }}',
                    dataType: 'json',
                    delay: 300,
                    data: function(params) {
                        return { q: params.term, page: params.page || 1 };
                    },
                    processResults: function(data) {
                        data.results.forEach(function(item) {
                            rkasData[item.id] = item;
                        });
                        return data;
                    },
                    cache: true
                },
                placeholder: '-- Cari Item RKAS --',
                minimumInputLength: 0,
                allowClear: true,
                templateResult: function(item) {
                    if (item.loading) return item.text;
                    return item.text;
                },
                templateSelection: function(item) {
                    if (item.id && rkasData[item.id]) {
                        return rkasData[item.id].text;
                    }
                    return item.text || '-- Cari Item RKAS --';
                }
            });

            function generateNoBukti() {
                var dateVal = tanggalInput.value;
                if (!dateVal) return;
                var dateObj = new Date(dateVal);
                var m = String(dateObj.getMonth() + 1).padStart(2, '0');
                var y = dateObj.getFullYear();
                if (jenisSelect.value === 'penerimaan') {
                    var num = String(countPenerimaan).padStart(3, '0');
                    noBuktiInput.value = 'BBU' + num + '/' + npsnCode + '/' + m + '/' + y;
                } else {
                    var num = String(countPengeluaran).padStart(3, '0');
                    noBuktiInput.value = 'BPU' + num + '/' + npsnCode + '/' + m + '/' + y;
                }
            }

            function toggleVisibility() {
                if (jenisSelect.value === 'penerimaan') {
                    rowRkas.style.display = 'none';
                    rowKalkulator.style.display = 'none';
                    rowMetodePengadaan.style.display = 'none';
                    rkasSelect.val(null).trigger('change');
                    hargaInput.value = '';
                    hargaInput.dataset.val = 0;
                    volumeInput.value = '';
                    hideDetailCard();
                } else {
                    rowRkas.style.display = 'block';
                    rowKalkulator.style.display = 'block';
                    rowMetodePengadaan.style.display = 'block';
                }
                generateNoBukti();
            }

            function showDetailCard(data) {
                if (!data || (!data.program && !data.kode)) { hideDetailCard(); return; }
                detailProgram.textContent = data.program || '-';
                detailKode.textContent = data.kode || '-';
                var tarifText = data.tarif > 0 ? 'Rp ' + new Intl.NumberFormat('id-ID').format(data.tarif) + ' / ' + (data.satuan || '-') : '-';
                detailTarif.textContent = tarifText;
                detailSisa.textContent = 'Rp ' + new Intl.NumberFormat('id-ID').format(data.sisa || 0);
                detailCard.classList.remove('hidden');
            }

            function hideDetailCard() {
                detailCard.classList.add('hidden');
            }

            function updateHarga(data) {
                var tarif = data ? (parseFloat(data.tarif) || 0) : 0;
                if (tarif > 0) {
                    hargaInput.value = 'Rp ' + new Intl.NumberFormat('id-ID').format(tarif);
                    hargaInput.dataset.val = tarif;
                } else {
                    hargaInput.value = '';
                    hargaInput.dataset.val = 0;
                }
                kalkulasiJumlah();
                if (data) {
                    showDetailCard(data);
                } else {
                    hideDetailCard();
                }
            }

            function kalkulasiJumlah() {
                var tarif = parseFloat(hargaInput.dataset.val) || 0;
                var volume = parseFloat(volumeInput.value) || 0;
                if (tarif > 0 && volume > 0 && jenisSelect.value === 'pengeluaran') {
                    jumlahInput.value = (tarif * volume).toFixed(2);
                }
            }

            rkasSelect.on('select2:select', function(e) {
                var data = e.params.data;
                if (data && data.id) {
                    rkasData[data.id] = data;
                }
                updateHarga(data);
            });

            rkasSelect.on('select2:clear', function() {
                updateHarga(null);
            });

            jenisSelect.addEventListener('change', toggleVisibility);
            volumeInput.addEventListener('input', kalkulasiJumlah);
            tanggalInput.addEventListener('change', generateNoBukti);

            toggleVisibility();
            generateNoBukti();
        });
    </script>
</x-app-layout>
