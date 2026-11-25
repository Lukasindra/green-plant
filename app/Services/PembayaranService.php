<?php

namespace App\Services;

use App\Models\PesananJasa;
use App\Models\PesananProduk;
use App\Models\Produk;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Satu tempat untuk semua perubahan status pembayaran pesanan.
 *
 * Dipakai oleh:
 *  - PaymentController (callback onSuccess Snap, halaman bayar, polling status)
 *  - MidtransNotificationController (webhook/notifikasi Midtrans)
 *
 * Status Midtrans yang dianggap lunas: settlement, atau capture + fraud accept.
 */
class PembayaranService
{
    private const MODEL_TIPE = [
        'produk' => PesananProduk::class,
        'jasa'   => PesananJasa::class,
    ];

    public function __construct(private MidtransService $midtrans)
    {
    }

    /**
     * Simpan token Snap yang baru dibuat (checkout awal maupun bayar ulang).
     * order_id Midtrans ikut disimpan supaya status transaksi bisa dicek lagi nanti.
     */
    public function simpanToken($pesanan, string $orderId, string $token): void
    {
        $tabel = $pesanan->getTable();

        $pesanan->snap_token        = $token;
        $pesanan->status_pembayaran = $pesanan::STATUS_UNPAID;

        if (Schema::hasColumn($tabel, 'midtrans_order_id')) {
            $pesanan->midtrans_order_id = $orderId;
        }

        // Detail transaksi lama tidak berlaku lagi untuk token yang baru.
        if (Schema::hasColumn($tabel, 'midtrans_transaction_id')) {
            $pesanan->midtrans_transaction_id = null;
        }

        if (Schema::hasColumn($tabel, 'midtrans_payment_type')) {
            $pesanan->midtrans_payment_type = null;
        }

        $pesanan->save();
    }

    /**
     * Cari pesanan berdasarkan order_id Midtrans.
     *
     * order_id bisa berupa invoice_number (transaksi awal) atau
     * invoice_number.'-'.time() (bayar ulang).
     *
     * @return array{0: object, 1: string}|null  [pesanan, tipe]
     */
    public function temukan(string $orderId): ?array
    {
        if ($orderId === '') {
            return null;
        }

        foreach (self::MODEL_TIPE as $tipe => $model) {
            $tabel = (new $model)->getTable();

            if (! Schema::hasTable($tabel)) {
                continue;
            }

            if (Schema::hasColumn($tabel, 'midtrans_order_id')) {
                $pesanan = $model::query()->where('midtrans_order_id', $orderId)->first();

                if ($pesanan) {
                    return [$pesanan, $tipe];
                }
            }

            // Data lama: order_id belum tersimpan di kolom midtrans_order_id.
            $kandidat = array_unique([$orderId, Str::beforeLast($orderId, '-')]);

            foreach ($kandidat as $invoice) {
                $pesanan = $model::query()->where('invoice_number', $invoice)->first();

                if ($pesanan) {
                    return [$pesanan, $tipe];
                }
            }
        }

        return null;
    }

