<?php

namespace App\Http\Controllers;

use App\Models\Jasa;
use App\Models\Kategori;
use App\Models\PesananJasa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class JasaController extends Controller
{
    public function index(Request $request)
    {
        $data = $request->validate([
            'kategori' => ['nullable', 'integer'],
        ]);

        $kategoriAktif = $data['kategori'] ?? null;

        $query = Jasa::with('kategori')->orderByDesc('id');

        if ($kategoriAktif) {
            $query->where('kategori_id', $kategoriAktif);
        }

        return view('jasa.index', [
            'jasa'          => $query->paginate(12)->withQueryString(),
            'kategori'      => Kategori::where('tipe', 'jasa')->orderBy('nama')->get(),
            'kategoriAktif' => $kategoriAktif,
        ]);
    }

    public function show(Jasa $jasa)
    {
        $customer = Auth::user()?->customer;

        return view('jasa.show', [
            'jasa'     => $jasa->load('kategori'),
            'alamat'   => $customer?->alamat,
        ]);
    }

    public function pesan(Request $request, Jasa $jasa)
    {
        $customer = Auth::user()?->customer;
        abort_if(! $customer, 403, 'Pemesanan jasa hanya untuk akun customer.');

        $data = $request->validate([
            'qty'               => ['required', 'integer', 'min:1', 'max:10'],
            'jadwal'            => ['required', 'date', 'after_or_equal:today'],
            'alamat_lokasi'     => ['required', 'string', 'max:500'],
            'catatan'           => ['nullable', 'string', 'max:500'],
            'metode_pembayaran' => ['required', 'in:transfer,qris,ewallet,cod'],
        ], [
            'qty.required'               => 'Jumlah pengerjaan wajib diisi.',
            'qty.min'                    => 'Jumlah pengerjaan minimal 1.',
            'qty.max'                    => 'Jumlah pengerjaan maksimal 10.',
            'jadwal.required'            => 'Jadwal pengerjaan wajib dipilih.',
            'jadwal.after_or_equal'      => 'Jadwal pengerjaan tidak boleh di masa lalu.',
            'alamat_lokasi.required'     => 'Alamat lokasi pengerjaan wajib diisi.',
            'metode_pembayaran.required' => 'Pilih metode pembayaran.',
        ]);

        $total = $jasa->harga * $data['qty'];

        $pesanan = DB::transaction(function () use ($jasa, $customer, $data, $total) {
            $pesanan = new PesananJasa([
                'kode'              => 'JSA-' . now()->format('ymd') . '-' . strtoupper(Str::random(4)),
                'customer_id'       => $customer->id,
                'tanggal_pesan'     => now()->toDateString(),
                'metode_pembayaran' => $data['metode_pembayaran'],
                'alamat_lokasi'     => $data['alamat_lokasi'],
                'catatan'           => $data['catatan'] ?? null,
            ]);
            $pesanan->jadwal_pengerjaan = $data['jadwal'];
            $pesanan->total_bayar       = $total;
            $pesanan->save();   // status_pembayaran & status_pesanan memakai nilai bawaan database

            $pesanan->detail()->create([
                'jasa_id'  => $jasa->id,
                'qty'      => $data['qty'],
                'harga'    => $jasa->harga,   // harga saat transaksi
                'subtotal' => $total,
            ]);

            return $pesanan;
        });

        return redirect()->route('pesanan-jasa.show', $pesanan)
            ->with('status', 'Pemesanan jasa berhasil dibuat. Silakan selesaikan pembayaran.');
    }
}
