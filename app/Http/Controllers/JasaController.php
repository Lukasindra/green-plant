<?php

namespace App\Http\Controllers;

use App\Models\Jasa;
use App\Models\Kategori;
use App\Models\PesananJasa;
use App\Services\MidtransService;
use App\Services\PembayaranService;
use App\Support\CatatanPesanan;
use App\Support\PencarianKatalog;
use App\Support\PemberitahuanPesanan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class JasaController extends Controller
{
    use PencarianKatalog;

    public function index(Request $request)
    {
        $data = $request->validate([
            'q'        => ['nullable', 'string', 'max:100'],
            'kategori' => ['nullable', 'integer'],
        ]);

        $kategoriAktif = $data['kategori'] ?? null;
        $q             = trim((string) ($data['q'] ?? ''));

        $query = Jasa::with('kategori')->orderByDesc('id');

        $this->terapkanPencarian($query, $q);

        if ($kategoriAktif) {
            $query->where('kategori_id', $kategoriAktif);
        }

        return view('jasa.index', [
            'jasa'          => $query->paginate(12)->withQueryString(),
            'kategori'      => Kategori::where('tipe', 'jasa')->orderBy('nama')->get(),
            'kategoriAktif' => $kategoriAktif,
            'q'             => $q,
        ]);
    }

    public function show(Jasa $jasa)
    {
        $customer = Auth::user()?->customer;

        return view('jasa.show', [
            'jasa'     => $jasa->load('kategori'),
            'alamat'   => $customer?->alamat,
        ]);
    }

    public function pesan(Request $request, Jasa $jasa)
    {
        $customer = Auth::user()?->customer;
        abort_if(! $customer, 403, 'Pemesanan jasa hanya untuk akun customer.');

        $data = $request->validate([
            'qty'           => ['required', 'integer', 'min:1', 'max:10'],
            'jadwal'        => ['required', 'date', 'after_or_equal:today'],
            'alamat_lokasi' => ['required', 'string', 'max:500'],
            'catatan'       => ['nullable', 'string', 'max:500'],
        ], [
            'qty.required'           => 'Jumlah pengerjaan wajib diisi.',
            'qty.min'                => 'Jumlah pengerjaan minimal 1.',
            'qty.max'                => 'Jumlah pengerjaan maksimal 10.',
            'jadwal.required'        => 'Jadwal pengerjaan wajib dipilih.',
            'jadwal.after_or_equal'  => 'Jadwal pengerjaan tidak boleh di masa lalu.',
            'alamat_lokasi.required' => 'Alamat lokasi pengerjaan wajib diisi.',
        ]);

        $total = $jasa->harga * $data['qty'];

        // Satu record pesanan (status unpaid) + Snap token; admin menjadwalkan setelah lunas.
        $pesanan = DB::transaction(function () use ($jasa, $customer, $data, $total) {
            $punyaStatusPesanan = Schema::hasColumn('pesanan_jasa', 'status_pesanan');

            $pesanan = new PesananJasa([
                'kode'          => 'JSA-' . now()->format('ymd') . '-' . strtoupper(Str::random(4)),
                'customer_id'   => $customer->id,
                'tanggal_pesan' => now()->toDateString(),
                'alamat_lokasi' => $data['alamat_lokasi'],
                'catatan'       => $data['catatan'] ?? null,
            ]);
            $pesanan->invoice_number    = $pesanan->buatInvoiceNumber();
            $pesanan->total_bayar       = $total;
            $pesanan->jadwal_pengerjaan = $data['jadwal'];
            $pesanan->status_pembayaran = PesananJasa::STATUS_UNPAID;

            if ($punyaStatusPesanan) {
                $pesanan->status_pesanan = 'menunggu_pembayaran';
            }

            $pesanan->save();

            $pesanan->detail()->create([
                'jasa_id'  => $jasa->id,
                'qty'      => $data['qty'],
                'harga'    => $jasa->harga,   // harga saat transaksi
                'subtotal' => $total,
            ]);

            return $pesanan;
        });

        CatatanPesanan::catat($pesanan, 'dibuat', 'Pemesanan jasa dibuat, menunggu pembayaran.');
        PemberitahuanPesanan::kirim($pesanan, 'dibuat', 'jasa');

        try {
            $orderId = $pesanan->invoice_number;         // order_id awal = invoice_number
            $token   = app(MidtransService::class)->snapToken(
                $orderId,
                (int) $pesanan->total_bayar,
                MidtransService::itemDetails([[
                    'id'    => 'jasa-' . $jasa->id,
                    'nama'  => $jasa->nama,
                    'harga' => $jasa->harga,
                    'qty'   => $data['qty'],
                ]]),
                [
                    'nama'  => Auth::user()->nama,
                    'email' => Auth::user()->email,
                    'no_hp' => Auth::user()->no_hp,
                ]
            );

            // Simpan token + order_id Midtrans (dipakai untuk cek status/webhook).
            app(PembayaranService::class)->simpanToken($pesanan, $orderId, $token);
        } catch (\Throwable $e) {
            // Pesanan tetap tersimpan (unpaid, token kosong); user bisa memakai "Coba Bayar Ulang".
            report($e);

            return redirect()->route('payment.show', ['tipe' => 'jasa', 'pesanan' => $pesanan->id])
                ->with('error', $e instanceof \RuntimeException
                    ? $e->getMessage()
                    : 'Gagal membuat token pembayaran Midtrans. Silakan coba "Coba Bayar Ulang".');
        }

        return redirect()->route('payment.show', ['tipe' => 'jasa', 'pesanan' => $pesanan->id])
            ->with('status', 'Pemesanan jasa berhasil dibuat. Selesaikan pembayaran pada jendela yang terbuka.');
    }
}
