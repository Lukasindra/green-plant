<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PesananJasa;
use App\Models\PesananProduk;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;

class PembayaranController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()?->isAdmin(), 403);

        $tipe = in_array($request->query('tipe'), ['produk', 'jasa'], true)
            ? $request->query('tipe')
            : 'semua';

        $jenis = in_array($request->query('jenis'), ['menunggu_bayar', 'menunggu_verifikasi', 'lunas'], true)
            ? $request->query('jenis')
            : 'menunggu_verifikasi';

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
        foreach (['menunggu_verifikasi', 'menunggu_bayar', 'lunas'] as $k) {
            $data = $this->ambilPesanan($tipe, $k);
            $ringkasan[$k] = [
                'jumlah' => $data->count(),
                'nilai' => (int) $data->sum('total_bayar'),
            ];
        }

        return view('admin.pembayaran.index', [
            'pesanan' => $pesanan,
            'tipe' => $tipe,
            'jenis' => $jenis,
            'ringkasan' => $ringkasan,
        ]);
    }

    private function ambilPesanan(string $tipe, string $jenis)
    {
        $modelTipe = [
            'produk' => PesananProduk::class,
            'jasa' => PesananJasa::class,
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
