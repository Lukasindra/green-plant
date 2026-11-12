<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    // Halaman beranda membaca katalog, jadi skema minimal dibangun manual
    // mengikuti pola test lain di proyek ini (proyek tidak memakai migrasi).
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
    }

    /**
     * Halaman utama (beranda) tampil normal tanpa data.
     */
    public function test_the_application_renders_the_landing_page(): void
    {
        $response = $this->get('/');

        $response->assertOk();
    }
}
