<form method="POST" action="{{ $produk ? route('admin.produk.update', $produk) : route('admin.produk.store') }}" enctype="multipart/form-data" class="space-y-5 rounded-xl border border-gray-200 bg-white p-5 shadow-sm sm:p-7">
    @csrf
    @if ($produk)
        @method('PUT')
    @endif

    <div>
        <label for="nama" class="block text-sm font-medium text-gray-700">Nama barang <span class="text-red-600">*</span></label>
        <input id="nama" name="nama" type="text" value="{{ old('nama', $produk?->nama) }}" maxlength="100" required autofocus
            class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-brand-600 focus:outline-none focus:ring-1 focus:ring-brand-600">
        @error('nama') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="kategori_id" class="block text-sm font-medium text-gray-700">Kategori</label>
        <select id="kategori_id" name="kategori_id"
            class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm focus:border-brand-600 focus:outline-none focus:ring-1 focus:ring-brand-600">
            <option value="">Tanpa kategori</option>
            @foreach ($kategori as $item)
                <option value="{{ $item->id }}" @selected(old('kategori_id', $produk?->kategori_id) == $item->id)>{{ $item->nama }}</option>
            @endforeach
        </select>
        @error('kategori_id') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="deskripsi" class="block text-sm font-medium text-gray-700">Deskripsi</label>
        <textarea id="deskripsi" name="deskripsi" rows="4" maxlength="5000"
            class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-brand-600 focus:outline-none focus:ring-1 focus:ring-brand-600">{{ old('deskripsi', $produk?->deskripsi) }}</textarea>
        @error('deskripsi') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
    </div>

    <div class="grid gap-5 sm:grid-cols-2">
        <div>
            <label for="harga" class="block text-sm font-medium text-gray-700">Harga (Rp) <span class="text-red-600">*</span></label>
            <input id="harga" name="harga" type="number" value="{{ old('harga', $produk?->harga) }}" min="0" max="4294967295" step="1" required
                class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-brand-600 focus:outline-none focus:ring-1 focus:ring-brand-600">
            @error('harga') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="stok" class="block text-sm font-medium text-gray-700">Stok <span class="text-red-600">*</span></label>
            <input id="stok" name="stok" type="number" value="{{ old('stok', $produk?->stok) }}" min="0" max="4294967295" step="1" required
                class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-brand-600 focus:outline-none focus:ring-1 focus:ring-brand-600">
            @error('stok') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
        </div>
    </div>

    <div>
        <label for="foto" class="block text-sm font-medium text-gray-700">Foto barang</label>
        @if ($produk?->foto)
            <div class="mt-2 h-32 w-32 overflow-hidden rounded-lg border border-gray-200">
                <x-gambar-produk :foto="$produk->foto" :nama="$produk->nama" />
            </div>
        @endif
        <input id="foto" name="foto" type="file" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
            class="mt-1 block w-full rounded-lg border border-gray-300 bg-white text-sm text-gray-700 file:mr-4 file:border-0 file:bg-brand-50 file:px-4 file:py-2.5 file:font-medium file:text-brand-800">
        <p class="mt-1 text-xs text-gray-500">Opsional. Format JPG, PNG, atau WebP; maksimal 2 MB. Foto lama tetap digunakan jika tidak memilih foto baru.</p>
        @error('foto') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
        @if ($produk?->foto)
            <label class="mt-3 flex items-center gap-2 text-sm text-gray-700">
                <input type="checkbox" name="hapus_foto" value="1" @checked(old('hapus_foto'))
                    class="rounded border-gray-300 text-brand-700 focus:ring-brand-600">
                Hapus foto saat ini
            </label>
            @error('hapus_foto') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
        @endif
    </div>

    <div class="flex flex-wrap justify-end gap-3 border-t border-gray-100 pt-5">
        <a href="{{ route('admin.produk.index') }}" class="rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-50">Batal</a>
        <button type="submit" class="rounded-lg bg-brand-700 px-5 py-2.5 text-sm font-semibold text-white hover:bg-brand-800">{{ $produk ? 'Simpan Perubahan' : 'Simpan Produk' }}</button>
    </div>
</form>
