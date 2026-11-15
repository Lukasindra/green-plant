<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PesananJasa;
use App\Models\PesananProduk;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class LaporanController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()?->isAdmin(), 403);

        $awal = $this->tanggalValid($request->input('awal')) ?? now()->startOfMonth()->toDateString();
        $akhir = $this->tanggalValid($request->input('akhir')) ?? now()->toDateString();

        if ($akhir < $awal) {
            [$awal, $akhir] = [$akhir, $awal];
        }

        $punyaStatusProduk = Schema::hasColumn('pesanan_produk', 'status_pembayaran');
        $punyaStatusJasa = Schema::hasColumn('pesanan_jasa', 'status_pembayaran');

        $basisProduk = PesananProduk::query()
            ->whereBetween('tanggal_pesan', [$awal, $akhir])
            ->when($punyaStatusProduk, fn ($q) => $q->where('status_pembayaran', 'lunas'));
        $basisJasa = PesananJasa::query()
            ->whereBetween('tanggal_pesan', [$awal, $akhir])
            ->when($punyaStatusJasa, fn ($q) => $q->where('status_pembayaran', 'lunas'));

        $rekap = $basisProduk->clone()
            ->selectRaw('tanggal_pesan as tanggal, count(*) as jumlah, coalesce(sum(total_bayar),0) as nilai')
            ->groupBy('tanggal_pesan')
            ->pluck('jumlah', 'tanggal');

        $nilaiProduk = $basisProduk->clone()
            ->selectRaw('tanggal_pesan as tanggal, coalesce(sum(total_bayar),0) as nilai')
            ->groupBy('tanggal_pesan')
            ->pluck('nilai', 'tanggal');

        $jumlahJasa = $basisJasa->clone()
            ->selectRaw('tanggal_pesan as tanggal, count(*) as jumlah')
            ->groupBy('tanggal_pesan')
            ->pluck('jumlah', 'tanggal');

        $nilaiJasa = $basisJasa->clone()
            ->selectRaw('tanggal_pesan as tanggal, coalesce(sum(total_bayar),0) as nilai')
            ->groupBy('tanggal_pesan')
            ->pluck('nilai', 'tanggal');

        $baris = collect(array_unique(array_merge(
            $rekap->keys()->all(),
            $jumlahJasa->keys()->all()
        )))->sort()->values()->map(function ($tanggal) use ($rekap, $nilaiProduk, $jumlahJasa, $nilaiJasa) {
            $jumlahProduk = (int) $rekap->get($tanggal, 0);
            $hargaProduk = (int) $nilaiProduk->get($tanggal, 0);
            $jumlahJasaHari = (int) $jumlahJasa->get($tanggal, 0);
            $hargaJasa = (int) $nilaiJasa->get($tanggal, 0);

            return (object) [
                'tanggal' => $tanggal,
                'jumlah_produk' => $jumlahProduk,
                'nilai_produk' => $hargaProduk,
                'jumlah_jasa' => $jumlahJasaHari,
                'nilai_jasa' => $hargaJasa,
                'total_nilai' => $hargaProduk + $hargaJasa,
            ];
        });

        $total = (object) [
            'jumlah_produk' => $baris->sum('jumlah_produk'),
            'nilai_produk' => $baris->sum('nilai_produk'),
            'jumlah_jasa' => $baris->sum('jumlah_jasa'),
            'nilai_jasa' => $baris->sum('nilai_jasa'),
            'total_nilai' => $baris->sum('total_nilai'),
        ];

        return view('admin.laporan.index', [
            'baris' => $baris,
            'total' => $total,
            'awal' => $awal,
            'akhir' => $akhir,
        ]);
    }

    private function tanggalValid(?string $tanggal): ?string
    {
        try {
            $d = \Carbon\Carbon::createFromFormat('Y-m-d', $tanggal);
        } catch (\Throwable) {
            return null;
        }

        return $tanggal === $d->format('Y-m-d') ? $tanggal : null;
    }
}