    /**
     * Terapkan isi notifikasi Midtrans (signature sudah diverifikasi pemanggil).
     *
     * @return array{status: string, pesan?: string}
     */
    public function dariNotifikasi(array $payload): array
    {
        $orderId = (string) ($payload['order_id'] ?? '');
        $status  = strtolower((string) ($payload['transaction_status'] ?? ''));

        if ($orderId === '' || $status === '') {
            return ['status' => 'diabaikan', 'pesan' => 'Payload notifikasi tidak lengkap.'];
        }

        $temuan = $this->temukan($orderId);

        if (! $temuan) {
            Log::info('Notifikasi Midtrans untuk pesanan yang tidak dikenal: ' . $orderId);

            return ['status' => 'diabaikan', 'pesan' => 'Pesanan tidak ditemukan.'];
        }

        [$pesanan, $tipe] = $temuan;

        // Setelah bayar ulang, notifikasi dari transaksi lama tidak boleh menimpa status.
        $orderIdTersimpan = $pesanan->getAttribute('midtrans_order_id');

        if (filled($orderIdTersimpan) && $orderIdTersimpan !== $orderId) {
            Log::info('Notifikasi transaksi lama diabaikan: ' . $orderId . ' (aktif: ' . $orderIdTersimpan . ')');

            return ['status' => 'diabaikan', 'pesan' => 'Transaksi lama, bukan transaksi aktif.'];
        }

        // Pengaman tambahan: nominal notifikasi harus sama dengan total pesanan.
        if (array_key_exists('gross_amount', $payload)
            && (int) round((float) $payload['gross_amount']) !== (int) $pesanan->total_bayar) {
            Log::warning('gross_amount notifikasi tidak cocok untuk ' . $orderId);

            return ['status' => 'ditolak', 'pesan' => 'gross_amount tidak cocok dengan total pesanan.'];
        }

        $this->catatTransaksi($pesanan, $orderId, $payload);

        return $this->terapkan($pesanan, $tipe, $status, $payload['fraud_status'] ?? null);
    }

    /**
     * Tanya status terakhir ke API Midtrans (dipakai polling, callback sukses,
     * rekonsiliasi, dan pengaman pembatalan pesanan).
     *
     * @return array{status: string, peringatan: ?string, belum_ada?: bool}
     */
    public function sinkronkan($pesanan, string $tipe): array
    {
        if ($this->dibatalkan($pesanan)) {
            return ['status' => 'dibatalkan', 'peringatan' => null];
        }

        if ($pesanan->sudahLunas()) {
            return ['status' => 'paid', 'peringatan' => null];
        }

        $orderId = $pesanan->getAttribute('midtrans_order_id') ?: $pesanan->invoice_number;

        if (! filled($orderId)) {
            return ['status' => 'pending', 'peringatan' => null];
        }

        try {
            $status = $this->midtrans->status($orderId);
        } catch (\RuntimeException $e) {
            Log::warning('Gagal memeriksa status Midtrans ' . $orderId . ': ' . $e->getMessage());

            return ['status' => 'tidak_diketahui', 'peringatan' => null];
        }

        // Transaksi belum pernah dibuat di Midtrans (mis. token belum dipakai).
        if (($status['transaction_status'] ?? null) === 'belum_ada') {
            return ['status' => 'pending', 'peringatan' => null, 'belum_ada' => true];
        }

        $this->catatTransaksi($pesanan, $orderId, $status);

        return $this->terapkan(
            $pesanan,
            $tipe,
            strtolower((string) ($status['transaction_status'] ?? '')),
            $status['fraud_status'] ?? null
        );
    }

    /** Pesanan yang sudah dibatalkan tidak boleh dibayar / ditandai lunas. */
    public function dibatalkan($pesanan): bool
    {
        return Schema::hasColumn($pesanan->getTable(), 'status_pesanan')
            && $pesanan->status_pesanan === 'dibatalkan';
    }

    /**
     * Tandai lunas + potong stok. Aman dipanggil berulang (idempotent).
     *
     * @return string|null Peringatan bila stok tidak cukup (pesanan tetap lunas)
     */
    public function tandaiLunas($pesanan, string $tipe): ?string
    {
        $peringatan = null;

        DB::transaction(function () use ($pesanan, $tipe, &$peringatan): void {
            $tabel = $pesanan->getTable();

            // Kunci baris pesanan: dua callback/notifikasi bersamaan tidak boleh
            // sama-sama menandai lunas lalu memotong stok dua kali.
            $segar = $pesanan->newQuery()->whereKey($pesanan->getKey())->lockForUpdate()->first();

            if (! $segar || $segar->sudahLunas()) {
                return;
            }

            $segar->status_pembayaran = $segar::STATUS_PAID;

            if (Schema::hasColumn($tabel, 'tanggal_bayar')) {
                $segar->tanggal_bayar = now()->toDateString();
            }

            // Pesanan produk yang baru lunas dipindah ke antrean "siap kirim" admin.
            if ($tipe === 'produk'
                && Schema::hasColumn($tabel, 'status_pesanan')
                && in_array($segar->status_pesanan, ['menunggu_pembayaran', 'diproses'], true)) {
                $segar->status_pesanan = 'dibayar';
            }

            $segar->save();

            // Stok produk berkurang saat pembayaran terkonfirmasi Midtrans (bukan saat checkout).
            if ($tipe === 'produk' && Schema::hasTable('produk')) {
                try {
                    $this->kurangiStok($segar);
                } catch (\RuntimeException $e) {
                    // Uang sudah masuk lewat Midtrans: pesanan tetap lunas, admin diberi tahu lewat log.
                    Log::error('Stok tidak cukup untuk pesanan ' . $segar->invoice_number . ': ' . $e->getMessage());
                    $peringatan = $e->getMessage();
                }
            }
        });

        return $peringatan;
    }

