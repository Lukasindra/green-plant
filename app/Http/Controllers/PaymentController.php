<?php

namespace App\Http\Controllers;

use App\Models\PesananJasa;
use App\Models\PesananProduk;
use App\Models\Produk;
use App\Services\MidtransService;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

/**
 * Pembayaran terpusat via Midtrans Snap.
 *
 * Alur:
 *  - show    : halaman pembayaran yang membuka popup Snap memakai snap_token tersimpan.
 *  - success : callback dari popup (onSuccess) -> tandai pesanan paid (+ stok berkurang).
 *  - retry   : "Coba Bayar Ulang" -> token BARU dengan order_id invoice_number.'-'.time(),
 *              tetap satu record order (invoice_number tidak berubah).
 */
class PaymentController extends Controller
{
    private const MODEL_TIPE = [
        'produk' => PesananProduk::class,
        'jasa'   => PesananJasa::class,
    ];

    // ---------------------------------------------------------------- show

    public function show(Request $request, string $tipe, int $pesanan): View|RedirectResponse
    {
        $pesanan = $this->temukan($tipe, $pesanan);
        $this->otorisasi($request, $pesanan);

        if ($pesanan->sudahLunas()) {
            return $this->keSukses($tipe, $pesanan);
        }

        return view('payment', [
            'tipe'    => $tipe,
            'pesanan' => $pesanan,
        ]);
    }

    // ------------------------------------------------------------- success

    /** Dipanggil dari callback onSuccess Snap (redirect GET). Hanya pemilik pesanan boleh. */
    public function success(Request $request, string $tipe, int $pesanan): RedirectResponse
    {
        $pesanan = $this->temukan($tipe, $pesanan);
        $this->otorisasi($request, $pesanan);

        if ($this->dibatalkan($pesanan)) {
            return $this->keSukses($tipe, $pesanan)
                ->with('error', 'Pesanan ini sudah dibatalkan, jadi pembayaran tidak dapat dikonfirmasi.');
        }

        // Refresh/klik ganda: jangan potong stok dua kali dan jangan ubah tanggal bayar.
        if ($pesanan->sudahLunas()) {
            return $this->keSukses($tipe, $pesanan)
                ->with('info', 'Pesanan ini sudah lunas.');
        }

        $peringatan = $this->tandaiLunas($pesanan, $tipe);

        if ($peringatan) {
            // Pembayaran sudah diterima Midtrans; hanya stok yang tidak bisa dipotong penuh.
            return $this->keSukses($tipe, $pesanan)->with('peringatan', $peringatan);
        }

        return $this->keSukses($tipe, $pesanan)
            ->with('status', 'Pembayaran berhasil dikonfirmasi Midtrans. Pesanan Anda lunas!');
    }

    // --------------------------------------------------------------- retry

    /** "Coba Bayar Ulang": token baru untuk order yang sama (anti order_id taken). */
    public function retry(Request $request, string $tipe, int $pesanan): RedirectResponse
    {
        $pesanan = $this->temukan($tipe, $pesanan);
        $this->otorisasi($request, $pesanan);

        if ($pesanan->sudahLunas()) {
            return $this->keShow($tipe, $pesanan)->with('info', 'Pesanan ini sudah lunas.');
        }

        if ($this->dibatalkan($pesanan)) {
            return $this->keShow($tipe, $pesanan)->with('error', 'Pesanan ini sudah dibatalkan.');
        }

        try {
            $orderIdBaru = $this->invoiceUntukMidtrans($pesanan) . '-' . time();
            $token       = $this->mintaToken($tipe, $pesanan, $orderIdBaru);
        } catch (\Throwable $e) {
            Log::warning('Bayar ulang Midtrans gagal: ' . $e->getMessage());

            return $this->keShow($tipe, $pesanan)
                ->with('error', $this->pesanError($e));
        }

        $pesanan->snap_token        = $token;                // timpa token lama
        $pesanan->status_pembayaran = $pesanan::STATUS_UNPAID;
        $pesanan->save();

        return $this->keShow($tipe, $pesanan)
            ->with('status', 'Token pembayaran baru berhasil dibuat. Lanjutkan pembayaran.');
    }

    // ------------------------------------------------------------ internals

