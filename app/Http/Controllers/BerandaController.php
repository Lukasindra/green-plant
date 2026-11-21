<?php

namespace App\Http\Controllers;

use App\Models\Jasa;
use App\Models\Produk;
use Illuminate\Support\Facades\Schema;

class BerandaController extends Controller
{
    public function __invoke()
    {
        // Guard defensif: bila tabel katalog belum ada, tampilkan beranda kosong
        if (! Schema::hasTable('produk') || ! Schema::hasTable('jasa')) {
            return view('beranda.index', [
                'produk' => collect(),
                'jasa'   => collect(),
            ]);
        }

        return view('beranda.index', [
            // Katalog terbaru untuk section unggulan beranda
            'produk' => Produk::with('kategori')->orderByDesc('id')->limit(8)->get(),
            'jasa'   => Jasa::with('kategori')->orderByDesc('id')->limit(4)->get(),
        ]);
    }
}
