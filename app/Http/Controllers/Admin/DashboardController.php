<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Jasa;
use App\Models\PesananJasa;
use App\Models\PesananProduk;
use App\Models\Produk;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        abort_unless($request->user()?->isAdmin(), 403);

        $productOrders = PesananProduk::query()
            ->with('customer.akun')
            ->latest('tanggal_pesan')
            ->limit(5)
            ->get(['id', 'kode', 'customer_id', 'tanggal_pesan', 'total_bayar'])
            ->map(fn (PesananProduk $order): array => $this->formatOrder($order, 'Produk'));

        $serviceOrders = PesananJasa::query()
            ->with('customer.akun')
            ->latest('tanggal_pesan')
            ->limit(5)
            ->get(['id', 'kode', 'customer_id', 'tanggal_pesan', 'total_bayar'])
            ->map(fn (PesananJasa $order): array => $this->formatOrder($order, 'Jasa'));

        $recentOrders = $productOrders
            ->concat($serviceOrders)
            ->sortByDesc('tanggal')
            ->take(6)
            ->values();

        $today = CarbonImmutable::today();
        $chartStart = $today->subDays(29);
        $ordersInPeriod = PesananProduk::query()
            ->whereBetween('tanggal_pesan', [$chartStart->toDateString(), $today->toDateString()])
            ->get(['tanggal_pesan', 'total_bayar'])
            ->concat(
                PesananJasa::query()
                    ->whereBetween('tanggal_pesan', [$chartStart->toDateString(), $today->toDateString()])
                    ->get(['tanggal_pesan', 'total_bayar'])
            );

        $dailyValues = $ordersInPeriod
            ->groupBy(fn ($order): string => CarbonImmutable::parse($order->tanggal_pesan)->toDateString())
            ->map(fn (Collection $orders): int => (int) $orders->sum('total_bayar'));

        $salesChart = collect(range(0, 29))
            ->map(function (int $offset) use ($chartStart, $dailyValues): array {
                $date = $chartStart->addDays($offset);

                return [
                    'tanggal' => $date->toDateString(),
                    'label' => $date->format('d M'),
                    'nilai' => $dailyValues->get($date->toDateString(), 0),
                ];
            });

        $maximumDailyValue = max(1, (int) $salesChart->max('nilai'));
        $chartPoints = $salesChart
            ->values()
            ->map(function (array $day, int $index) use ($maximumDailyValue): string {
                $x = (int) round($index * 600 / 29);
                $y = 180 - (int) round($day['nilai'] / $maximumDailyValue * 160);

                return "{$x},{$y}";
            })
            ->implode(' ');

        return view('admin.dashboard', [
            'jumlahPesanan' => PesananProduk::count() + PesananJasa::count(),
            'jumlahProduk' => Produk::count(),
            'jumlahJasa' => Jasa::count(),
            'nilaiPesanan' => (int) PesananProduk::sum('total_bayar') + (int) PesananJasa::sum('total_bayar'),
            'recentOrders' => $recentOrders,
            'salesChart' => $salesChart,
            'chartPoints' => $chartPoints,
        ]);
    }

    /**
     * @return array{kode: string, pelanggan: string, tipe: string, tanggal: CarbonImmutable, nilai: int}
     */
    private function formatOrder(PesananProduk|PesananJasa $order, string $type): array
    {
        return [
            'kode' => $order->kode,
            'pelanggan' => $order->customer?->akun?->nama ?? 'Pelanggan',
            'tipe' => $type,
            'tanggal' => CarbonImmutable::parse($order->tanggal_pesan),
            'nilai' => (int) $order->total_bayar,
        ];
    }
}
