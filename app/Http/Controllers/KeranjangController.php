<?php

namespace App\Http\Controllers;

use App\Models\Produk;
use Illuminate\Http\Request;

class KeranjangController extends Controller
{
    // Keranjang disimpan di session: [produk_id => jumlah]

    public function index()
    {
        $cart   = session('cart', []);
        $produk = Produk::with('kategori')->whereIn('id', array_keys($cart))->get()->keyBy('id');

        $baris  = [];
        $bersih = [];
        $total  = 0;

        foreach ($cart as $id => $qty) {
            $p = $produk->get($id);

            // Produk sudah dihapus atau stoknya habis: buang dari keranjang
            if (! $p || $p->stok < 1) {
                continue;
            }

            $qty = min((int) $qty, $p->stok);          // jumlah tidak boleh melebihi stok
            $bersih[$id] = $qty;

            $subtotal = $p->harga * $qty;              // harga selalu dihitung dari database
            $total   += $subtotal;
            $baris[]  = ['produk' => $p, 'qty' => $qty, 'subtotal' => $subtotal];
        }

        if ($bersih !== $cart) {
            session(['cart' => $bersih]);
        }

        return view('keranjang.index', compact('baris', 'total'));
    }

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

    public function ubah(Request $request)
    {
        $data = $request->validate([
            'produk_id' => ['required', 'integer', 'exists:produk,id'],
            'aksi'      => ['required', 'in:tambah,kurang'],
        ]);

        $produk = Produk::findOrFail($data['produk_id']);
        $cart   = session('cart', []);

        if (! isset($cart[$produk->id])) {
            return back();
        }

        if ($data['aksi'] === 'tambah') {
            if ($cart[$produk->id] + 1 > $produk->stok) {
                return back()->with('error', "Stok {$produk->nama} hanya {$produk->stok}.");
            }
            $cart[$produk->id]++;
        } else {
            $cart[$produk->id] = max(1, $cart[$produk->id] - 1);
        }

        session(['cart' => $cart]);

        return back();
    }

    public function hapus(Produk $produk)
    {
        $cart = session('cart', []);
        unset($cart[$produk->id]);
        session(['cart' => $cart]);

        return back()->with('status', "{$produk->nama} dihapus dari keranjang.");
    }
}
