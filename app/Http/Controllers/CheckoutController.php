<?php

namespace App\Http\Controllers;

use App\Exceptions\StokTidakCukup;
use App\Models\DetailPesananProduk;
use App\Models\PesananProduk;
use App\Models\Produk;
use App\Services\MidtransService;
use App\Services\PembayaranService;
use App\Services\ReservasiStokService;
use App\Support\CatatanPesanan;
use App\Support\PemberitahuanPesanan;
use App\Support\Toko;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
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

        $ongkir = Toko::ongkir();

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
        ], [
            'alamat_pengiriman.required' => 'Alamat pengiriman wajib diisi.',
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

        $ongkir = Toko::ongkir();

        // Satu record pesanan (status unpaid) + Snap token; stok fisik dipotong setelah
        // Midtrans sukses, tetapi stok sudah direservasi di sini agar tidak overselling.
        try {
            $pesanan = DB::transaction(function () use ($customer, $data, $baris, $subtotal, $ongkir) {
                $punyaStatusPesanan = Schema::hasColumn('pesanan_produk', 'status_pesanan');

                $pesanan = new PesananProduk([
                    'kode'              => 'PRD-' . now()->format('ymd') . '-' . strtoupper(Str::random(4)),
                    'customer_id'       => $customer->id,
                    'tanggal_pesan'     => now()->toDateString(),
                    'alamat_pengiriman' => $data['alamat_pengiriman'],
                ]);
                $pesanan->invoice_number = $pesanan->buatInvoiceNumber();
                $pesanan->ongkir         = $ongkir;
                $pesanan->total_bayar    = $subtotal + $ongkir;
                $pesanan->status_pembayaran = PesananProduk::STATUS_UNPAID;

                if ($punyaStatusPesanan) {
                    $pesanan->status_pesanan = 'menunggu_pembayaran';
                }

                $pesanan->save();

                foreach ($baris as $b) {
                    DetailPesananProduk::create([
                        'pesanan_produk_id' => $pesanan->id,
                        'produk_id'         => $b['produk']->id,
                        'qty'               => $b['qty'],
                        'harga'             => $b['produk']->harga,   // harga saat transaksi
                        'subtotal'          => $b['subtotal'],
                    ]);
                }

                // Tahan stok selama menunggu pembayaran. Bila stok sudah diambil
                // pembeli lain, transaksi di-rollback dan keranjang tidak dikosongkan.
                app(ReservasiStokService::class)->pesan($pesanan, $baris);

                return $pesanan;
            });
        } catch (StokTidakCukup $e) {
            return redirect()->route('keranjang.index')->with('error', $e->getMessage());
        }

        // Order sudah sah tersimpan: kosongkan keranjang sekarang juga.
        // Kalau pembuatan token gagal, user tetap hanya punya SATU order (bayar lewat "Coba Bayar Ulang").
        session()->forget('cart');

        CatatanPesanan::catat($pesanan, 'dibuat', 'Pesanan dibuat, menunggu pembayaran.');
        PemberitahuanPesanan::kirim($pesanan, 'dibuat', 'produk');

        try {
            $orderId = $pesanan->invoice_number;   // order_id awal = invoice_number
            $token   = app(MidtransService::class)->snapToken(
                $orderId,
                (int) $pesanan->total_bayar,
                MidtransService::itemDetails(
                    collect($baris)->map(fn ($b) => [
                        'id'    => 'produk-' . $b['produk']->id,
                        'nama'  => $b['produk']->nama,
                        'harga' => $b['produk']->harga,
                        'qty'   => $b['qty'],
                    ]),
                    $ongkir
                ),
                [
                    'nama'  => Auth::user()->nama,
                    'email' => Auth::user()->email,
                    'no_hp' => Auth::user()->no_hp,
                ]
            );

            // Simpan token + order_id Midtrans (dipakai untuk cek status/webhook).
            app(PembayaranService::class)->simpanToken($pesanan, $orderId, $token);
        } catch (\Throwable $e) {
            // Pesanan tetap tersimpan (unpaid, token kosong); user bisa memakai "Coba Bayar Ulang".
            report($e);

            return redirect()->route('payment.show', ['tipe' => 'produk', 'pesanan' => $pesanan->id])
                ->with('error', $this->pesanError($e));
        }

        return redirect()->route('payment.show', ['tipe' => 'produk', 'pesanan' => $pesanan->id])
            ->with('status', 'Pesanan berhasil dibuat. Selesaikan pembayaran pada jendela yang terbuka.');
    }

    private function pesanError(\Throwable $e): string
    {
        return $e instanceof \RuntimeException
            ? $e->getMessage()
            : 'Gagal membuat token pembayaran Midtrans. Silakan coba "Coba Bayar Ulang".';
    }
}
