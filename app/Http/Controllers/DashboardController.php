<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Jasa;
use App\Models\PesananJasa;
use App\Models\PesananProduk;
use App\Models\Produk;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        abort_unless($request->user()?->isAdmin(), 403);

        $hariIni = CarbonImmutable::today();

        // Pesanan terbaru (produk + jasa), diurutkan dari yang paling baru
        $recentOrders = $this->pesananTerbaru(PesananProduk::class, 'Produk')
            ->concat($this->pesananTerbaru(PesananJasa::class, 'Jasa'))
            ->sortByDesc('tanggal')
            ->take(6)
            ->values();

        // Total pendapatan bulan ini: hanya pesanan yang sudah lunas
        $pendapatanBulanIni = (int) $this->pesananBerbayar(PesananProduk::class, $hariIni->startOfMonth(), $hariIni->endOfMonth())->sum('total_bayar')
            + (int) $this->pesananBerbayar(PesananJasa::class, $hariIni->startOfMonth(), $hariIni->endOfMonth())->sum('total_bayar');

        // Grafik penjualan 30 hari terakhir (juga hanya pesanan lunas)
        $awalGrafik = $hariIni->subDays(29);
        $nilaiHarian = $this->pesananBerbayar(PesananProduk::class, $awalGrafik, $hariIni)
            ->concat($this->pesananBerbayar(PesananJasa::class, $awalGrafik, $hariIni))
            ->groupBy(fn ($pesanan): string => CarbonImmutable::parse($pesanan->tanggal_pesan)->toDateString())
            ->map(fn (Collection $pesanan): int => (int) $pesanan->sum('total_bayar'));

        $salesChart = collect(range(0, 29))
            ->map(function (int $offset) use ($awalGrafik, $nilaiHarian): array {
                $tanggal = $awalGrafik->addDays($offset);

                return [
                    'tanggal' => $tanggal->toDateString(),
                    'label' => $tanggal->format('d M'),
                    'nilai' => $nilaiHarian->get($tanggal->toDateString(), 0),
                ];
            });

        $puncakNilai = (int) $salesChart->max('nilai');
        $pembagi = max(1, $puncakNilai);
        $chartPoints = $salesChart
            ->values()
            ->map(function (array $hari, int $index) use ($pembagi): string {
                $x = (int) round($index * 600 / 29);
                $y = 180 - (int) round($hari['nilai'] / $pembagi * 160);

                return "{$x},{$y}";
            })
            ->implode(' ');

        return view('admin.dashboard', [
            'jumlahPesanan' => PesananProduk::count() + PesananJasa::count(),
            'jumlahProduk' => Produk::count(),
            'jumlahJasa' => Jasa::count(),
            'pendapatanBulanIni' => $pendapatanBulanIni,
            'recentOrders' => $recentOrders,
            'salesChart' => $salesChart,
            'chartPoints' => $chartPoints,
            'puncakNilai' => $puncakNilai,
        ]);
    }

    private function pesananTerbaru(string $model, string $tipe): Collection
    {
        $tabel = (new $model)->getTable();
        $kolom = ['id', 'kode', 'customer_id', 'tanggal_pesan', 'total_bayar'];

        // Kolom status ikut diambil kalau tabelnya punya
        if (Schema::hasColumn($tabel, 'status_pesanan')) {
            $kolom[] = 'status_pesanan';
        }

        return $model::query()
            ->with('customer.akun')
            ->latest('tanggal_pesan')
            ->latest('id')
            ->limit(5)
            ->get($kolom)
            ->map(fn ($pesanan): array => $this->formatPesanan($pesanan, $tipe));
    }

    /**
     * Pesanan dalam rentang tanggal yang sudah lunas.
     * Kalau tabelnya belum punya kolom status_pembayaran, semua pesanan ikut dihitung.
     */
    private function pesananBerbayar(string $model, CarbonImmutable $dari, CarbonImmutable $sampai): Collection
    {
        $query = $model::query()
            ->whereBetween('tanggal_pesan', [$dari->toDateString(), $sampai->toDateString()]);

        if (Schema::hasColumn((new $model)->getTable(), 'status_pembayaran')) {
            $query->where('status_pembayaran', 'lunas');
        }

        return $query->get(['tanggal_pesan', 'total_bayar']);
    }

    /**
     * @return array{kode: string, pelanggan: string, tipe: string, tanggal: CarbonImmutable, nilai: int, status: ?string}
     */
    private function formatPesanan(PesananProduk|PesananJasa $pesanan, string $tipe): array
    {
        return [
            'kode' => $pesanan->kode,
            'pelanggan' => $pesanan->customer?->akun?->nama ?? 'Pelanggan',
            'tipe' => $tipe,
            'tanggal' => CarbonImmutable::parse($pesanan->tanggal_pesan),
            'nilai' => (int) $pesanan->total_bayar,
            'status' => $pesanan->status_pesanan ?? null,
        ];
    }
}
