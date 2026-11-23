<?php

namespace App\Http\Controllers;

use App\Models\PesananJasa;
use App\Models\PesananProduk;
use App\Services\MidtransService;
use App\Services\PembayaranService;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

/**
 * Pembayaran terpusat via Midtrans Snap.
 *
 * Alur:
 *  - show    : halaman pembayaran yang membuka popup Snap memakai snap_token tersimpan.
 *  - success : callback onSuccess Snap -> verifikasi ke API Midtrans lalu tandai paid.
 *  - status  : polling JSON untuk pembayaran yang selesai belakangan (VA/e-wallet).
 *  - retry   : "Coba Bayar Ulang" -> token BARU dengan order_id invoice_number.'-'.time(),
 *              tetap satu record order (invoice_number tidak berubah).
 *
 * Perubahan status sebenarnya ditangani PembayaranService supaya callback,
 * webhook, dan polling memakai aturan yang sama.
 */
class PaymentController extends Controller
{
    private const MODEL_TIPE = [
        'produk' => PesananProduk::class,
        'jasa'   => PesananJasa::class,
    ];

    public function __construct(private PembayaranService $pembayaran)
    {
    }

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

        if ($this->pembayaran->dibatalkan($pesanan)) {
            return $this->keSukses($tipe, $pesanan)
                ->with('error', 'Pesanan ini sudah dibatalkan, jadi pembayaran tidak dapat dikonfirmasi.');
        }

        // Refresh/klik ganda: jangan potong stok dua kali dan jangan ubah tanggal bayar.
        if ($pesanan->sudahLunas()) {
            return $this->keSukses($tipe, $pesanan)
                ->with('info', 'Pesanan ini sudah lunas.');
        }

        $hasil = $this->pembayaran->sinkronkan($pesanan, $tipe);

        if ($hasil['status'] === 'failed') {
            return $this->keSukses($tipe, $pesanan)
                ->with('error', 'Pembayaran gagal atau kedaluwarsa. Buka halaman pembayaran dan tekan "Coba Bayar Ulang".');
        }

        if ($hasil['status'] === 'dibatalkan') {
            return $this->keSukses($tipe, $pesanan)
                ->with('error', 'Pesanan ini sudah dibatalkan, jadi pembayaran tidak dapat dikonfirmasi.');
        }

        if ($hasil['status'] !== 'paid') {
            // Callback onSuccess Snap tetap menjadi bukti utama bila verifikasi API
            // belum tersedia (gangguan jaringan / status Midtrans belum berubah).
            $hasil = [
                'status'     => 'paid',
                'peringatan' => $this->pembayaran->tandaiLunas($pesanan, $tipe),
            ];
        }

        if (filled($hasil['peringatan'] ?? null)) {
            // Pembayaran sudah diterima Midtrans; hanya stok yang tidak bisa dipotong penuh.
            return $this->keSukses($tipe, $pesanan)->with('peringatan', $hasil['peringatan']);
        }

        return $this->keSukses($tipe, $pesanan)
            ->with('status', 'Pembayaran berhasil dikonfirmasi Midtrans. Pesanan Anda lunas!');
    }

    // -------------------------------------------------------------- status

    /**
     * Polling dari payment.blade.php. Dipakai untuk pembayaran yang selesai
     * belakangan (VA/e-wallet) tanpa harus menutup halaman.
     */
    public function status(Request $request, string $tipe, int $pesanan): JsonResponse
    {
        $pesanan = $this->temukan($tipe, $pesanan);
        $this->otorisasi($request, $pesanan);

        if ($pesanan->sudahLunas()) {
            return response()->json(['status' => 'paid', 'peringatan' => null]);
        }

        return response()->json($this->pembayaran->sinkronkan($pesanan, $tipe));
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

        if ($this->pembayaran->dibatalkan($pesanan)) {
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

        $this->pembayaran->simpanToken($pesanan, $orderIdBaru, $token);

        return $this->keShow($tipe, $pesanan)
            ->with('status', 'Token pembayaran baru berhasil dibuat. Lanjutkan pembayaran.');
    }

    // ------------------------------------------------------------ internals

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
        $akun    = auth()->user();
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
