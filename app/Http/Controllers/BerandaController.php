<?php

namespace App\Http\Controllers;

use App\Models\Jasa;
use App\Models\Produk;

class BerandaController extends Controller
{
    public function __invoke()
    {
        return view('beranda.index', [
            // Katalog terbaru untuk section unggulan beranda
            'produk' => Produk::with('kategori')->orderByDesc('id')->limit(8)->get(),
            'jasa'   => Jasa::with('kategori')->orderByDesc('id')->limit(4)->get(),
        ]);
    }
}
