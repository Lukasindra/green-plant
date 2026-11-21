<?php

namespace App\Http\Controllers;

use App\Models\Kategori;
use App\Models\Produk;
use Illuminate\Http\Request;

class ProdukController extends Controller
{
    public function index(Request $request)
    {
        // Semua input dari URL divalidasi dulu
        $data = $request->validate([
            'q'        => ['nullable', 'string', 'max:100'],
            'kategori' => ['nullable', 'integer'],
            'maks'     => ['nullable', 'integer', 'min:0', 'max:100000000'],
            'urut'     => ['nullable', 'in:terbaru,termurah,termahal'],
        ]);

        $kategoriAktif = $data['kategori'] ?? null;
        $maks          = $data['maks'] ?? 1000000;
        $urut          = $data['urut'] ?? 'terbaru';
        $q             = trim((string) ($data['q'] ?? ''));

        $query = Produk::with('kategori')->where('harga', '<=', $maks);

        if ($q !== '') {
            $query->where(function ($cari) use ($q) {
                $cari->where('nama', 'like', "%{$q}%")
                    ->orWhere('deskripsi', 'like', "%{$q}%");
            });
        }

        if ($kategoriAktif) {
            $query->where('kategori_id', $kategoriAktif);
        }

        match ($urut) {
            'termurah' => $query->orderBy('harga'),
            'termahal' => $query->orderByDesc('harga'),
            default    => $query->orderByDesc('id'),   // tabel produk tidak punya tanggal, jadi "terbaru" = id terbesar
        };

        return view('produk.index', [
            'produk'        => $query->paginate(12)->withQueryString(),
            'kategori'      => Kategori::where('tipe', 'produk')->orderBy('nama')->get(),
            'kategoriAktif' => $kategoriAktif,
            'maks'          => $maks,
            'urut'          => $urut,
            'q'             => $q,
        ]);
    }

    public function show(Produk $produk)
    {
        return view('produk.show', [
            'produk'  => $produk->load('kategori'),
            'terkait' => Produk::with('kategori')
                ->where('kategori_id', $produk->kategori_id)
                ->whereKeyNot($produk->id)
                ->orderByDesc('id')
                ->limit(4)
                ->get(),
        ]);
    }
}
