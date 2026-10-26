<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DetailPesananJasa;
use App\Models\Jasa;
use App\Models\Kategori;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class JasaController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()?->isAdmin(), 403);

        return view('admin.jasa.index', [
            'jasa' => Jasa::query()
                ->with('kategori')
                ->orderByDesc('id')
                ->paginate(15),
        ]);
    }

    public function create(Request $request): View
    {
        abort_unless($request->user()?->isAdmin(), 403);

        return view('admin.jasa.create', [
            'jasa' => null,
            'kategori' => Kategori::query()
                ->where('tipe', 'jasa')
                ->orderBy('nama')
                ->get(),
        ]);
    }

    public function edit(Request $request, Jasa $jasa): View
    {
        abort_unless($request->user()?->isAdmin(), 403);

        return view('admin.jasa.create', [
            'jasa' => $jasa,
            'kategori' => Kategori::query()
                ->where('tipe', 'jasa')
                ->orderBy('nama')
                ->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->isAdmin(), 403);

        $data = $this->validatedServiceData($request);
        $data['foto'] = $this->storePhoto($request);

        Jasa::create($data);

        return redirect()
            ->route('admin.jasa.index')
            ->with('status', 'Jasa berhasil ditambahkan.');
    }

    public function update(Request $request, Jasa $jasa): RedirectResponse
    {
        abort_unless($request->user()?->isAdmin(), 403);

        $data = $this->validatedServiceData($request, true);
        $newPhoto = $this->storePhoto($request);
        $oldPhoto = $jasa->foto;

        $data['foto'] = $newPhoto
            ?? ($request->boolean('hapus_foto') ? null : $oldPhoto);

        $jasa->update($data);

        if ($newPhoto !== null || $request->boolean('hapus_foto')) {
            $this->deletePhoto($oldPhoto);
        }

        return redirect()
            ->route('admin.jasa.index')
            ->with('status', 'Jasa berhasil diperbarui.');
    }

    public function destroy(Request $request, Jasa $jasa): RedirectResponse
    {
        abort_unless($request->user()?->isAdmin(), 403);

        if (DetailPesananJasa::query()->where('jasa_id', $jasa->id)->exists()) {
            return redirect()
                ->route('admin.jasa.index')
                ->with('error', 'Jasa tidak dapat dihapus karena sudah tercatat dalam pesanan.');
        }

        $foto = $jasa->foto;
        $jasa->delete();
        $this->deletePhoto($foto);

        return redirect()
            ->route('admin.jasa.index')
            ->with('status', 'Jasa berhasil dihapus.');
    }

    private function validatedServiceData(Request $request, bool $updating = false): array
    {
        $rules = [
            'nama' => ['required', 'string', 'max:100'],
            'kategori_id' => [
                'nullable',
                'integer',
                Rule::exists('kategori', 'id')->where('tipe', 'jasa'),
            ],
            'deskripsi' => ['nullable', 'string', 'max:5000'],
            'harga' => ['required', 'integer', 'min:0', 'max:4294967295'],
            'satuan' => ['required', 'string', 'max:30'],
            'foto' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];

        if ($updating) {
            $rules['hapus_foto'] = ['nullable', 'boolean'];
        }

        return $request->validate($rules, [
            'kategori_id.exists' => 'Kategori yang dipilih bukan kategori jasa.',
            'satuan.required' => 'Satuan wajib diisi, misalnya "per kunjungan".',
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

        $directory = public_path('images/jasa');
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

        $path = public_path('images/jasa/'.$photo);

        if (File::isFile($path)) {
            File::delete($path);
        }
    }
}
