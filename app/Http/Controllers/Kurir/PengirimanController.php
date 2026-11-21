<?php

namespace App\Http\Controllers\Kurir;

use App\Http\Controllers\Controller;
use App\Models\Karyawan;
use App\Models\PesananProduk;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class PengirimanController extends Controller
{
    // Daftar pengantaran milik kurir yang login (kurir_id = karyawan kurir tersebut).
    public function index(Request $request): View
    {
        $kurir = $this->kurirAtauTolak($request);

        $status = in_array($request->query('status'), ['dikirim', 'selesai'], true)
            ? $request->query('status')
            : 'dikirim';

        $pesanan = PesananProduk::query()
            ->with(['customer.akun', 'detail.produk'])
            ->where('kurir_id', $kurir->id)
            ->where('status_pesanan', $status)
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return view('kurir.index', [
            'pesanan' => $pesanan,
            'status'  => $status,
        ]);
    }

    public function show(Request $request, PesananProduk $pesanan): View
    {
        $this->kurirAtauTolak($request);
        Gate::authorize('antar', $pesanan);

        $pesanan->load(['customer.akun', 'detail.produk']);

        return view('kurir.show', ['pesanan' => $pesanan]);
    }

    // Kurir hanya menandai pesanan miliknya selesai diantar. Stok dan pembayaran tidak disentuh.
    public function selesai(Request $request, PesananProduk $pesanan): RedirectResponse
    {
        $this->kurirAtauTolak($request);
        Gate::authorize('antar', $pesanan);

        if ($pesanan->status_pesanan !== 'dikirim') {
            return redirect()
                ->route('kurir.show', $pesanan)
                ->with('error', 'Pesanan ini tidak sedang dalam pengantaran.');
        }

        $pesanan->status_pesanan = 'selesai';

        if (Schema::hasColumn($pesanan->getTable(), 'tanggal_selesai')) {
            $pesanan->tanggal_selesai = now();
        }

        $pesanan->save();

        return redirect()
            ->route('kurir.index')
            ->with('status', "Pesanan {$pesanan->kode} ditandai sudah diterima customer.");
    }

    private function kurirAtauTolak(Request $request): Karyawan
    {
        $kurir = $request->user()->karyawan;
        abort_if(! $kurir, 403, 'Akun kurir belum terdaftar sebagai karyawan.');

        return $kurir;
    }
}
