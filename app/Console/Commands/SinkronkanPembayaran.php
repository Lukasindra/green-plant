<?php

namespace App\Console\Commands;

use App\Models\PesananJasa;
use App\Models\PesananProduk;
use App\Services\PembayaranService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

/**
 * Rekonsiliasi pembayaran Midtrans di latar belakang.
 *
 * Polling di halaman pembayaran hanya berjalan selama tab terbuka. Pesanan yang
 * dibayar lewat VA/e-wallet setelah tab ditutup (dan webhook tidak sampai ke
 * server, mis. saat pengembangan lokal) akan tetap "unpaid" tanpa command ini.
 */
class SinkronkanPembayaran extends Command
{
    protected $signature = 'pesanan:sinkronkan-pembayaran
                            {--hari=7 : Periksa pesanan yang dibuat sekian hari terakhir}';

    protected $description = 'Menyinkronkan status pembayaran Midtrans untuk pesanan yang masih belum dibayar';

    public function __construct(private PembayaranService $pembayaran)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $hari = (int) $this->option('hari');

        if ($hari < 1) {
            $this->error('Nilai --hari minimal 1.');

            return self::INVALID;
        }

        $batas = now()->subDays($hari)->toDateString();

        foreach (['produk' => PesananProduk::class, 'jasa' => PesananJasa::class] as $nama => $model) {
            $tabel = (new $model)->getTable();

            if (! Schema::hasTable($tabel)
                || ! Schema::hasColumn($tabel, 'status_pembayaran')
                || ! Schema::hasColumn($tabel, 'midtrans_order_id')) {
                $this->warn("Pesanan {$nama}: kolom Midtrans belum tersedia, dilewati.");

                continue;
            }

            $ringkasan = ['diperiksa' => 0, 'paid' => 0, 'pending' => 0, 'failed' => 0, 'lainnya' => 0];

            $query = $model::query()
                ->where('status_pembayaran', 'unpaid')
                ->whereNotNull('midtrans_order_id')
                ->where('midtrans_order_id', '!=', '')
                ->whereDate('tanggal_pesan', '>=', $batas);

            if (Schema::hasColumn($tabel, 'status_pesanan')) {
                $query->whereNotIn('status_pesanan', ['dibatalkan']);
            }

            $query->chunkById(100, function ($daftar) use ($nama, &$ringkasan): void {
                foreach ($daftar as $pesanan) {
                    $ringkasan['diperiksa']++;

                    try {
                        $hasil = $this->pembayaran->sinkronkan($pesanan, $nama);
                    } catch (\Throwable $e) {
                        // Satu pesanan bermasalah tidak boleh menghentikan rekonsiliasi lainnya.
                        report($e);
                        $ringkasan['lainnya']++;

                        continue;
                    }

                    $kunci = in_array($hasil['status'], ['paid', 'pending', 'failed'], true)
                        ? $hasil['status']
                        : 'lainnya';

                    $ringkasan[$kunci]++;
                }
            });

            $this->info(
                "Pesanan {$nama} diperiksa: {$ringkasan['diperiksa']}, "
                . "lunas: {$ringkasan['paid']}, menunggu: {$ringkasan['pending']}, "
                . "gagal: {$ringkasan['failed']}, tidak diketahui: {$ringkasan['lainnya']}"
            );
        }

        return self::SUCCESS;
    }
}
