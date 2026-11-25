<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PesananJasa;
use App\Models\PesananProduk;
use App\Services\ReservasiStokService;
use App\Support\CatatanPesanan;
use App\Support\PemberitahuanPesanan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class PesananController extends Controller
{
    public function index(Request $request): View
    {
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
        $pesanan->load('detail.produk', 'customer.akun');

        if (Schema::hasTable('riwayat_pesanan')) {
            $pesanan->load('riwayat');
        }

        return view('admin.pesanan.show', [
            'pesanan' => $pesanan,
            'tipe' => 'produk',
        ]);
    }

    public function showJasa(Request $request, PesananJasa $pesanan): View
    {
        $pesanan->load('detail.jasa', 'customer.akun');

        if (Schema::hasTable('riwayat_pesanan')) {
            $pesanan->load('riwayat');
        }

        return view('admin.pesanan.show', [
            'pesanan' => $pesanan,
            'tipe' => 'jasa',
        ]);
    }

    public function invoiceProduk(PesananProduk $pesanan): View
    {
        $pesanan->load('detail.produk', 'customer.akun');

        return view('invoice', ['pesanan' => $pesanan, 'tipe' => 'produk']);
    }

    public function invoiceJasa(PesananJasa $pesanan): View
    {
        $pesanan->load('detail.jasa', 'customer.akun');

        return view('invoice', ['pesanan' => $pesanan, 'tipe' => 'jasa']);
    }

    public function aksiProduk(Request $request, PesananProduk $pesanan): RedirectResponse
    {
        return $this->terapkanAksi($request, $pesanan, 'produk');
    }

    public function aksiJasa(Request $request, PesananJasa $pesanan): RedirectResponse
    {
        return $this->terapkanAksi($request, $pesanan, 'jasa');
    }

    private function ambilPesanan(string $tipe, ?string $status)
    {
        $status = in_array($status, ['unpaid', 'paid', 'failed', 'dijadwalkan', 'dikerjakan', 'dikirim', 'selesai', 'dibatalkan', 'perlu_tindakan'], true)
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

            if (in_array($status, ['unpaid', 'paid', 'failed'], true)) {
                $query->where('status_pembayaran', $status);
            } elseif (in_array($status, ['dijadwalkan', 'dikerjakan', 'dikirim', 'selesai', 'dibatalkan', 'perlu_tindakan'], true)) {
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
        $lunas = $pesanan->status_pembayaran === 'paid';
        $dibatalkan = $punyaStatusPesanan && $statusPesanan === 'dibatalkan';

        if ($dibatalkan) {
            return $this->kembali($request, $tipe, $pesanan, 'Pesanan sudah dibatalkan dan tidak dapat diproses lagi.', 'error');
        }

        // Pembayaran tidak lagi diverifikasi admin: Midtrans Snap yang menetapkan unpaid/paid/failed.
        $pesan = match ($aksi) {
            'dikirim' => $tipe === 'produk'
                ? $this->aksiDikirim($pesanan, $lunas, $punyaStatusPesanan, $statusPesanan)
                : null,
            'jadwalkan' => $this->aksiJadwalkan($request, $pesanan, $tipe, $lunas, $punyaStatusPesanan, $statusPesanan),
            'mulai_kerja' => $this->aksiMulaiKerja($pesanan, $tipe, $lunas, $punyaStatusPesanan, $statusPesanan),
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

        $this->catatAksi($pesanan, $aksi, $tipe);

        return $this->kembali($request, $tipe, $pesanan, $pesan, 'status');
    }

    /** Simpan jejak perubahan status + kirim email untuk aksi yang relevan. */
    private function catatAksi($pesanan, string $aksi, string $tipe): void
    {
        $status = match ($aksi) {
            'dikirim'     => 'dikirim',
            'jadwalkan'   => 'dijadwalkan',
            'mulai_kerja' => 'dikerjakan',
            'selesai'     => 'selesai',
            'batalkan'    => 'dibatalkan',
            default       => null,
        };

        if ($status === null) {
            return;
        }

        CatatanPesanan::catat($pesanan, $status, 'Diperbarui oleh admin.');

        if ($aksi === 'batalkan') {
            if ($tipe === 'produk') {
                app(ReservasiStokService::class)->lepas($pesanan);
            }

            PemberitahuanPesanan::kirim($pesanan, 'dibatalkan', $tipe);
        }

        if ($aksi === 'dikirim') {
            PemberitahuanPesanan::kirim($pesanan, 'dikirim', $tipe);
        }
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

    // Khusus jasa: admin menetapkan jadwal pengerjaan (boleh sekaligus mengubah tanggalnya).
    private function aksiJadwalkan(Request $request, $pesanan, string $tipe, bool $lunas, bool $punyaStatusPesanan, string $statusPesanan): ?string
    {
        if ($tipe !== 'jasa' || ! $lunas || ! $punyaStatusPesanan
            || in_array($statusPesanan, ['dikerjakan', 'selesai', 'dibatalkan'], true)) {
            return null;
        }

        $jadwalBaru = $request->input('jadwal');

        if (filled($jadwalBaru) && Schema::hasColumn($pesanan->getTable(), 'jadwal_pengerjaan')) {
            try {
                $pesanan->jadwal_pengerjaan = Carbon::parse($jadwalBaru);
            } catch (\Throwable) {
                return null;
            }
        }

        $pesanan->status_pesanan = 'dijadwalkan';

        return 'Pesanan jasa dijadwalkan.';
    }

    // Khusus jasa: pengerjaan di lokasi dimulai.
    private function aksiMulaiKerja($pesanan, string $tipe, bool $lunas, bool $punyaStatusPesanan, string $statusPesanan): ?string
    {
        if ($tipe !== 'jasa' || ! $lunas || ! $punyaStatusPesanan
            || in_array($statusPesanan, ['dikerjakan', 'selesai', 'dibatalkan'], true)) {
            return null;
        }

        $pesanan->status_pesanan = 'dikerjakan';

        return 'Pengerjaan jasa dimulai.';
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
        if ($lunas || in_array($statusPesanan, ['dikirim', 'selesai'], true)) {
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
