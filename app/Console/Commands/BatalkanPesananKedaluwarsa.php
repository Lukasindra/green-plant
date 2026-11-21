<?php

namespace App\Console\Commands;

use App\Models\PesananJasa;
use App\Models\PesananProduk;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class BatalkanPesananKedaluwarsa extends Command
{
    protected $signature = 'pesanan:batalkan-kedaluwarsa
                            {--hari=2 : Batalkan pesanan yang dibuat sekian hari kalender lalu atau lebih}
                            {--dry-run : Hanya hitung, tanpa mengubah data}';

    protected $description = 'Membatalkan pesanan (produk dan jasa) yang belum dibayar dan sudah kedaluwarsa';

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

        foreach (['produk' => PesananProduk::class, 'jasa' => PesananJasa::class] as $nama => $model) {
            if (! Schema::hasColumn((new $model)->getTable(), 'status_pesanan')) {
                $this->warn("Pesanan {$nama}: kolom status_pesanan tidak ada, dilewati.");

                continue;
            }

            $query = $model::query()
                ->where('status_pembayaran', 'menunggu_bayar')      // belum ada bukti bayar
                ->where('metode_pembayaran', '!=', 'cod')           // COD dibayar saat serah terima, bukan di muka
                ->whereNotIn('status_pesanan', ['dibatalkan', 'selesai', 'dikirim', 'dijadwalkan', 'dikerjakan'])
                ->whereDate('tanggal_pesan', '<=', $batas);

            if ($dryRun) {
                $this->line("Pesanan {$nama} yang akan dibatalkan: ".$query->count());

                continue;
            }

            // Stok produk baru berkurang saat lunas, jadi tidak ada stok yang perlu dikembalikan.
            $jumlah = $query->update(['status_pesanan' => 'dibatalkan']);

            $this->info("Pesanan {$nama} dibatalkan: {$jumlah}");
        }

        return self::SUCCESS;
    }
}
