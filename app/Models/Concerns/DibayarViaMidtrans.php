<?php

namespace App\Models\Concerns;

use Illuminate\Support\Str;

/**
 * Perilaku pembayaran Midtrans yang dipakai PesananProduk dan PesananJasa.
 * Status pembayaran: unpaid (menunggu Snap), paid (Snap sukses), failed (gagal/kadaluarsa).
 */
trait DibayarViaMidtrans
{
    public const STATUS_UNPAID = 'unpaid';
    public const STATUS_PAID   = 'paid';
    public const STATUS_FAILED = 'failed';

    /** Apakah pesanan ini sudah lunas menurut Midtrans. */
    public function sudahLunas(): bool
    {
        return $this->status_pembayaran === self::STATUS_PAID;
    }

    /** Belum dibayar dan masih boleh dibayar / dibayar ulang. */
    public function masihBelumDibayar(): bool
    {
        return in_array($this->status_pembayaran, [self::STATUS_UNPAID, self::STATUS_FAILED], true);
    }

    /** Invoice format INV-YYYYMMDD-XXXX, diulang bila tabrakan (kolom unique). */
    public static function buatInvoiceNumber(): string
    {
        $awalan = 'INV-' . now()->format('Ymd') . '-';

        for ($i = 0; $i < 5; $i++) {
            $invoice = $awalan . strtoupper(Str::random(4));

            if (! static::query()->where('invoice_number', $invoice)->exists()) {
                return $invoice;
            }
        }

        // Serangan keberuntungan terakhir: tambah kata acak yang lebih panjang
        return $awalan . strtoupper(Str::random(8));
    }
}
