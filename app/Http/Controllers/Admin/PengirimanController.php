<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Karyawan;
use App\Models\PesananProduk;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class PengirimanController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()?->isAdmin(), 403);

        $status = in_array($request->query('status'), ['siap', 'dikirim', 'selesai'], true)
            ? $request->query('status')
            : 'siap';

        $punyaKolomStatus = Schema::hasColumn('pesanan_produk', 'status_pesanan');

        $query = PesananProduk::query()
            ->with(['customer.akun', 'detail'])
            ->where('status_pembayaran', 'lunas')
            ->orderByDesc('id');

        $query->when($punyaKolomStatus, function ($q) use ($status) {
            match ($status) {
                'dikirim' => $q->where('status_pesanan', 'dikirim'),
                'selesai' => $q->where('status_pesanan', 'selesai'),
                default => $q->where('status_pesanan', 'diproses'),
            };
        }, function ($q) use ($status) {
            // Tanpa kolom status: siap = belum ada tanggal kirim; selesai = tak ada kolom, hasil kosong
            if ($status === 'siap') {
                $q->whereNull('tanggal_kirim');
            } else {
                $q->whereRaw('1 = 0');
            }
        });

        return view('admin.pengiriman.index', [
            'pesanan' => $query->paginate(15)->withQueryString(),
            'daftarKurir' => Karyawan::query()
                ->whereHas('akun', fn ($akun) => $akun->where('role', 'kurir'))
                ->orderBy('username')
                ->get(),
            'status' => $status,
        ]);
    }

    public function aksi(Request $request, PesananProduk $pesanan): \Illuminate\Http\RedirectResponse
    {
        abort_unless($request->user()?->isAdmin(), 403);

        $punyaKolomStatus = Schema::hasColumn((new PesananProduk)->getTable(), 'status_pesanan');
        $statusPesanan = $punyaKolomStatus ? ($pesanan->status_pesanan ?? 'diproses') : 'diproses';
        $lunas = in_array($pesanan->status_pembayaran, ['lunas', 'dibayar'], true);

        $pesan = match ($request->input('aksi')) {
            'kirim' => $this->aksiKirim($request, $pesanan, $lunas, $punyaKolomStatus, $statusPesanan),
            'selesai' => $this->aksiSelesai($pesanan, $punyaKolomStatus, $statusPesanan),
            default => null,
        };

        if (! $pesan) {
            return redirect()
                ->route('admin.pengiriman.index')
                ->with('error', 'Aksi pengiriman tidak dapat diterapkan pada pesanan ini.');
        }

        $pesanan->save();

        return redirect()
            ->route('admin.pengiriman.index')
            ->with('status', $pesan);
    }

    private function aksiKirim(Request $request, PesananProduk $pesanan, bool $lunas, bool $punyaKolomStatus, string $statusPesanan): ?string
    {
        if (! $lunas || in_array($statusPesanan, ['dikirim', 'selesai'], true)) {
            return null;
        }

        // Kurir opsional: bisa dikirim tanpa kurir tercatat
        $kurirId = $request->validate([
            'kurir_id' => ['nullable', 'integer', 'exists:karyawan,id'],
        ])['kurir_id'] ?? null;

        $pesanan->kurir_id = $kurirId;
        $pesanan->tanggal_kirim = now()->toDateString();

        if ($punyaKolomStatus) {
            $pesanan->status_pesanan = 'dikirim';
        }

        return "Pesanan {$pesanan->kode} ditandai sudah dikirim.";
    }

    private function aksiSelesai(PesananProduk $pesanan, bool $punyaKolomStatus, string $statusPesanan): ?string
    {
        if ($statusPesanan !== 'dikirim') {
            return null;
        }

        if ($punyaKolomStatus) {
            $pesanan->status_pesanan = 'selesai';
        }
        if (Schema::hasColumn((new PesananProduk)->getTable(), 'tanggal_selesai')) {
            $pesanan->tanggal_selesai = now();
        }

        return "Pesanan {$pesanan->kode} ditandai selesai.";
    }
}
