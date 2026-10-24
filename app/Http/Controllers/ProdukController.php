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
            'kategori' => ['nullable', 'integer'],
            'maks'     => ['nullable', 'integer', 'min:0', 'max:100000000'],
            'urut'     => ['nullable', 'in:terbaru,termurah,termahal'],
        ]);

        $kategoriAktif = $data['kategori'] ?? null;
        $maks          = $data['maks'] ?? 1000000;
        $urut          = $data['urut'] ?? 'terbaru';

        $query = Produk::with('kategori')->where('harga', '<=', $maks);

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
        ]);
    }
}
