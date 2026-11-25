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
    /** Opsi rentang grafik penjualan (hari => label tombol). */
    private const PERIODE_GRAFIK = [
        7   => '7 Hari',
        30  => '30 Hari',
        90  => '90 Hari',
        365 => '1 Tahun',
    ];

    /** Keterangan rentang untuk subjudul grafik. */
    private const KETERANGAN_PERIODE = [
        7   => '7 hari terakhir',
        30  => '30 hari terakhir',
        90  => '90 hari terakhir',
        365 => '12 bulan terakhir',
    ];

    public function __invoke(Request $request): View
    {
        $hariIni = CarbonImmutable::today();

        // Rentang grafik dipilih lewat ?periode=7|30|90|365 (default 30 hari).
        $periode = $request->integer('periode', 30);
        $periode = array_key_exists($periode, self::PERIODE_GRAFIK) ? $periode : 30;

        // Pesanan terbaru (produk + jasa), diurutkan dari yang paling baru
        $recentOrders = $this->pesananTerbaru(PesananProduk::class, 'Produk')
            ->concat($this->pesananTerbaru(PesananJasa::class, 'Jasa'))
            ->sortByDesc('tanggal')
            ->take(6)
            ->values();

        // Total pendapatan bulan ini: hanya pesanan yang sudah lunas
        $pendapatanBulanIni = (int) $this->pesananBerbayar(PesananProduk::class, $hariIni->startOfMonth(), $hariIni->endOfMonth())->sum('total_bayar')
            + (int) $this->pesananBerbayar(PesananJasa::class, $hariIni->startOfMonth(), $hariIni->endOfMonth())->sum('total_bayar');

        // Grafik penjualan sesuai rentang pilihan (juga hanya pesanan lunas).
        // Rentang panjang diagregasi agar titik grafik tidak terlalu padat:
        // 90 hari -> per minggu, 1 tahun -> per bulan.
        $awalGrafik = match ($periode) {
            365 => $hariIni->startOfMonth()->subMonths(11),
            90  => $hariIni->subDays(89),
            default => $hariIni->subDays($periode - 1),
        };

        $pesananGrafik = $this->pesananBerbayar(PesananProduk::class, $awalGrafik, $hariIni)
            ->concat($this->pesananBerbayar(PesananJasa::class, $awalGrafik, $hariIni));

        // Kunci pengelompokan: bulan (Y-m), indeks minggu, atau tanggal (Y-m-d).
        $kunciGrafik = function ($tanggal) use ($periode, $awalGrafik): string {
            $t = CarbonImmutable::parse($tanggal);

            if ($periode === 365) {
                return $t->format('Y-m');
            }

            if ($periode === 90) {
                return (string) intdiv(max(0, (int) round($awalGrafik->diffInDays($t))), 7);
            }

            return $t->format('Y-m-d');
        };

        $nilaiGrafik = $pesananGrafik
            ->groupBy(fn ($pesanan): string => $kunciGrafik($pesanan->tanggal_pesan))
            ->map(fn (Collection $pesanan): int => (int) $pesanan->sum('total_bayar'));

        $salesChart = match ($periode) {
            365 => collect(range(0, 11))->map(function (int $offset) use ($awalGrafik, $nilaiGrafik): array {
                $bulan = $awalGrafik->addMonths($offset);

                return [
                    'tanggal' => $bulan->toDateString(),
                    'label' => $bulan->format('M Y'),
                    'nilai' => $nilaiGrafik->get($bulan->format('Y-m'), 0),
                ];
            }),
            90 => collect(range(0, 12))->map(function (int $minggu) use ($awalGrafik, $nilaiGrafik): array {
                $mulai = $awalGrafik->addDays($minggu * 7);

                return [
                    'tanggal' => $mulai->toDateString(),
                    'label' => $mulai->format('d M'),
                    'nilai' => $nilaiGrafik->get((string) $minggu, 0),
                ];
            }),
            default => collect(range(0, $periode - 1))
                ->map(function (int $offset) use ($awalGrafik, $nilaiGrafik): array {
                    $tanggal = $awalGrafik->addDays($offset);

                    return [
                        'tanggal' => $tanggal->toDateString(),
                        'label' => $tanggal->format('d M'),
                        'nilai' => $nilaiGrafik->get($tanggal->toDateString(), 0),
                    ];
                }),
        };

        $puncakNilai = (int) $salesChart->max('nilai');
        $pembagi = max(1, $puncakNilai);
        $jumlahTitik = max(1, $salesChart->count() - 1);
        $chartPoints = $salesChart
            ->values()
            ->map(function (array $hari, int $index) use ($pembagi, $jumlahTitik): string {
                $x = (int) round($index * 600 / $jumlahTitik);
                $y = 180 - (int) round($hari['nilai'] / $pembagi * 160);

                return "{$x},{$y}";
            })
            ->implode(' ');

        // Kartu tindakan: pembayaran yang gagal dikonfirmasi Midtrans dan produk dengan stok rendah
        $pembayaranGagal = $this->hitungPembayaranGagal();
        $stokRendah = $this->produkStokRendah();

        return view('admin.dashboard', [
            'jumlahPesanan' => PesananProduk::count() + PesananJasa::count(),
            'jumlahProduk' => Produk::count(),
            'jumlahJasa' => Jasa::count(),
            'pendapatanBulanIni' => $pendapatanBulanIni,
            'recentOrders' => $recentOrders,
            'salesChart' => $salesChart,
            'chartPoints' => $chartPoints,
            'puncakNilai' => $puncakNilai,
            'pembayaranGagal' => $pembayaranGagal,
            'stokRendah' => $stokRendah,
            'periodeAktif' => $periode,
            'periodeGrafik' => self::PERIODE_GRAFIK,
            'keteranganPeriode' => self::KETERANGAN_PERIODE[$periode],
        ]);
    }

    // Jumlah pesanan (produk + jasa) yang pembayarannya gagal/kedaluwarsa di Midtrans
    private function hitungPembayaranGagal(): int
    {
        $jumlah = 0;

        foreach ([PesananProduk::class, PesananJasa::class] as $model) {
            if (Schema::hasColumn((new $model)->getTable(), 'status_pembayaran')) {
                $jumlah += $model::query()->where('status_pembayaran', 'failed')->count();
            }
        }

        return $jumlah;
    }

    // Produk dengan stok paling tipis, untuk peringatan di dashboard
    private function produkStokRendah(): Collection
    {
        if (! Schema::hasTable('produk')) {
            return collect();
        }

        return Produk::query()
            ->where('stok', '<=', 5)
            ->orderBy('stok')
            ->limit(5)
            ->get(['id', 'nama', 'stok']);
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
     * Pesanan dalam rentang tanggal yang sudah lunas (paid di Midtrans).
     * Kalau tabelnya belum punya kolom status_pembayaran, semua pesanan ikut dihitung.
     */
    private function pesananBerbayar(string $model, CarbonImmutable $dari, CarbonImmutable $sampai): Collection
    {
        $query = $model::query()
            ->whereBetween('tanggal_pesan', [$dari->toDateString(), $sampai->toDateString()]);

        if (Schema::hasColumn((new $model)->getTable(), 'status_pembayaran')) {
            $query->where('status_pembayaran', 'paid');
        }

        return $query->get(['tanggal_pesan', 'total_bayar']);
    }

    /**
     * @return array{id: int, kode: string, pelanggan: string, tipe: string, tanggal: CarbonImmutable, nilai: int, status: ?string}
     */
    private function formatPesanan(PesananProduk|PesananJasa $pesanan, string $tipe): array
    {
        return [
            'id' => $pesanan->id,
            'kode' => $pesanan->kode,
            'pelanggan' => $pesanan->customer?->akun?->nama ?? 'Pelanggan',
            'tipe' => $tipe,
            'tanggal' => CarbonImmutable::parse($pesanan->tanggal_pesan),
            'nilai' => (int) $pesanan->total_bayar,
            'status' => $pesanan->status_pesanan ?? null,
        ];
    }
}
