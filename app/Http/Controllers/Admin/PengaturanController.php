<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Pengaturan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

/**
 * Pengaturan toko. Pembayaran kini diproses Midtrans Snap, sehingga
 * yang masih bisa diatur admin hanya ongkir.
 */
class PengaturanController extends Controller
{
    private const KUNCI = ['ongkir'];

    public function index(Request $request): View
    {
        return view('admin.pengaturan.index', $this->data());
    }

    public function simpan(Request $request): \Illuminate\Http\RedirectResponse
    {
        if (! Schema::hasTable('pengaturan')) {
            return redirect()
                ->route('admin.pengaturan.index')
                ->with('error', 'Tabel pengaturan belum tersedia. Buat tabel "pengaturan" (kunci, nilai) di database.');
        }

        $data = $request->validate([
            'ongkir' => ['required', 'integer', 'min:0', 'max:1000000'],
        ], [
            'ongkir.required' => 'Ongkir wajib diisi.',
            'ongkir.integer'  => 'Ongkir harus berupa angka.',
            'ongkir.min'      => 'Ongkir tidak boleh minus.',
        ]);

        Pengaturan::updateOrCreate(['kunci' => 'ongkir'], ['nilai' => json_encode($data['ongkir'])]);

        return redirect()
            ->route('admin.pengaturan.index')
            ->with('status', 'Pengaturan toko disimpan.');
    }

    private function data(): array
    {
        // Baca dari tabel pengaturan bila ada, fallback ke config/toko.php
        $tersimpan = Schema::hasTable('pengaturan')
            ? Pengaturan::query()->whereIn('kunci', self::KUNCI)->pluck('nilai', 'kunci')
            : collect();

        $ongkir = $this->bacaTersimpan($tersimpan, 'ongkir', config('toko.ongkir', 0));

        return compact('ongkir');
    }

    private function bacaTersimpan($tersimpan, string $kunci, mixed $bawaan): mixed
    {
        if ($tersimpan->has($kunci)) {
            $nilai = json_decode($tersimpan->get($kunci), true);

            return $nilai ?? $bawaan;   // ongkir 0 (gratis) tetap sah
        }

        return $bawaan;
    }
}