    private function tandaiLunas($pesanan, string $tipe): ?string
    {
        $peringatan = null;

        DB::transaction(function () use ($pesanan, $tipe, &$peringatan): void {
            $tabel = $pesanan->getTable();

            // Kunci baris pesanan: dua callback onSuccess yang berjalan bersamaan
            // tidak boleh sama-sama menandai lunas lalu memotong stok dua kali.
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
                    // Uang sudah masuk lewat Snap: pesanan tetap lunas, admin diberi tahu lewat log.
                    Log::error('Stok tidak cukup untuk pesanan ' . $segar->invoice_number . ': ' . $e->getMessage());
                    $peringatan = $e->getMessage();
                }
            }
        });

        return $peringatan;
    }

    /** Potong stok dengan kunci baris agar dua callback bersamaan tidak sama-sama lolos. */
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

    /** Invoice dasar untuk order_id Midtrans; invoice lama diisi otomatis bila kosong. */
    private function invoiceUntukMidtrans($pesanan): string
    {
        if (! $pesanan->invoice_number) {
            $pesanan->invoice_number = $pesanan->buatInvoiceNumber();
            $pesanan->save();
        }

        return $pesanan->invoice_number;
    }

    private function mintaToken(string $tipe, $pesanan, string $orderId): string
    {
        $akun   = auth()->user();
        $layanan = app(MidtransService::class);

        if ($tipe === 'jasa') {
            $baris = $pesanan->detail()
                ->with('jasa')
                ->get()
                ->map(fn ($d) => [
                    'id'    => 'jasa-' . $d->jasa_id,
                    'nama'  => $d->jasa?->nama ?? 'Jasa',
                    'harga' => (int) $d->harga,
                    'qty'   => (int) $d->qty,
                ]);

            return $layanan->snapToken(
                $orderId,
                (int) $pesanan->total_bayar,
                MidtransService::itemDetails($baris),
                ['nama' => $akun->nama, 'email' => $akun->email, 'no_hp' => $akun->no_hp]
            );
        }

        $baris = $pesanan->detail()
            ->with('produk')
            ->get()
            ->map(fn ($d) => [
                'id'    => 'produk-' . $d->produk_id,
                'nama'  => $d->produk?->nama ?? 'Produk',
                'harga' => (int) $d->harga,
                'qty'   => (int) $d->qty,
            ]);

        return $layanan->snapToken(
            $orderId,
            (int) $pesanan->total_bayar,
            MidtransService::itemDetails($baris, (int) $pesanan->ongkir),
            ['nama' => $akun->nama, 'email' => $akun->email, 'no_hp' => $akun->no_hp]
        );
    }

    private function temukan(string $tipe, int $id)
    {
        $model = self::MODEL_TIPE[$tipe] ?? abort(404);

        try {
            return $model::query()->findOrFail($id);
        } catch (QueryException) {
            abort(404);   // tabel belum ada (mis. katalog kosong)
        }
    }

    /** Pesanan yang sudah dibatalkan tidak boleh dibayar / dikonfirmasi lunas. */
    private function dibatalkan($pesanan): bool
    {
        return Schema::hasColumn($pesanan->getTable(), 'status_pesanan')
            && $pesanan->status_pesanan === 'dibatalkan';
    }

    /** Pesan error yang aman ditampilkan: detail SDK hanya untuk RuntimeException milik kita. */
    private function pesanError(\Throwable $e): string
    {
        return $e instanceof \RuntimeException
            ? $e->getMessage()
            : 'Gagal membuat token pembayaran Midtrans. Silakan coba lagi.';
    }

    /** Proteksi: halaman pembayaran hanya untuk pemilik pesanan (akun customer sendiri). */
    private function otorisasi(Request $request, $pesanan): void
    {
        $customer = $request->user()?->customer;

        abort_if(! $customer || $pesanan->customer?->akun_id !== $customer->akun_id, 403,
            'Halaman pembayaran hanya untuk pemilik pesanan.');
    }

    private function keShow(string $tipe, $pesanan): RedirectResponse
    {
        return redirect()->route('payment.show', ['tipe' => $tipe, 'pesanan' => $pesanan->id]);
    }

    private function keSukses(string $tipe, $pesanan): RedirectResponse
    {
        return redirect()->route($tipe === 'jasa' ? 'pesanan-jasa.show' : 'pesanan.show', $pesanan->id);
    }
}
