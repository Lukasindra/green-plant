<?php

namespace App\Services;

use Illuminate\Support\Str;
use Midtrans\Config;
use Midtrans\Snap;

/**
 * Pembungkus SDK midtrans/midtrans-php untuk kebutuhan Snap.
 * Semua pemanggilan Midtrans melewati kelas ini supaya konfigurasi diset
 * sekali di satu tempat dan error API tidak meruntuhkan halaman.
 */
class MidtransService
{
    /** Isi konfigurasi statis SDK dari config/midtrans.php (dipanggil sebelum tiap request Snap). */
    public function konfigurasi(): void
    {
        Config::$serverKey    = config('midtrans.server_key');
        Config::$clientKey    = config('midtrans.client_key');
        Config::$isProduction = config('midtrans.is_production', false);
        Config::$isSanitized  = config('midtrans.is_sanitized', true);
        Config::$is3ds        = config('midtrans.is_3ds', true);
    }

    /**
     * Minta Snap token.
     *
     * @param string $orderId     ID transaksi yang dikirim ke Midtrans (invoice_number, atau invoice_number.'-'.time() saat bayar ulang)
     * @param int    $totalBayar  Gross amount dalam rupiah (tanpa desimal)
     * @param array  $itemDetails [['id','price','quantity','name'], ...]
     * @param array  $pelanggan   ['nama'=>..., 'email'=>..., 'no_hp'=>...]
     *
     * @throws \RuntimeException bila kredensial belum diisi atau Midtrans menolak request
     */
    public function snapToken(string $orderId, int $totalBayar, array $itemDetails, array $pelanggan): string
    {
        // Lingkungan test tidak boleh memanggil jaringan: token palsu cukup untuk alur aplikasi.
        if (app()->runningUnitTests()) {
            return 'snap-token-test-' . md5($orderId);
        }

        if (! config('midtrans.server_key') || ! config('midtrans.client_key')) {
            throw new \RuntimeException('Kredensial Midtrans belum diisi. Lengkapi MIDTRANS_SERVER_KEY dan MIDTRANS_CLIENT_KEY di file .env.');
        }

        $this->konfigurasi();

        $params = [
            'transaction_details' => [
                'order_id'     => $orderId,
                'gross_amount' => $totalBayar,
            ],
            'item_details'       => $this->itemAman($itemDetails, $totalBayar),
            'customer_details'   => [
                'first_name' => $pelanggan['nama'] ?? 'Customer',
                'email'      => $pelanggan['email'] ?? null,
                'phone'      => $pelanggan['no_hp'] ?? null,
            ],
            'expiry' => [
                'unit'     => 'hours',
                'duration' => (int) config('midtrans.kedaluwarsa_jam', 24),
            ],
        ];

        try {
            return Snap::getSnapToken($params);
        } catch (\Throwable $e) {
            throw new \RuntimeException('Midtrans menolak permintaan token: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Midtrans menolak transaksi bila jumlah item_details tidak sama dengan gross_amount
     * (mis. detail produk terhapus atau harga berubah). Amankan dengan satu baris total.
     */
    private function itemAman(array $itemDetails, int $totalBayar): array
    {
        $jumlah = 0;

        foreach ($itemDetails as $item) {
            $jumlah += (int) ($item['price'] ?? 0) * (int) ($item['quantity'] ?? 0);
        }

        if (empty($itemDetails) || $jumlah !== $totalBayar) {
            return [[
                'id'       => 'total',
                'price'    => $totalBayar,
                'quantity' => 1,
                'name'     => 'Total pesanan',
            ]];
        }

        return $itemDetails;
    }

    /**
     * Item details wajib dijumlah sama dengan gross_amount, jadi ongkir ikut jadi baris item.
     *
     * @param iterable $baris [['id'=>.., 'nama'=>.., 'harga'=>.., 'qty'=>..], ...]
     */
    public static function itemDetails(iterable $baris, int $ongkir = 0): array
    {
        $item = [];

        foreach ($baris as $b) {
            $item[] = [
                'id'       => (string) $b['id'],
                'price'    => (int) $b['harga'],
                'quantity' => (int) $b['qty'],
                'name'     => Str::limit(trim((string) $b['nama']), 50, '...'),
            ];
        }

        if ($ongkir > 0) {
            $item[] = [
                'id'       => 'ongkir',
                'price'    => $ongkir,
                'quantity' => 1,
                'name'     => 'Ongkos kirim',
            ];
        }

        return $item;
    }
}
