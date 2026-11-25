<?php

namespace App\Http\Controllers;

use App\Models\PesananProduk;
use App\Services\ReservasiStokService;
use App\Support\CatatanPesanan;
use App\Support\PemberitahuanPesanan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;

class PesananController extends Controller
{
    public function index()
    {
        $customer = Auth::user()->customer;
        abort_if(! $customer, 403, 'Halaman ini hanya untuk akun customer.');

        $pesanan = PesananProduk::where('customer_id', $customer->id)
            ->orderByDesc('id')
            ->paginate(10);

        return view('pesanan.index', compact('pesanan'));
    }

    public function show(PesananProduk $pesanan)
    {
        Gate::authorize('view', $pesanan);

        $pesanan->load('detail.produk');

        if (Schema::hasTable('riwayat_pesanan')) {
            $pesanan->load('riwayat');
        }

        return view('pesanan.show', compact('pesanan'));
    }

    public function invoice(PesananProduk $pesanan)
    {
        Gate::authorize('view', $pesanan);

        $pesanan->load('detail.produk', 'customer.akun');

        return view('invoice', [
            'pesanan' => $pesanan,
            'tipe'    => 'produk',
        ]);
    }

    // Customer membatalkan pesanannya sendiri selama pembayaran belum dikonfirmasi Midtrans.
    // Stok produk baru berkurang saat lunas, jadi tidak ada stok yang perlu dikembalikan.
    public function batalkan(PesananProduk $pesanan)
    {
        Gate::authorize('batalkan', $pesanan);

        $pesanan->status_pesanan = 'dibatalkan';
        $pesanan->save();

        // Lepaskan stok yang ditahan saat checkout agar bisa dibeli pelanggan lain.
        app(ReservasiStokService::class)->lepas($pesanan);

        CatatanPesanan::catat($pesanan, 'dibatalkan', 'Dibatalkan oleh pelanggan.');
        PemberitahuanPesanan::kirim($pesanan, 'dibatalkan', 'produk');

        return redirect()
            ->route('pesanan.show', $pesanan)
            ->with('status', "Pesanan {$pesanan->kode} berhasil dibatalkan.");
    }
}
