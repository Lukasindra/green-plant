<form method="POST" action="{{ $jasa ? route('admin.jasa.update', $jasa) : route('admin.jasa.store') }}" enctype="multipart/form-data" class="space-y-5 rounded-xl border border-gray-200 bg-white p-5 shadow-sm sm:p-7">
    @csrf
    @if ($jasa)
        @method('PUT')
    @endif

    <x-input nama="nama" label="Nama layanan" wajib maxlength="100" autofocus value="{{ old('nama', $jasa?->nama) }}" />

    <x-select nama="kategori_id" label="Kategori">
        <option value="">Tanpa kategori</option>
        @foreach ($kategori as $item)
            <option value="{{ $item->id }}" @selected(old('kategori_id', $jasa?->kategori_id) == $item->id)>{{ $item->nama }}</option>
        @endforeach
    </x-select>

    <x-textarea nama="deskripsi" label="Deskripsi" baris="4" maxlength="5000">{{ old('deskripsi', $jasa?->deskripsi) }}</x-textarea>

    <div class="grid gap-5 sm:grid-cols-2">
        <x-input nama="harga" label="Harga (Rp)" tipe="number" wajib min="0" max="4294967295" step="1" value="{{ old('harga', $jasa?->harga) }}" />
        <x-select nama="satuan" label="Satuan" wajib>
            @foreach (['paket' => 'paket', 'm2' => 'm2 (meter persegi)', 'kunjungan' => 'kunjungan'] as $kode => $label)
                <option value="{{ $kode }}" @selected(old('satuan', $jasa?->satuan) === $kode)>{{ $label }}</option>
            @endforeach
        </x-select>
    </div>

    <div>
        <label for="foto" class="block text-sm font-medium text-gray-700">Foto layanan</label>
        @if ($jasa?->foto)
            <div class="mt-2 h-32 w-32 overflow-hidden rounded-lg border border-gray-200">
                <x-gambar-jasa :foto="$jasa->foto" :nama="$jasa->nama" />
            </div>
        @endif
        <input id="foto" name="foto" type="file" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
            class="mt-1 block w-full rounded-lg border border-gray-300 bg-white text-sm text-gray-700 file:mr-4 file:border-0 file:bg-brand-50 file:px-4 file:py-2.5 file:font-medium file:text-brand-800">
        <p class="mt-1 text-xs text-gray-500">Opsional. Format JPG, PNG, atau WebP; maksimal 2 MB. Foto lama tetap digunakan jika tidak memilih foto baru.</p>
        @error('foto') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
        @if ($jasa?->foto)
            <label class="mt-3 flex items-center gap-2 text-sm text-gray-700">
                <input type="checkbox" name="hapus_foto" value="1" @checked(old('hapus_foto'))
                    class="rounded border-gray-300 text-brand-700 focus:ring-brand-600">
                Hapus foto saat ini
            </label>
            @error('hapus_foto') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
        @endif
    </div>

    <div class="flex flex-wrap justify-end gap-3 border-t border-gray-100 pt-5">
        <x-button varian="outline" href="{{ route('admin.jasa.index') }}">Batal</x-button>
        <x-button>{{ $jasa ? 'Simpan Perubahan' : 'Simpan Jasa' }}</x-button>
    </div>
</form>
