<?php

namespace App\Http\Controllers;

use App\Models\PesananJasa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class PesananJasaController extends Controller
{
    public function index()
    {
        $customer = Auth::user()->customer;
        abort_if(! $customer, 403, 'Halaman ini hanya untuk akun customer.');

        $pesanan = PesananJasa::where('customer_id', $customer->id)
            ->orderByDesc('id')
            ->paginate(10);

        return view('pesanan-jasa.index', compact('pesanan'));
    }

    public function show(PesananJasa $pesanan)
    {
        Gate::authorize('view', $pesanan);

        $pesanan->load('detail.jasa');

        return view('pesanan-jasa.show', compact('pesanan'));
    }

    public function uploadBukti(Request $request, PesananJasa $pesanan)
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
        $pesanan->tanggal_bayar     = now()->toDateString();
        $pesanan->save();

        return back()->with('status', 'Bukti pembayaran terkirim. Admin akan memverifikasinya.');
    }

    // Menampilkan bukti bayar: hanya pemilik pesanan dan admin
    public function bukti(PesananJasa $pesanan)
    {
        Gate::authorize('view', $pesanan);

        abort_if(! $pesanan->bukti_bayar || ! Storage::exists($pesanan->bukti_bayar), 404);

        return Storage::response($pesanan->bukti_bayar);
    }
}
