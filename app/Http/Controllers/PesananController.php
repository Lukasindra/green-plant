<?php

namespace App\Http\Controllers;

use App\Models\PesananProduk;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

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

        return view('pesanan.show', compact('pesanan'));
    }

    public function uploadBukti(Request $request, PesananProduk $pesanan)
    {
        Gate::authorize('uploadBukti', $pesanan);

        $request->validate([
            // mimes memeriksa isi file, bukan sekadar ekstensi
            'bukti_bayar' => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
        ], [
            'bukti_bayar.required' => 'Pilih file bukti pembayaran.',
            'bukti_bayar.image'    => 'File harus berupa gambar.',
            'bukti_bayar.mimes'    => 'Format yang diizinkan: JPG atau PNG.',
            'bukti_bayar.max'      => 'Ukuran file maksimal 2 MB.',
        ]);

        // Disimpan di disk privat (tidak bisa dibuka lewat URL langsung), dengan nama acak
        $path = $request->file('bukti_bayar')->store('bukti-bayar');

        if ($pesanan->bukti_bayar && Storage::exists($pesanan->bukti_bayar)) {
            Storage::delete($pesanan->bukti_bayar);
        }

        $pesanan->bukti_bayar       = $path;
        $pesanan->status_pembayaran = 'menunggu_verifikasi';
        $pesanan->save();

        return back()->with('status', 'Bukti pembayaran terkirim. Admin akan memverifikasinya.');
    }

    // Menampilkan bukti bayar: hanya pemilik pesanan dan admin
    public function bukti(PesananProduk $pesanan)
    {
        Gate::authorize('view', $pesanan);

        abort_if(! $pesanan->bukti_bayar || ! Storage::exists($pesanan->bukti_bayar), 404);

        return Storage::response($pesanan->bukti_bayar);
    }
}
