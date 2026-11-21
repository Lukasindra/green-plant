@extends('layouts.admin')

@section('judul', 'Pengaturan')

@section('isi')
    @php
        $formatUang = fn ($nilai) => 'Rp ' . number_format((float) $nilai, 0, ',', '.');
        $kunciSumber = \Illuminate\Support\Facades\Schema::hasTable('pengaturan');

        // Baris yang dikirim sebelumnya (old) dipakai bila ada, tanpa baris yang benar-benar kosong
        $barisTerisi = fn ($daftar) => collect($daftar)
            ->filter(fn ($b) => is_array($b) && trim(implode('', array_map('strval', $b))) !== '')
            ->values();

        $rekeningLama = $barisTerisi(old('rekening', []));
        $daftarRekening = $rekeningLama->isNotEmpty() ? $rekeningLama : collect($rekening);
        if ($daftarRekening->isEmpty()) {
            $daftarRekening = collect([['bank' => '', 'nomor' => '', 'nama' => '']]);
        }

        $ewalletLama = $barisTerisi(old('ewallet', []));
        $daftarEwallet = $ewalletLama->isNotEmpty() ? $ewalletLama : collect($ewallet);
        if ($daftarEwallet->isEmpty()) {
            $daftarEwallet = collect([['nama' => '', 'nomor' => '']]);
        }
    @endphp

    <div class="mx-auto max-w-3xl space-y-6">
        @if (! $kunciSumber)
            <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800" role="alert">
                Perubahan disimpan ke tabel <code>pengaturan</code>; tabel belum ada sehingga tampilan memakai nilai bawaan <code>config/toko.php</code>.
            </div>
        @endif

        <form method="POST" action="{{ route('admin.pengaturan.simpan') }}" id="form-pengaturan"
            class="space-y-6 rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
            @csrf
            @method('PUT')

            {{-- Ongkir --}}
            <fieldset class="space-y-3">
                <legend class="text-sm font-semibold text-gray-900">Biaya Antar (Ongkir)</legend>
                <p class="text-xs text-gray-500">Berpakai untuk pesanan produk checkout. Saat ini: <strong>{{ $formatUang($ongkir) }}</strong></p>
                <div>
                    <label for="ongkir" class="mb-1 block text-sm font-medium text-gray-700">Ongkir per pesanan</label>
                    <input type="number" id="ongkir" name="ongkir" min="0" step="500" required
                        value="{{ old('ongkir', $ongkir) }}"
                        class="w-48 rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm focus:border-brand-600 focus:outline-none focus:ring-1 focus:ring-brand-600">
                    @error('ongkir') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
                </div>
            </fieldset>

            {{-- Rekening --}}
            <fieldset class="space-y-3">
                <legend class="text-sm font-semibold text-gray-900">Rekening Transfer</legend>
                <p class="text-xs text-gray-500">Maksimal 5 baris. Gunakan tombol silang untuk menghapus baris.</p>
                @error('rekening') <p class="text-sm text-red-700">{{ $message }}</p> @enderror

                <div id="baris-rekening" class="space-y-2">
                    @foreach ($daftarRekening as $i => $baris)
                        <div data-baris class="flex items-start gap-2">
                            <div class="grid grow grid-cols-3 gap-2">
                                <x-input nama="rekening[{{ $i }}][bank]" aria-label="Nama bank {{ $i + 1 }}"
                                    placeholder="Bank" value="{{ $baris['bank'] ?? '' }}" />
                                <x-input nama="rekening[{{ $i }}][nomor]" aria-label="Nomor rekening {{ $i + 1 }}"
                                    placeholder="Nomor" value="{{ $baris['nomor'] ?? '' }}" />
                                <x-input nama="rekening[{{ $i }}][nama]" aria-label="Nama pemilik rekening {{ $i + 1 }}"
                                    placeholder="Nama pemilik" value="{{ $baris['nama'] ?? '' }}" />
                            </div>
                            <button type="button" data-hapus-baris aria-label="Hapus baris rekening {{ $i + 1 }}"
                                class="mt-1 shrink-0 rounded-lg border border-gray-300 px-2.5 py-2 text-xs text-gray-500 transition hover:bg-red-50 hover:text-red-700">
                                &#10005;
                            </button>
                        </div>
                    @endforeach
                </div>

                <template id="template-rekening">
                    <div data-baris class="flex items-start gap-2">
                        <div class="grid grow grid-cols-3 gap-2">
                            <x-input nama="rekening[__I__][bank]" aria-label="Nama bank" placeholder="Bank" />
                            <x-input nama="rekening[__I__][nomor]" aria-label="Nomor rekening" placeholder="Nomor" />
                            <x-input nama="rekening[__I__][nama]" aria-label="Nama pemilik rekening" placeholder="Nama pemilik" />
                        </div>
                        <button type="button" data-hapus-baris aria-label="Hapus baris"
                            class="mt-1 shrink-0 rounded-lg border border-gray-300 px-2.5 py-2 text-xs text-gray-500 transition hover:bg-red-50 hover:text-red-700">
                            &#10005;
                        </button>
                    </div>
                </template>

                <x-button varian="outline" ukuran="sm" type="button" id="tambah-rekening">+ Tambah Baris</x-button>
            </fieldset>

            {{-- E-Wallet --}}
            <fieldset class="space-y-3">
                <legend class="text-sm font-semibold text-gray-900">E-Wallet</legend>
                <p class="text-xs text-gray-500">Maksimal 5 baris. Gunakan tombol silang untuk menghapus baris.</p>
                @error('ewallet') <p class="text-sm text-red-700">{{ $message }}</p> @enderror

                <div id="baris-ewallet" class="space-y-2">
                    @foreach ($daftarEwallet as $i => $baris)
                        <div data-baris class="flex items-start gap-2">
                            <div class="grid grow grid-cols-2 gap-2">
                                <x-input nama="ewallet[{{ $i }}][nama]" aria-label="Nama e-wallet {{ $i + 1 }}"
                                    placeholder="Nama e-wallet" value="{{ $baris['nama'] ?? '' }}" />
                                <x-input nama="ewallet[{{ $i }}][nomor]" aria-label="Nomor e-wallet {{ $i + 1 }}"
                                    placeholder="Nomor" value="{{ $baris['nomor'] ?? '' }}" />
                            </div>
                            <button type="button" data-hapus-baris aria-label="Hapus baris e-wallet {{ $i + 1 }}"
                                class="mt-1 shrink-0 rounded-lg border border-gray-300 px-2.5 py-2 text-xs text-gray-500 transition hover:bg-red-50 hover:text-red-700">
                                &#10005;
                            </button>
                        </div>
                    @endforeach
                </div>

                <template id="template-ewallet">
                    <div data-baris class="flex items-start gap-2">
                        <div class="grid grow grid-cols-2 gap-2">
                            <x-input nama="ewallet[__I__][nama]" aria-label="Nama e-wallet" placeholder="Nama e-wallet" />
                            <x-input nama="ewallet[__I__][nomor]" aria-label="Nomor e-wallet" placeholder="Nomor" />
                        </div>
                        <button type="button" data-hapus-baris aria-label="Hapus baris"
                            class="mt-1 shrink-0 rounded-lg border border-gray-300 px-2.5 py-2 text-xs text-gray-500 transition hover:bg-red-50 hover:text-red-700">
                            &#10005;
                        </button>
                    </div>
                </template>

                <x-button varian="outline" ukuran="sm" type="button" id="tambah-ewallet">+ Tambah Baris</x-button>
            </fieldset>

            <div class="flex items-center gap-3 border-t border-gray-100 pt-4">
                <x-button>Simpan Pengaturan</x-button>
            </div>
        </form>
    </div>

    <script>
        (function () {
            var form = document.getElementById('form-pengaturan');
            if (!form) return;

            function pasangDaftar(prefix, batas) {
                var wrap = document.getElementById('baris-' + prefix);
                var template = document.getElementById('template-' + prefix);
                var tambah = document.getElementById('tambah-' + prefix);
                if (!wrap || !template || !tambah) return;

                function jumlahBaris() {
                    return wrap.querySelectorAll('[data-baris]').length;
                }

                function segarkan() {
                    var nomor = 0;
                    wrap.querySelectorAll('[data-baris]').forEach(function (baris) {
                        baris.querySelectorAll('input').forEach(function (input) {
                            input.name = input.name.replace(/\[\d+\]/, '[' + nomor + ']');
                        });
                        nomor++;
                    });
                    tambah.disabled = nomor >= batas;
                    if (tambah.disabled) tambah.title = 'Maksimal ' + batas + ' baris';
                    else tambah.title = '';
                }

                tambah.addEventListener('click', function () {
                    if (jumlahBaris() >= batas) return;
                    var klon = template.content.firstElementChild.cloneNode(true);
                    klon.querySelectorAll('input').forEach(function (input) {
                        input.name = input.name.replace('__I__', String(jumlahBaris()));
                    });
                    wrap.appendChild(klon);
                    segarkan();
                });

                wrap.addEventListener('click', function (e) {
                    var tombolHapus = e.target.closest('[data-hapus-baris]');
                    if (!tombolHapus) return;
                    tombolHapus.closest('[data-baris]').remove();
                    segarkan();
                });

                form.addEventListener('submit', segarkan);
                segarkan();
            }

            pasangDaftar('rekening', 5);
            pasangDaftar('ewallet', 5);
        })();
    </script>
@endsection
