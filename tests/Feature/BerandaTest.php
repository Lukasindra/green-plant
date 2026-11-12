<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class BerandaTest extends TestCase
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
            'kategori_id' => 1, 'nama' => 'Monstera Deliciosa', 'deskripsi' => 'Daun lebar cantik',
            'harga' => 120000, 'stok' => 10, 'foto' => null,
        ]);

        DB::table('jasa')->insert([
            'kategori_id' => null, 'nama' => 'Perawatan Taman', 'deskripsi' => 'Kunjungan berkala',
            'harga' => 150000, 'satuan' => 'kunjungan', 'foto' => null,
        ]);
    }

    public function test_beranda_menampilkan_produk_dan_jasa_terbaru(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Monstera Deliciosa')
            ->assertSee('Perawatan Taman');
    }

    public function test_beranda_tetap_tampil_bila_tabel_katalog_belum_ada(): void
    {
        Schema::drop('produk');
        Schema::drop('jasa');

        $this->get('/')->assertOk();
    }
}
