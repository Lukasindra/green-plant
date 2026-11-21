<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CustomerKatalogTest extends TestCase
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

        Schema::create('jasa', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('kategori_id')->nullable();
            $table->string('nama');
            $table->text('deskripsi')->nullable();
            $table->unsignedInteger('harga');
            $table->string('satuan');
            $table->string('foto')->nullable();
        });

        DB::table('kategori')->insert(['nama' => 'Tanaman Hias', 'tipe' => 'produk']);

        DB::table('produk')->insert([
            ['kategori_id' => 1, 'nama' => 'Monstera Deliciosa', 'deskripsi' => 'Tanaman daun besar khas tropis', 'harga' => 120000, 'stok' => 10, 'foto' => null],
            ['kategori_id' => 1, 'nama' => 'Palem Ekor Tupai', 'deskripsi' => 'Palem indah untuk pekarangan', 'harga' => 250000, 'stok' => 5, 'foto' => null],
        ]);

        DB::table('jasa')->insert([
            ['kategori_id' => null, 'nama' => 'Perawatan Rutin', 'deskripsi' => 'Kunjungan penyiraman dan pemangkasan', 'harga' => 150000, 'satuan' => 'kunjungan', 'foto' => null],
            ['kategori_id' => null, 'nama' => 'Pembuatan Taman', 'deskripsi' => 'Taman baru dari nol', 'harga' => 2000000, 'satuan' => 'proyek', 'foto' => null],
        ]);
    }

    public function test_pencarian_produk_dengan_kata_kunci(): void
    {
        $this->get('/produk?q=monstera')
            ->assertOk()
            ->assertSee('Monstera Deliciosa')
            ->assertDontSee('Palem Ekor Tupai');
    }

    public function test_pencarian_produk_cocok_dengan_deskripsi(): void
    {
        $this->get('/produk?q=tropis')
            ->assertOk()
            ->assertSee('Monstera Deliciosa')
            ->assertDontSee('Palem Ekor Tupai');
    }

    public function test_pencarian_jasa_dengan_kata_kunci(): void
    {
        $this->get('/jasa?q=perawatan')
            ->assertOk()
            ->assertSee('Perawatan Rutin')
            ->assertDontSee('Pembuatan Taman');
    }
}