    /** @param array{status: string, peringatan: ?string} */
    private function terapkan($pesanan, string $tipe, string $status, ?string $fraud): array
    {
        $fraud = $fraud === null ? null : strtolower($fraud);

        if ($this->dibatalkan($pesanan)) {
            Log::warning('Pembayaran Midtrans masuk untuk pesanan yang sudah dibatalkan: '
                . ($pesanan->invoice_number ?? $pesanan->kode));

            return ['status' => 'dibatalkan', 'peringatan' => null];
        }

        if ($pesanan->sudahLunas()) {
            return ['status' => 'paid', 'peringatan' => null];
        }

        $lunas = $status === 'settlement'
            || ($status === 'capture' && $fraud === 'accept');

        if ($lunas) {
            return ['status' => 'paid', 'peringatan' => $this->tandaiLunas($pesanan, $tipe)];
        }

        if (in_array($status, ['deny', 'cancel', 'expire', 'failure'], true)
            || ($status === 'capture' && $fraud === 'deny')) {
            $this->tandaiGagal($pesanan);

            return ['status' => 'failed', 'peringatan' => null];
        }

        // pending, capture + challenge, refund, dan status lain: biarkan apa adanya.
        return ['status' => 'pending', 'peringatan' => null];
    }

    private function tandaiGagal($pesanan): void
    {
        if ($pesanan->sudahLunas() || $pesanan->status_pembayaran === $pesanan::STATUS_FAILED) {
            return;
        }

        $pesanan->status_pembayaran = $pesanan::STATUS_FAILED;
        $pesanan->save();
    }

    /** Simpan ID transaksi & jenis pembayaran Midtrans bila kolomnya tersedia. */
    private function catatTransaksi($pesanan, string $orderId, array $status): void
    {
        $tabel   = $pesanan->getTable();
        $berubah = false;

        if (Schema::hasColumn($tabel, 'midtrans_order_id') && $pesanan->midtrans_order_id !== $orderId) {
            $pesanan->midtrans_order_id = $orderId;
            $berubah = true;
        }

        foreach (['midtrans_transaction_id' => 'transaction_id', 'midtrans_payment_type' => 'payment_type'] as $kolom => $kunci) {
            $nilai = $status[$kunci] ?? null;

            if (Schema::hasColumn($tabel, $kolom) && filled($nilai) && $pesanan->{$kolom} !== (string) $nilai) {
                $pesanan->{$kolom} = (string) $nilai;
                $berubah = true;
            }
        }

        if ($berubah) {
            $pesanan->save();
        }
    }

    /** Potong stok dengan kunci baris agar dua proses bersamaan tidak sama-sama lolos. */
    private function kurangiStok(PesananProduk $pesanan): void
    {
        foreach ($pesanan->detail()->get() as $detail) {
            if (! $detail->produk_id) {
                continue;
            }

            $produk = Produk::lockForUpdate()->find($detail->produk_id);

            if (! $produk || $produk->stok < $detail->qty) {
                throw new \RuntimeException(
                    'Stok ' . ($produk?->nama ?? 'produk') . ' tidak mencukupi. Admin akan menyesuaikan pesanan ini.'
                );
            }

            $produk->decrement('stok', $detail->qty);
        }
    }
}
