<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class PengaturanController extends Controller
{
    private const KUNCI = ['ongkir', 'rekening', 'ewallet'];

    public function index(Request $request): View
    {
        abort_unless($request->user()?->isAdmin(), 403);

        return view('admin.pengaturan.index', $this->data());
    }

    public function simpan(Request $request): \Illuminate\Http\RedirectResponse
    {
        abort_unless($request->user()?->isAdmin(), 403);

        if (! Schema::hasTable('pengaturan')) {
            return redirect()
                ->route('admin.pengaturan.index')
                ->with('error', 'Tabel pengaturan belum tersedia. Buat tabel "pengaturan" (kunci, nilai) di database.');
        }

        $data = $request->validate([
            'ongkir' => ['required', 'integer', 'min:0', 'max:1000000'],
            'rekening' => ['nullable', 'array', 'max:5'],
            'rekening.*.bank' => ['nullable', 'string', 'max:30'],
            'rekening.*.nomor' => ['nullable', 'string', 'max:40'],
            'rekening.*.nama' => ['nullable', 'string', 'max:100'],
            'ewallet' => ['nullable', 'array', 'max:5'],
            'ewallet.*.nama' => ['nullable', 'string', 'max:30'],
            'ewallet.*.nomor' => ['nullable', 'string', 'max:40'],
        ], [
            'ongkir.required' => 'Ongkir wajib diisi.',
            'ongkir.integer' => 'Ongkir harus berupa angka.',
            'ongkir.min' => 'Ongkir tidak boleh minus.',
        ]);

        $rekening = array_values(array_filter(
            $data['rekening'] ?? [],
            fn ($r) => trim($r['bank'] ?? '') !== '' && trim($r['nomor'] ?? '') !== ''
        ));
        $ewallet = array_values(array_filter(
            $data['ewallet'] ?? [],
            fn ($r) => trim($r['nama'] ?? '') !== '' && trim($r['nomor'] ?? '') !== ''
        ));

        if ($rekening === [] && $ewallet === []) {
            return redirect()
                ->route('admin.pengaturan.index')
                ->with('error', 'Minimal isi satu rekening atau e-wallet.')
                ->withInput();
        }

        foreach ([
            'ongkir' => $data['ongkir'],
            'rekening' => $rekening,
            'ewallet' => $ewallet,
        ] as $kunci => $nilai) {
            \App\Models\Pengaturan::updateOrCreate(['kunci' => $kunci], ['nilai' => json_encode($nilai)]);
        }

        return redirect()
            ->route('admin.pengaturan.index')
            ->with('status', 'Pengaturan toko disimpan.');
    }

    private function data(): array
    {
        // Baca dari tabel pengaturan bila ada, fallback ke config/toko.php
        $tersimpan = Schema::hasTable('pengaturan')
            ? \App\Models\Pengaturan::query()->whereIn('kunci', self::KUNCI)->pluck('nilai', 'kunci')
            : collect();

        $ongkir = $this->bacaTersimpan($tersimpan, 'ongkir', config('toko.ongkir', 0));
        $rekening = $this->bacaTersimpan($tersimpan, 'rekening', config('toko.rekening', []));
        $ewallet = $this->bacaTersimpan($tersimpan, 'ewallet', config('toko.ewallet', []));

        return compact('ongkir', 'rekening', 'ewallet');
    }

    private function bacaTersimpan($tersimpan, string $kunci, mixed $bawaan): mixed
    {
        if ($tersimpan->has($kunci)) {
            return json_decode($tersimpan->get($kunci), true) ?: $bawaan;
        }

        return $bawaan;
    }
}
