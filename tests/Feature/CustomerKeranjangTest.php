<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CustomerKeranjangTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('kategori', function (Blueprint $table): void {
            $table->id();
            $table->string('nama');
            $table->string('tipe');
        });

        Schema::create('produk', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('kategori_id')->nullable();
            $table->string('nama');
            $table->text('deskripsi')->nullable();
            $table->unsignedInteger('harga');
            $table->unsignedInteger('stok');
            $table->string('foto')->nullable();
        });
    }

    public function test_set_qty_langsung_lewat_aksi_atur(): void
    {
        $id = DB::table('produk')->insertGetId([
            'kategori_id' => null, 'nama' => 'Monstera', 'deskripsi' => null,
            'harga' => 100000, 'stok' => 10, 'foto' => null,
        ]);

        $this->withSession(['cart' => [$id => 1]])
            ->post(route('keranjang.ubah'), ['produk_id' => $id, 'aksi' => 'atur', 'qty' => 3])
            ->assertSessionHas('cart', [$id => 3]);
    }

    public function test_atur_menolak_qty_melebihi_stok(): void
    {
        $id = DB::table('produk')->insertGetId([
            'kategori_id' => null, 'nama' => 'Palem', 'deskripsi' => null,
            'harga' => 250000, 'stok' => 2, 'foto' => null,
        ]);

        $this->withSession(['cart' => [$id => 1]])
            ->post(route('keranjang.ubah'), ['produk_id' => $id, 'aksi' => 'atur', 'qty' => 5])
            ->assertSessionHas('error')
            ->assertSessionHas('cart', [$id => 1]);
    }

    public function test_atur_qty_nol_ditolak_validasi(): void
    {
        $id = DB::table('produk')->insertGetId([
            'kategori_id' => null, 'nama' => 'Suku-suku', 'deskripsi' => null,
            'harga' => 50000, 'stok' => 8, 'foto' => null,
        ]);

        $this->withSession(['cart' => [$id => 1]])
            ->post(route('keranjang.ubah'), ['produk_id' => $id, 'aksi' => 'atur', 'qty' => 0])
            ->assertSessionHasErrors('qty');
    }
}
