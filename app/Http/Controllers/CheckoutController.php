<?php

namespace App\Http\Controllers;

use App\Models\DetailPesananProduk;
use App\Models\PesananProduk;
use App\Models\Produk;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CheckoutController extends Controller
{
    // Mengubah isi keranjang (session) menjadi baris + subtotal. Harga SELALU dari database.
    private function hitungKeranjang(): array
    {
        $cart   = session('cart', []);
        $produk = Produk::whereIn('id', array_keys($cart))->get()->keyBy('id');

        $baris    = [];
        $subtotal = 0;

        foreach ($cart as $id => $qty) {
            $p = $produk->get($id);
            if (! $p) {
                continue;
            }
            $qty      = (int) $qty;
            $sub      = $p->harga * $qty;
            $baris[]  = ['produk' => $p, 'qty' => $qty, 'subtotal' => $sub];
            $subtotal += $sub;
        }

        return [$baris, $subtotal];
    }

    private function customerAtauTolak()
    {
        $customer = Auth::user()->customer;
        abort_if(! $customer, 403, 'Checkout hanya untuk akun customer.');

        return $customer;
    }

    public function index()
    {
        $customer = $this->customerAtauTolak();
        [$baris, $subtotal] = $this->hitungKeranjang();

        if (empty($baris)) {
            return redirect()->route('keranjang.index')->with('error', 'Keranjang Anda masih kosong.');
        }

        $ongkir = (int) config('toko.ongkir');

        return view('checkout.index', [
            'baris'    => $baris,
            'subtotal' => $subtotal,
            'ongkir'   => $ongkir,
            'total'    => $subtotal + $ongkir,
            'alamat'   => $customer->alamat,
        ]);
    }

    public function store(Request $request)
    {
        $customer = $this->customerAtauTolak();

        $data = $request->validate([
            'alamat_pengiriman' => ['required', 'string', 'max:500'],
            'metode_pembayaran' => ['required', 'in:transfer,qris,ewallet,cod'],
        ], [
            'alamat_pengiriman.required' => 'Alamat pengiriman wajib diisi.',
            'metode_pembayaran.required' => 'Pilih metode pembayaran.',
        ]);

        [$baris, $subtotal] = $this->hitungKeranjang();

        if (empty($baris)) {
            return redirect()->route('keranjang.index')->with('error', 'Keranjang Anda masih kosong.');
        }

        // Cek stok terakhir sebelum pesanan dibuat
        foreach ($baris as $b) {
            if ($b['produk']->stok < $b['qty']) {
                return redirect()->route('keranjang.index')
                    ->with('error', "Stok {$b['produk']->nama} tidak mencukupi.");
            }
        }

        $ongkir = (int) config('toko.ongkir');

        $pesanan = DB::transaction(function () use ($customer, $data, $baris, $subtotal, $ongkir) {
            $pesanan = new PesananProduk([
                'kode'              => 'PRD-' . now()->format('ymd') . '-' . strtoupper(Str::random(4)),
                'customer_id'       => $customer->id,
                'tanggal_pesan'     => now()->toDateString(),
                'metode_pembayaran' => $data['metode_pembayaran'],
                'alamat_pengiriman' => $data['alamat_pengiriman'],
            ]);
            $pesanan->ongkir      = $ongkir;
            $pesanan->total_bayar = $subtotal + $ongkir;
            $pesanan->save();   // status_pembayaran dan status_pesanan memakai nilai bawaan database

            foreach ($baris as $b) {
                DetailPesananProduk::create([
                    'pesanan_produk_id' => $pesanan->id,
                    'produk_id'         => $b['produk']->id,
                    'qty'               => $b['qty'],
                    'harga'             => $b['produk']->harga,   // harga saat transaksi
                    'subtotal'          => $b['subtotal'],
                ]);
            }

            return $pesanan;
        });

        // Stok dikurangi nanti, saat admin mengonfirmasi pembayaran (bukan saat pesanan dibuat)
        session()->forget('cart');

        return redirect()->route('pesanan.show', $pesanan)
            ->with('status', 'Pesanan berhasil dibuat. Silakan selesaikan pembayaran.');
    }
}
