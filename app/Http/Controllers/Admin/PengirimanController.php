<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Karyawan;
use App\Models\PesananProduk;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PengirimanController extends Controller
{
    // Tab -> nilai pesanan_produk.status_pesanan
    private const TAB = [
        'siap' => 'dibayar',
        'dikirim' => 'dikirim',
        'selesai' => 'selesai',
    ];

    public function index(Request $request): View
    {
        abort_unless($request->user()?->isAdmin(), 403);

        $status = in_array($request->query('status'), array_keys(self::TAB), true)
            ? $request->query('status')
            : 'siap';

        $query = PesananProduk::query()
            ->with(['customer.akun', 'detail', 'kurir'])
            ->where('status_pesanan', self::TAB[$status])
            ->orderByDesc('id');

        return view('admin.pengiriman.index', [
            'pesanan' => $query->paginate(15)->withQueryString(),
            'daftarKurir' => Karyawan::query()
                ->whereHas('akun', fn ($akun) => $akun->where('role', 'kurir'))
                ->orderBy('username')
                ->get(),
            'status' => $status,
        ]);
    }

    public function aksi(Request $request, PesananProduk $pesanan): RedirectResponse
    {
        abort_unless($request->user()?->isAdmin(), 403);

        $pesan = match ($request->input('aksi')) {
            'kirim' => $this->aksiKirim($request, $pesanan),
            'selesai' => $this->aksiSelesai($pesanan),
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

    private function aksiKirim(Request $request, PesananProduk $pesanan): ?string
    {
        // Hanya pesanan yang sudah dibayar (dan belum dikirim) yang boleh dikirim
        if ($pesanan->status_pesanan !== 'dibayar') {
            return null;
        }

        // Kurir opsional: bisa dikirim tanpa kurir tercatat
        $kurirId = $request->validate([
            'kurir_id' => ['nullable', 'integer', 'exists:karyawan,id'],
        ])['kurir_id'] ?? null;

        $pesanan->kurir_id = $kurirId;
        $pesanan->tanggal_kirim = now()->toDateString();
        $pesanan->status_pesanan = 'dikirim';

        return "Pesanan {$pesanan->kode} ditandai sudah dikirim.";
    }

    private function aksiSelesai(PesananProduk $pesanan): ?string
    {
        if ($pesanan->status_pesanan !== 'dikirim') {
            return null;
        }

        $pesanan->status_pesanan = 'selesai';

        return "Pesanan {$pesanan->kode} ditandai selesai.";
    }
}
