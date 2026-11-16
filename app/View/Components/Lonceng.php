<?php

namespace App\View\Components;

use App\Support\Notifikasi;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Lonceng extends Component
{
    public bool $tampil = false;

    public int $total = 0;

    /** @var list<array{judul: string, keterangan: string, jumlah: int, url: string}> */
    public array $item = [];

    public function __construct()
    {
        $akun = auth()->user();

        // Lonceng hanya untuk admin dan kurir
        if ($akun && ($akun->isAdmin() || $akun->isKurir())) {
            $hasil = Notifikasi::untuk($akun);

            $this->tampil = true;
            $this->total = $hasil['total'];
            $this->item = $hasil['item'];
        }
    }

    public function render(): View
    {
        return view('components.lonceng');
    }
}
