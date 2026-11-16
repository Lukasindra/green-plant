<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Kategori;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class KategoriController extends Controller
{
    private const TIPE = ['produk', 'jasa'];

    public function index(Request $request): View
    {
        $tipe = in_array($request->query('tipe'), self::TIPE, true) ? $request->query('tipe') : null;

        $kategori = Kategori::query()
            ->withCount(['produk', 'jasa'])
            ->when($tipe, fn ($query) => $query->where('tipe', $tipe))
            ->orderBy('tipe')
            ->orderBy('nama')
            ->paginate(20)
            ->withQueryString();

        return view('admin.kategori.index', [
            'kategori' => $kategori,
            'tipe' => $tipe,
        ]);
    }

    public function create(): View
    {
        return view('admin.kategori.form', [
            'kategori' => null,
            'daftarTipe' => self::TIPE,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'tipe' => ['required', Rule::in(self::TIPE)],
            'nama' => [
                'required',
                'string',
                'max:100',
                // Nama boleh sama asalkan tipenya berbeda (misalnya "Perawatan" untuk produk dan jasa)
                Rule::unique('kategori', 'nama')->where('tipe', $request->input('tipe')),
            ],
        ], [
            'nama.unique' => 'Kategori dengan nama ini sudah ada untuk tipe yang sama.',
        ]);

        Kategori::create($data);

        return redirect()
            ->route('admin.kategori.index', ['tipe' => $data['tipe']])
            ->with('status', 'Kategori berhasil ditambahkan.');
    }

    public function edit(Kategori $kategori): View
    {
        return view('admin.kategori.form', [
            'kategori' => $kategori,
            'daftarTipe' => self::TIPE,
        ]);
    }

    // Tipe sengaja tidak bisa diubah: produk atau jasa yang sudah memakainya akan ikut salah kategori.
    public function update(Request $request, Kategori $kategori): RedirectResponse
    {
        $data = $request->validate([
            'nama' => [
                'required',
                'string',
                'max:100',
                Rule::unique('kategori', 'nama')->where('tipe', $kategori->tipe)->ignore($kategori->id),
            ],
        ], [
            'nama.unique' => 'Kategori dengan nama ini sudah ada untuk tipe yang sama.',
        ]);

        $kategori->update($data);

        return redirect()
            ->route('admin.kategori.index', ['tipe' => $kategori->tipe])
            ->with('status', 'Kategori berhasil diperbarui.');
    }

    public function destroy(Kategori $kategori): RedirectResponse
    {
        $dipakai = $kategori->produk()->count() + $kategori->jasa()->count();

        if ($dipakai > 0) {
            return redirect()
                ->route('admin.kategori.index', ['tipe' => $kategori->tipe])
                ->with('error', "Kategori tidak dapat dihapus karena masih dipakai oleh {$dipakai} {$kategori->tipe}.");
        }

        $kategori->delete();

        return redirect()
            ->route('admin.kategori.index', ['tipe' => $kategori->tipe])
            ->with('status', 'Kategori berhasil dihapus.');
    }
}
