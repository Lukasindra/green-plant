<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PesananJasa;
use App\Models\PesananProduk;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

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
            } elseif (in_array($status, ['dikirim', 'selesai', 'dibatalkan'], true)) {
                if (Schema::hasColumn((new $model)->getTable(), 'status_pesanan')) {
                    $query->where('status_pesanan', $status);
                } else {
                    // Tabel tidak punya kolom status pesanan: hasil kosong
                    $query->whereRaw('1 = 0');
                }
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
        $punyaStatusPesanan = Schema::hasColumn($pesanan->getTable(), 'status_pesanan');
        $statusPesanan = $punyaStatusPesanan ? ($pesanan->status_pesanan ?? 'diproses') : 'diproses';
        $lunas = in_array($pesanan->status_pembayaran, ['lunas', 'dibayar'], true);
        $dibatalkan = $punyaStatusPesanan && $statusPesanan === 'dibatalkan';

        if ($dibatalkan) {
            return $this->kembali($request, $tipe, $pesanan, 'Pesanan sudah dibatalkan dan tidak dapat diproses lagi.', 'error');
        }

        $pesan = match ($aksi) {
            'verifikasi_bayar' => $this->aksiBayar($pesanan, true),
            'tolak_bayar' => $this->aksiBayar($pesanan, false),
            'konfirmasi_cod' => $this->aksiCod($pesanan),
            'dikirim' => $tipe === 'produk'
                ? $this->aksiDikirim($pesanan, $lunas, $punyaStatusPesanan, $statusPesanan)
                : null,
            'selesai' => $this->aksiSelesai($pesanan, $tipe, $lunas, $punyaStatusPesanan, $statusPesanan),
            'batalkan' => $this->aksiBatalkan($pesanan, $lunas, $punyaStatusPesanan, $statusPesanan),
            default => null,
        };

        if (! $pesan) {
            return $this->kembali($request, $tipe, $pesanan, 'Aksi tidak dapat diterapkan pada status pesanan ini.', 'error');
        }

        // Catat admin yang memproses bila struktur data mendukung
        if (Schema::hasColumn($pesanan->getTable(), 'admin_id') && $request->user()?->karyawan) {
            $pesanan->admin_id = $request->user()->karyawan->id;
        }

        $pesanan->save();

        return $this->kembali($request, $tipe, $pesanan, $pesan, 'status');
    }

    private function aksiBayar($pesanan, bool $terima): ?string
    {
        if ($pesanan->status_pembayaran !== 'menunggu_verifikasi') {
            return null;
        }

        if ($terima) {
            $pesanan->status_pembayaran = 'lunas';
            $pesanan->tanggal_bayar = now()->toDateString();

            return 'Pembayaran berhasil diverifikasi.';
        }

        $pesanan->status_pembayaran = 'menunggu_bayar';

        return 'Bukti bayar ditolak. Pesanan kembali menunggu pembayaran.';
    }

    private function aksiCod($pesanan): ?string
    {
        if ($pesanan->metode_pembayaran !== 'cod' || $pesanan->status_pembayaran !== 'menunggu_bayar') {
            return null;
        }

        $pesanan->status_pembayaran = 'lunas';
        $pesanan->tanggal_bayar = now()->toDateString();

        return 'Pembayaran COD berhasil dikonfirmasi.';
    }

    private function aksiDikirim($pesanan, bool $lunas, bool $punyaStatusPesanan, string $statusPesanan): ?string
    {
        if (! $lunas || in_array($statusPesanan, ['dikirim', 'selesai'], true)) {
            return null;
        }

        if ($punyaStatusPesanan) {
            $pesanan->status_pesanan = 'dikirim';
        }
        $pesanan->tanggal_kirim = now()->toDateString();

        return 'Pesanan ditandai sudah dikirim.';
    }

    private function aksiSelesai($pesanan, string $tipe, bool $lunas, bool $punyaStatusPesanan, string $statusPesanan): ?string
    {
        $siap = $tipe === 'jasa'
            ? $lunas
            : $lunas && $statusPesanan === 'dikirim';

        if (! $siap || $statusPesanan === 'selesai') {
            return null;
        }

        if ($punyaStatusPesanan) {
            $pesanan->status_pesanan = 'selesai';
        }
        if (Schema::hasColumn($pesanan->getTable(), 'tanggal_selesai')) {
            $pesanan->tanggal_selesai = now();
        }

        return 'Pesanan ditandai selesai.';
    }

    private function aksiBatalkan($pesanan, bool $lunas, bool $punyaStatusPesanan, string $statusPesanan): ?string
    {
        if ($lunas || $pesanan->status_pembayaran === 'menunggu_verifikasi' || in_array($statusPesanan, ['dikirim', 'selesai'], true)) {
            return null;
        }

        if ($punyaStatusPesanan) {
            $pesanan->status_pesanan = 'dibatalkan';
        }

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
