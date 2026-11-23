<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PesananJasa;
use App\Models\PesananProduk;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;

/**
 * Pemantauan status pembayaran Midtrans (unpaid/paid/failed).
 * Tidak ada lagi aksi verifikasi: status ditetapkan oleh Midtrans Snap.
 */
class PembayaranController extends Controller
{
    public function index(Request $request): View
    {
        $tipe = in_array($request->query('tipe'), ['produk', 'jasa'], true)
            ? $request->query('tipe')
            : 'semua';

        $jenis = in_array($request->query('jenis'), ['unpaid', 'paid', 'failed'], true)
            ? $request->query('jenis')
            : 'unpaid';

        $semua = $this->ambilPesanan($tipe, $jenis);

        // Paginasi manual karena data produk & jasa sudah digabung
        $perHalaman = 15;
        $halaman = LengthAwarePaginator::resolveCurrentPage();
        $pesanan = new LengthAwarePaginator(
            $semua->slice(($halaman - 1) * $perHalaman, $perHalaman)->values(),
            $semua->count(),
            $perHalaman,
            $halaman,
            ['path' => url()->current(), 'query' => $request->query()]
        );

        $ringkasan = [];
        foreach (['unpaid', 'paid', 'failed'] as $k) {
            $data = $this->ambilPesanan($tipe, $k);
            $ringkasan[$k] = [
                'jumlah' => $data->count(),
                'nilai'  => (int) $data->sum('total_bayar'),
            ];
        }

        return view('admin.pembayaran.index', [
            'pesanan'   => $pesanan,
            'tipe'      => $tipe,
            'jenis'     => $jenis,
            'ringkasan' => $ringkasan,
        ]);
    }

    private function ambilPesanan(string $tipe, string $jenis)
    {
        $modelTipe = [
            'produk' => PesananProduk::class,
            'jasa'   => PesananJasa::class,
        ];

        $hasil = collect();

        foreach ($modelTipe as $k => $model) {
            if ($tipe !== 'semua' && $tipe !== $k) {
                continue;
            }

            $hasil = $hasil->merge(
                $model::query()
                    ->with(['customer.akun', 'detail'])
                    ->where('status_pembayaran', $jenis)
                    ->orderByDesc('id')
                    ->limit(50)
                    ->get()
            );
        }

        return $hasil
            ->sortByDesc(fn ($pesanan) => [$pesanan->tanggal_pesan?->timestamp, $pesanan->id])
            ->values();
    }
}
