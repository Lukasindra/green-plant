<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class KeranjangTest extends TestCase
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

    public function test_it_shows_an_empty_cart(): void
    {
        $response = $this->get(route('keranjang.index'));

        $response->assertOk()
            ->assertSee('Keranjang Anda masih kosong')
            ->assertSee(route('produk.index'));
    }

    public function test_it_shows_cart_products_and_subtotal(): void
    {
        $productId = $this->createProduct('Monstera', 25000, 5);

        $response = $this->withSession(['cart' => [$productId => 2]])
            ->get(route('keranjang.index'));

        $response->assertOk()
            ->assertSee('Monstera')
            ->assertSee('Rp 50.000')
            ->assertSee('2');
    }

    public function test_it_updates_a_cart_product_quantity(): void
    {
        $productId = $this->createProduct('Sansevieria', 75000, 4);

        $response = $this->from(route('keranjang.index'))
            ->withSession(['cart' => [$productId => 1]])
            ->patch(route('keranjang.update', $productId), ['jumlah' => 3]);

        $response->assertRedirect(route('keranjang.index'))
            ->assertSessionHas('cart', [$productId => 3])
            ->assertSessionHas('status');
    }

    public function test_it_rejects_a_quantity_above_available_stock(): void
    {
        $productId = $this->createProduct('Calathea', 50000, 2);

        $response = $this->from(route('keranjang.index'))
            ->withSession(['cart' => [$productId => 1]])
            ->patch(route('keranjang.update', $productId), ['jumlah' => 3]);

        $response->assertRedirect(route('keranjang.index'))
            ->assertSessionHas('cart', [$productId => 1])
            ->assertSessionHas('error');
    }

    public function test_it_removes_a_product_and_can_clear_the_cart(): void
    {
        $firstProductId = $this->createProduct('Monstera', 25000, 5);
        $secondProductId = $this->createProduct('Sansevieria', 75000, 4);

        $removeResponse = $this->from(route('keranjang.index'))
            ->withSession(['cart' => [$firstProductId => 1, $secondProductId => 2]])
            ->delete(route('keranjang.destroy', $firstProductId));

        $removeResponse->assertRedirect(route('keranjang.index'))
            ->assertSessionHas('cart', [$secondProductId => 2]);

        $clearResponse = $this->from(route('keranjang.index'))
            ->delete(route('keranjang.clear'));

        $clearResponse->assertRedirect(route('keranjang.index'))
            ->assertSessionMissing('cart');
    }

    private function createProduct(string $name, int $price, int $stock): int
    {
        $categoryId = DB::table('kategori')->insertGetId([
            'nama' => 'Tanaman Hias',
            'tipe' => 'produk',
        ]);

        return DB::table('produk')->insertGetId([
            'kategori_id' => $categoryId,
            'nama' => $name,
            'deskripsi' => null,
            'harga' => $price,
            'stok' => $stock,
            'foto' => null,
        ]);
    }
}
