<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DetailPesananProduk;
use App\Models\Kategori;
use App\Models\Produk;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProdukController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()?->isAdmin(), 403);

        return view('admin.produk.index', [
            'produk' => Produk::query()
                ->with('kategori')
                ->orderByDesc('id')
                ->paginate(15),
        ]);
    }

    public function create(Request $request): View
    {
        abort_unless($request->user()?->isAdmin(), 403);

        return view('admin.produk.create', [
            'produk' => null,
            'kategori' => Kategori::query()
                ->where('tipe', 'produk')
                ->orderBy('nama')
                ->get(),
        ]);
    }

    public function edit(Request $request, Produk $produk): View
    {
        abort_unless($request->user()?->isAdmin(), 403);

        return view('admin.produk.create', [
            'produk' => $produk,
            'kategori' => Kategori::query()
                ->where('tipe', 'produk')
                ->orderBy('nama')
                ->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->isAdmin(), 403);

        $data = $this->validatedProductData($request);
        $data['foto'] = $this->storePhoto($request);

        Produk::create($data);

        return redirect()
            ->route('admin.produk.index')
            ->with('status', 'Produk berhasil ditambahkan.');
    }

    public function update(Request $request, Produk $produk): RedirectResponse
    {
        abort_unless($request->user()?->isAdmin(), 403);

        $data = $this->validatedProductData($request, true);
        $newPhoto = $this->storePhoto($request);
        $oldPhoto = $produk->foto;

        $data['foto'] = $newPhoto
            ?? ($request->boolean('hapus_foto') ? null : $oldPhoto);

        $produk->update($data);

        if ($newPhoto !== null || $request->boolean('hapus_foto')) {
            $this->deletePhoto($oldPhoto);
        }

        return redirect()
            ->route('admin.produk.index')
            ->with('status', 'Produk berhasil diperbarui.');
    }

    public function destroy(Request $request, Produk $produk): RedirectResponse
    {
        abort_unless($request->user()?->isAdmin(), 403);

        if (DetailPesananProduk::query()->where('produk_id', $produk->id)->exists()) {
            return redirect()
                ->route('admin.produk.index')
                ->with('error', 'Produk tidak dapat dihapus karena sudah tercatat dalam pesanan.');
        }

        $foto = $produk->foto;
        $produk->delete();
        $this->deletePhoto($foto);

        return redirect()
            ->route('admin.produk.index')
            ->with('status', 'Produk berhasil dihapus.');
    }

    private function validatedProductData(Request $request, bool $updating = false): array
    {
        $rules = [
            'nama' => ['required', 'string', 'max:100'],
            'kategori_id' => [
                'nullable',
                'integer',
                Rule::exists('kategori', 'id')->where('tipe', 'produk'),
            ],
            'deskripsi' => ['nullable', 'string', 'max:5000'],
            'harga' => ['required', 'integer', 'min:0', 'max:4294967295'],
            'stok' => ['required', 'integer', 'min:0', 'max:4294967295'],
            'foto' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];

        if ($updating) {
            $rules['hapus_foto'] = ['nullable', 'boolean'];
        }

        return $request->validate($rules, [
            'kategori_id.exists' => 'Kategori yang dipilih bukan kategori produk.',
            'foto.image' => 'File foto harus berupa gambar.',
            'foto.mimes' => 'Foto harus berformat JPG, PNG, atau WebP.',
            'foto.max' => 'Ukuran foto maksimal 2 MB.',
        ]);
    }

    private function storePhoto(Request $request): ?string
    {
        if (! $request->hasFile('foto')) {
            return null;
        }

        $directory = public_path('images/produk');
        File::ensureDirectoryExists($directory);

        $image = $request->file('foto');
        $photo = $image->hashName();
        $image->move($directory, $photo);

        return $photo;
    }

    private function deletePhoto(?string $photo): void
    {
        if ($photo === null || basename($photo) !== $photo) {
            return;
        }

        $path = public_path('images/produk/'.$photo);

        if (File::isFile($path)) {
            File::delete($path);
        }
    }
}
