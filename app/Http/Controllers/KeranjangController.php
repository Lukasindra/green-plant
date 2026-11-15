<?php

namespace App\Http\Controllers;

use App\Models\Produk;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class KeranjangController extends Controller
{
    // Keranjang disimpan di session: [produk_id => jumlah]
<<<<<<< HEAD
    public function index(): View
    {
        $cart = session('cart', []);
        $cart = is_array($cart) ? $cart : [];
        $cart = collect($cart)
            ->filter(fn ($quantity, $productId) => filter_var($productId, FILTER_VALIDATE_INT) !== false && is_numeric($quantity) && (int) $quantity > 0)
            ->map(fn ($quantity) => (int) $quantity)
            ->all();

        $products = Produk::with('kategori')
            ->whereKey(array_keys($cart))
            ->get()
            ->keyBy('id');

        $cartItems = $products->map(function (Produk $produk) use ($cart): array {
            $quantity = $cart[$produk->id];

            return [
                'produk' => $produk,
                'jumlah' => $quantity,
                'subtotal' => $produk->harga * $quantity,
            ];
        })->values();

        $cart = $cartItems->mapWithKeys(fn (array $item) => [$item['produk']->id => $item['jumlah']])->all();
        session(['cart' => $cart]);

        return view('keranjang.index', [
            'cartItems' => $cartItems,
            'jumlahProduk' => $cartItems->sum('jumlah'),
            'subtotal' => $cartItems->sum('subtotal'),
        ]);
    }

    public function tambah(Request $request): RedirectResponse
=======

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
>>>>>>> 4561d79cb7f3dba4f34a1bbf2a7638dff7d6e969
    {
        $data = $request->validate([
            'produk_id' => ['required', 'integer', 'exists:produk,id'],
        ]);

        $produk = Produk::findOrFail($data['produk_id']);
        $cart = session('cart', []);
        $sudah = $cart[$produk->id] ?? 0;

        if ($sudah + 1 > $produk->stok) {
            return back()->with('error', "Stok {$produk->nama} tidak mencukupi.");
        }

        $cart[$produk->id] = $sudah + 1;
        session(['cart' => $cart]);

        return back()->with('status', "{$produk->nama} ditambahkan ke keranjang.");
    }

<<<<<<< HEAD
    public function update(Request $request, Produk $produk): RedirectResponse
    {
        $data = $request->validate([
            'jumlah' => ['required', 'integer', 'min:1'],
        ]);

        $cart = session('cart', []);

        if (! is_array($cart) || ! array_key_exists($produk->id, $cart)) {
            return back()->with('error', 'Produk tidak ditemukan di keranjang.');
        }

        if ($data['jumlah'] > $produk->stok) {
            return back()->with('error', "Jumlah {$produk->nama} melebihi stok yang tersedia.");
        }

        $cart[$produk->id] = $data['jumlah'];
        session(['cart' => $cart]);

        return back()->with('status', 'Jumlah produk di keranjang berhasil diperbarui.');
    }

    public function destroy(Produk $produk): RedirectResponse
    {
        $cart = session('cart', []);

        if (is_array($cart)) {
            unset($cart[$produk->id]);
            session(['cart' => $cart]);
        }

        return back()->with('status', "{$produk->nama} dihapus dari keranjang.");
    }

    public function clear(): RedirectResponse
    {
        session()->forget('cart');

        return back()->with('status', 'Keranjang berhasil dikosongkan.');
    }
=======
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
>>>>>>> 4561d79cb7f3dba4f34a1bbf2a7638dff7d6e969
}
