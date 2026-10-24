<?php

namespace App\Http\Controllers;

use App\Models\Produk;
use Illuminate\Http\Request;

class KeranjangController extends Controller
{
    // Keranjang disimpan di session: [produk_id => jumlah]
    public function tambah(Request $request)
    {
        $data = $request->validate([
            'produk_id' => ['required', 'integer', 'exists:produk,id'],
        ]);

        $produk = Produk::findOrFail($data['produk_id']);
        $cart   = session('cart', []);
        $sudah  = $cart[$produk->id] ?? 0;

        if ($sudah + 1 > $produk->stok) {
            return back()->with('error', "Stok {$produk->nama} tidak mencukupi.");
        }

        $cart[$produk->id] = $sudah + 1;
        session(['cart' => $cart]);

        return back()->with('status', "{$produk->nama} ditambahkan ke keranjang.");
    }
}
