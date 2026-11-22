<?php

namespace App\Http\Controllers;

use App\Models\Produk;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class FavoritController extends Controller
{
    public function index(): View
    {
        $produk = Auth::user()->favoritProduk()
            ->with('kategori')
            ->orderBy('produk_favorit.created_at', 'desc')
            ->orderBy('produk.id', 'desc')
            ->paginate(12);

        return view('favorit.index', [
            'produk' => $produk,
            'favoritProdukIds' => $produk->getCollection()->modelKeys(),
        ]);
    }

    public function store(Produk $produk): RedirectResponse
    {
        Auth::user()->favoritProduk()->syncWithoutDetaching([$produk->getKey()]);

        return back()->with('status', 'Produk berhasil ditambahkan ke favorit.');
    }

    public function destroy(Produk $produk): RedirectResponse
    {
        Auth::user()->favoritProduk()->detach($produk->getKey());

        return back()->with('status', 'Produk dihapus dari favorit.');
    }
}
