<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\StokTidakCukupException;
use App\Http\Controllers\Controller;
use App\Models\PesananJasa;
use App\Models\PesananProduk;
use App\Models\Produk;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/*
 * Alur status (sesuai CHECK pada tabel di database):
 *
 * pesanan_produk.status_pembayaran : menunggu_bayar -> menunggu_verifikasi -> lunas
 * pesanan_produk.status_pesanan    : menunggu_pembayaran -> dibayar -> dikirim -> selesai  (atau dibatalkan)
 *
 * pesanan_jasa.status_pembayaran   : menunggu_bayar -> menunggu_verifikasi -> lunas
 * pesanan_jasa.status_pesanan      : menunggu_konfirmasi -> dijadwalkan -> dikerjakan -> selesai  (atau dibatalkan)
 */
class PesananController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()?->isAdmin(), 403);

        $tipe = in_array($request->query('tipe'), ['produk', 'jasa'], true)
            ? $request->query('tipe')
            : 'semua';

        $status = $request->query('status');
        $semua = $this->ambilPesanan($tipe, $status);

        // Paginasi manual karena data produk & jasa sudah digabung
        $perHalaman = 15;
        $halaman = LengthAwarePaginator::resolveCurrentPage();
        $pesanan = new LengthAwarePaginator(
            $semua->slice(($halaman - 1) * $perHalaman, $perHalaman)->values(),
            $semua->count(),
            $perHalaman,
            $halaman,
            ['path' => url()->current(), 'query' => $request->query()]
        );

        return view('admin.pesanan.index', [
            'pesanan' => $pesanan,
            'tipe' => $tipe,
            'status' => $status,
        ]);
    }

    public function showProduk(Request $request, PesananProduk $pesanan): View
    {
        abort_unless($request->user()?->isAdmin(), 403);

        $pesanan->load('detail.produk', 'customer.akun');

        return view('admin.pesanan.show', [
            'pesanan' => $pesanan,
            'tipe' => 'produk',
        ]);
    }

    public function showJasa(Request $request, PesananJasa $pesanan): View
    {
        abort_unless($request->user()?->isAdmin(), 403);

        $pesanan->load('detail.jasa', 'customer.akun');

        return view('admin.pesanan.show', [
            'pesanan' => $pesanan,
            'tipe' => 'jasa',
        ]);
    }

    public function aksiProduk(Request $request, PesananProduk $pesanan): RedirectResponse
    {
        abort_unless($request->user()?->isAdmin(), 403);

        return $this->terapkanAksi($request, $pesanan, 'produk');
    }

    public function aksiJasa(Request $request, PesananJasa $pesanan): RedirectResponse
    {
        abort_unless($request->user()?->isAdmin(), 403);

        return $this->terapkanAksi($request, $pesanan, 'jasa');
    }

    // Bukti bayar disimpan di disk privat: hanya admin yang boleh melihat
    public function bukti(Request $request, string $tipe, int $id)
    {
        abort_unless($request->user()?->isAdmin(), 403);

        $pesanan = $tipe === 'jasa'
            ? PesananJasa::query()->findOrFail($id)
            : PesananProduk::query()->findOrFail($id);

        abort_if(! $pesanan->bukti_bayar || ! Storage::exists($pesanan->bukti_bayar), 404);

        return Storage::response($pesanan->bukti_bayar);
    }

    private function ambilPesanan(string $tipe, ?string $status)
    {
        $status = in_array($status, ['menunggu_bayar', 'menunggu_verifikasi', 'lunas', 'dikirim', 'selesai', 'dibatalkan'], true)
            ? $status
            : null;

        $modelTipe = [
            'produk' => PesananProduk::class,
            'jasa' => PesananJasa::class,
        ];

        $hasil = collect();

        foreach ($modelTipe as $k => $model) {
            if ($tipe !== 'semua' && $tipe !== $k) {
                continue;
            }

            $query = $model::query()->with(['customer.akun', 'detail']);

            if (in_array($status, ['menunggu_bayar', 'menunggu_verifikasi', 'lunas'], true)) {
                $query->where('status_pembayaran', $status);
            } elseif ($status !== null) {
                $query->where('status_pesanan', $status);
            }

            $hasil = $hasil->merge(
                $query->orderByDesc('id')->limit(50)->get()
            );
        }

        return $hasil
            ->sortByDesc(fn ($pesanan) => [$pesanan->tanggal_pesan?->timestamp, $pesanan->id])
            ->values();
    }

    private function terapkanAksi(Request $request, $pesanan, string $tipe): RedirectResponse
    {
        $aksi = $request->input('aksi');

        if ($pesanan->status_pesanan === 'dibatalkan') {
            return $this->kembali($request, $tipe, $pesanan, 'Pesanan sudah dibatalkan dan tidak dapat diproses lagi.', 'error');
        }

        try {
            $pesan = DB::transaction(function () use ($aksi, $pesanan, $tipe, $request) {
                $pesan = match ($aksi) {
                    'verifikasi_bayar' => $this->aksiBayar($pesanan, $tipe, true),
                    'tolak_bayar' => $this->aksiBayar($pesanan, $tipe, false),
                    'konfirmasi_cod' => $this->aksiCod($pesanan, $tipe),
                    'dikirim' => $tipe === 'produk' ? $this->aksiDikirim($pesanan) : null,
                    'jadwalkan' => $tipe === 'jasa' ? $this->aksiJadwalkan($pesanan) : null,
                    'kerjakan' => $tipe === 'jasa' ? $this->aksiKerjakan($pesanan) : null,
                    'selesai' => $this->aksiSelesai($pesanan, $tipe),
                    'batalkan' => $this->aksiBatalkan($pesanan),
                    default => null,
                };

                if ($pesan === null) {
                    return null;
                }

                // Catat admin yang memproses (kolom admin_id mengacu ke karyawan.id)
                if ($request->user()?->karyawan) {
                    $pesanan->admin_id = $request->user()->karyawan->id;
                }

                $pesanan->save();

                return $pesan;
            });
        } catch (StokTidakCukupException $e) {
            return $this->kembali($request, $tipe, $pesanan, $e->getMessage(), 'error');
        }

        if (! $pesan) {
            return $this->kembali($request, $tipe, $pesanan, 'Aksi tidak dapat diterapkan pada status pesanan ini.', 'error');
        }

        return $this->kembali($request, $tipe, $pesanan, $pesan, 'status');
    }

    private function aksiBayar($pesanan, string $tipe, bool $terima): ?string
    {
        if ($pesanan->status_pembayaran !== 'menunggu_verifikasi') {
            return null;
        }

        if ($terima) {
            $this->tandaiLunas($pesanan, $tipe);

            return 'Pembayaran berhasil diverifikasi.';
        }

        $pesanan->status_pembayaran = 'menunggu_bayar';

        return 'Bukti bayar ditolak. Pesanan kembali menunggu pembayaran.';
    }

    private function aksiCod($pesanan, string $tipe): ?string
    {
        if ($pesanan->metode_pembayaran !== 'cod' || $pesanan->status_pembayaran !== 'menunggu_bayar') {
            return null;
        }

        $this->tandaiLunas($pesanan, $tipe);

        return 'Pembayaran COD berhasil dikonfirmasi.';
    }

    // Pembayaran lunas. Pesanan produk lanjut ke 'dibayar' dan stok dikurangi.
    // Pesanan jasa tetap 'menunggu_konfirmasi' sampai admin menjadwalkan.
    private function tandaiLunas($pesanan, string $tipe): void
    {
        $pesanan->status_pembayaran = 'lunas';
        $pesanan->tanggal_bayar = now()->toDateString();

        if ($tipe === 'produk') {
            $this->kurangiStok($pesanan);
            $pesanan->status_pesanan = 'dibayar';
        }
    }

    // Stok dikurangi saat pembayaran lunas. Baris produk dikunci agar dua pembayaran
    // yang bersamaan tidak membuat stok minus.
    private function kurangiStok(PesananProduk $pesanan): void
    {
        $pesanan->loadMissing('detail');

        foreach ($pesanan->detail as $d) {
            $produk = Produk::query()->lockForUpdate()->find($d->produk_id);

            if (! $produk || $produk->stok < $d->qty) {
                $nama = $produk?->nama ?? 'produk';
                throw new StokTidakCukupException("Stok {$nama} tidak mencukupi. Pembayaran belum dikonfirmasi.");
            }

            $produk->stok -= $d->qty;
            $produk->save();
        }
    }

    private function aksiDikirim(PesananProduk $pesanan): ?string
    {
        if ($pesanan->status_pesanan !== 'dibayar') {
            return null;
        }

        $pesanan->status_pesanan = 'dikirim';
        $pesanan->tanggal_kirim = now()->toDateString();

        return 'Pesanan ditandai sudah dikirim.';
    }

    private function aksiJadwalkan(PesananJasa $pesanan): ?string
    {
        if ($pesanan->status_pembayaran !== 'lunas' || $pesanan->status_pesanan !== 'menunggu_konfirmasi') {
            return null;
        }

        $pesanan->status_pesanan = 'dijadwalkan';

        return 'Pesanan jasa dijadwalkan.';
    }

    private function aksiKerjakan(PesananJasa $pesanan): ?string
    {
        if ($pesanan->status_pesanan !== 'dijadwalkan') {
            return null;
        }

        $pesanan->status_pesanan = 'dikerjakan';

        return 'Pengerjaan jasa dimulai.';
    }

    private function aksiSelesai($pesanan, string $tipe): ?string
    {
        $prasyarat = $tipe === 'jasa' ? 'dikerjakan' : 'dikirim';

        if ($pesanan->status_pesanan !== $prasyarat) {
            return null;
        }

        $pesanan->status_pesanan = 'selesai';

        if ($tipe === 'jasa') {
            $pesanan->tanggal_selesai = now();
        }

        return 'Pesanan ditandai selesai.';
    }

    // Hanya pesanan yang belum dibayar sama sekali yang boleh dibatalkan.
    private function aksiBatalkan($pesanan): ?string
    {
        $belumDibayar = in_array($pesanan->status_pesanan, ['menunggu_pembayaran', 'menunggu_konfirmasi'], true);

        if ($pesanan->status_pembayaran === 'lunas' || $pesanan->status_pembayaran === 'menunggu_verifikasi' || ! $belumDibayar) {
            return null;
        }

        $pesanan->status_pesanan = 'dibatalkan';

        return 'Pesanan berhasil dibatalkan.';
    }

    private function kembali(Request $request, string $tipe, $pesanan, string $pesan, string $header): RedirectResponse
    {
        // Form halaman detail mengirim dari=detail: kembali ke detail
        if ($request->input('dari') === 'detail') {
            return redirect()
                ->route('admin.pesanan.'.($tipe === 'jasa' ? 'jasa' : 'produk').'.show', $pesanan)
                ->with($header, $pesan);
        }

        // Quick-action dari halaman Pembayaran: kembali ke daftar pembayaran
        if ($request->input('dari') === 'pembayaran') {
            return redirect()->route('admin.pembayaran.index')->with($header, $pesan);
        }

        // Selain itu: kembali ke daftar pesanan sesuai tipe
        return redirect()
            ->route('admin.pesanan.index', ['tipe' => $tipe])
            ->with($header, $pesan);
    }
}
