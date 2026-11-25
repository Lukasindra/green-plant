<?php

namespace App\Console\Commands;

use App\Models\PesananJasa;
use App\Models\PesananProduk;
use App\Services\PembayaranService;
use App\Services\ReservasiStokService;
use App\Support\CatatanPesanan;
use App\Support\PemberitahuanPesanan;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class BatalkanPesananKedaluwarsa extends Command
{
    protected $signature = 'pesanan:batalkan-kedaluwarsa
                            {--hari=2 : Batalkan pesanan yang dibuat sekian hari kalender lalu atau lebih}
                            {--dry-run : Hanya hitung, tanpa mengubah data}';

    protected $description = 'Membatalkan pesanan (produk dan jasa) yang belum dibayar dan sudah kedaluwarsa';

    public function __construct(private PembayaranService $pembayaran)
    {
        parent::__construct();
    }

    // Kolom tanggal_pesan hanya berisi tanggal (tanpa jam), jadi batasnya dihitung per hari kalender.
    // Dengan --hari=2, pesanan baru dibatalkan setelah lewat lebih dari 24 jam (paling lama sekitar 48 jam).
    public function handle(): int
    {
        $hari = (int) $this->option('hari');

        if ($hari < 1) {
            $this->error('Nilai --hari minimal 1.');

            return self::INVALID;
        }

        $batas = now()->subDays($hari)->toDateString();
        $dryRun = (bool) $this->option('dry-run');

        if (! $dryRun) {
            // Reservasi yang sudah lewat masa berlaku tidak lagi menahan stok.
            app(ReservasiStokService::class)->bersihkanKedaluwarsa();
        }

        foreach (['produk' => PesananProduk::class, 'jasa' => PesananJasa::class] as $nama => $model) {
            $tabel = (new $model)->getTable();

            if (! Schema::hasColumn($tabel, 'status_pesanan')) {
                $this->warn("Pesanan {$nama}: kolom status_pesanan tidak ada, dilewati.");

                continue;
            }

            $query = $model::query()
                ->where('status_pembayaran', 'unpaid')             // belum dibayar via Midtrans
                ->whereNotIn('status_pesanan', ['dibatalkan', 'selesai', 'dikirim', 'dijadwalkan', 'dikerjakan'])
                ->whereDate('tanggal_pesan', '<=', $batas);

            if ($dryRun) {
                $this->line("Pesanan {$nama} yang akan dibatalkan: ".$query->count());

                continue;
            }

            $dibatalkan   = 0;
            $ditahan      = 0;
            $punyaOrderId = Schema::hasColumn($tabel, 'midtrans_order_id');

            $query->chunkById(100, function ($daftar) use ($model, $nama, $punyaOrderId, &$dibatalkan, &$ditahan): void {
                foreach ($daftar as $pesanan) {
                    // Jangan batalkan pesanan yang ternyata sudah dibayar atau masih
                    // menunggu pembayaran (mis. VA/e-wallet yang settle belakangan).
                    if ($punyaOrderId && $this->ditahanKarenaMidtrans($pesanan, $nama)) {
                        $ditahan++;

                        continue;
                    }

                    // Update bersyarat: jangan menimpa pesanan yang baru saja lunas
                    // lewat webhook di antara pengecekan dan pembatalan.
                    $terbatalkan = $model::query()
                        ->whereKey($pesanan->getKey())
                        ->where('status_pembayaran', '!=', 'paid')
                        ->whereNotIn('status_pesanan', ['dibatalkan', 'selesai', 'dikirim', 'dijadwalkan', 'dikerjakan'])
                        ->update(['status_pesanan' => 'dibatalkan']);

                    if ($terbatalkan > 0) {
                        $dibatalkan++;

                        if ($nama === 'produk') {
                            app(ReservasiStokService::class)->lepas($pesanan);
                        }

                        CatatanPesanan::catat($pesanan, 'dibatalkan', 'Dibatalkan otomatis karena pembayaran kedaluwarsa.');
                        PemberitahuanPesanan::kirim($pesanan, 'dibatalkan', $nama);
                    }
                }
            });

            // Stok produk baru berkurang saat Midtrans sukses, jadi tidak ada stok yang perlu dikembalikan.
            $this->info("Pesanan {$nama} dibatalkan: {$dibatalkan}");

            if ($ditahan > 0) {
                $this->line("Pesanan {$nama} ditahan karena status Midtrans belum final: {$ditahan}");
            }
        }

        return self::SUCCESS;
    }

    /**
     * Cek status Midtrans untuk pesanan yang punya order_id.
     *
     * @return bool True = jangan dibatalkan (lunas / masih menunggu / tidak bisa dipastikan).
     */
    private function ditahanKarenaMidtrans($pesanan, string $tipe): bool
    {
        if (blank($pesanan->midtrans_order_id)) {
            return false;
        }

        try {
            $hasil = $this->pembayaran->sinkronkan($pesanan, $tipe);
        } catch (\Throwable $e) {
            // Status tidak bisa dipastikan: lebih aman menunda pembatalan.
            report($e);

            return true;
        }

        // Transaksi belum pernah dibuat di Midtrans (token tidak dipakai): aman dibatalkan.
        if ($hasil['belum_ada'] ?? false) {
            return false;
        }

        return in_array($hasil['status'], ['paid', 'pending', 'tidak_diketahui'], true);
    }
}
