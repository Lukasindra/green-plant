<?php

namespace App\Http\Controllers;

use App\Models\PesananJasa;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

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

    // Customer membatalkan pesanannya sendiri selama pembayaran belum dikonfirmasi Midtrans.
    public function batalkan(PesananJasa $pesanan)
    {
        Gate::authorize('batalkan', $pesanan);

        $pesanan->status_pesanan = 'dibatalkan';
        $pesanan->save();

        return redirect()
            ->route('pesanan-jasa.show', $pesanan)
            ->with('status', "Pesanan {$pesanan->kode} berhasil dibatalkan.");
    }
}
