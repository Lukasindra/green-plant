<?php

namespace Tests\Feature;

use App\Models\Akun;
use App\Models\Produk;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CustomerFavoritTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('akun', function (Blueprint $table): void {
            $table->id();
            $table->string('nama');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('no_hp')->nullable();
            $table->string('role');
            $table->string('remember_token')->nullable();
        });

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
            $table->decimal('harga', 12, 0);
            $table->string('satuan');
            $table->string('foto')->nullable();
        });

        Schema::create('produk_favorit', function (Blueprint $table): void {
            $table->foreignId('akun_id')->constrained('akun')->cascadeOnDelete();
            $table->foreignId('produk_id')->constrained('produk')->cascadeOnDelete();
            $table->timestamps();
            $table->primary(['akun_id', 'produk_id']);
            $table->index('produk_id');
        });
    }

    public function test_guest_cannot_view_or_save_favorites(): void
    {
        $produk = $this->createProduct();

        $this->get(route('favorit.index'))->assertRedirect(route('login'));
        $this->post(route('favorit.produk.store', $produk))->assertRedirect(route('login'));
        $this->get(route('produk.index'))
            ->assertOk()
            ->assertSee('Masuk untuk menyimpan Monstera ke favorit');

        $this->assertDatabaseCount('produk_favorit', 0);
    }

    public function test_customer_can_save_a_product_and_see_its_favorite_state(): void
    {
        $akun = $this->createAccount('Pelanggan');
        $produk = $this->createProduct();

        $this->actingAs($akun)
            ->post(route('favorit.produk.store', $produk))
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->assertDatabaseHas('produk_favorit', [
            'akun_id' => $akun->id,
            'produk_id' => $produk->id,
        ]);

        $this->actingAs($akun)
            ->post(route('favorit.produk.store', $produk))
            ->assertRedirect();

        $this->assertDatabaseCount('produk_favorit', 1);

        $this->actingAs($akun)
            ->get(route('favorit.index'))
            ->assertOk()
            ->assertSee($produk->nama)
            ->assertSee('aria-pressed="true"', false);

        $this->actingAs($akun)
            ->get(route('produk.index'))
            ->assertOk()
            ->assertSee('aria-pressed="true"', false);

        $this->actingAs($akun)
            ->get(route('beranda'))
            ->assertOk()
            ->assertSee('aria-pressed="true"', false);
    }

    public function test_customer_can_remove_a_favorite(): void
    {
        $akun = $this->createAccount('Pelanggan');
        $produk = $this->createProduct();

        DB::table('produk_favorit')->insert([
            'akun_id' => $akun->id,
            'produk_id' => $produk->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($akun)
            ->delete(route('favorit.produk.destroy', $produk))
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->assertDatabaseMissing('produk_favorit', [
            'akun_id' => $akun->id,
            'produk_id' => $produk->id,
        ]);

        $this->actingAs($akun)
            ->get(route('favorit.index'))
            ->assertOk()
            ->assertSee('Belum ada produk favorit.')
            ->assertDontSee($produk->nama);
    }

    public function test_customer_cannot_view_or_remove_another_accounts_favorite(): void
    {
        $pemilik = $this->createAccount('Pemilik');
        $akunLain = $this->createAccount('Pelanggan Lain');
        $produk = $this->createProduct();

        DB::table('produk_favorit')->insert([
            'akun_id' => $pemilik->id,
            'produk_id' => $produk->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($akunLain)
            ->get(route('favorit.index'))
            ->assertOk()
            ->assertDontSee($produk->nama);

        $this->actingAs($akunLain)
            ->delete(route('favorit.produk.destroy', $produk))
            ->assertRedirect();

        $this->assertDatabaseHas('produk_favorit', [
            'akun_id' => $pemilik->id,
            'produk_id' => $produk->id,
        ]);
    }

    public function test_non_customer_cannot_access_favorites(): void
    {
        $admin = $this->createAccount('Admin', 'admin');

        $this->actingAs($admin)
            ->get(route('favorit.index'))
            ->assertForbidden();
    }

    private function createAccount(string $nama, string $role = 'customer'): Akun
    {
        $akun = new Akun([
            'nama' => $nama,
            'email' => strtolower(str_replace(' ', '.', $nama)).'@contoh.test',
            'password' => 'rahasia123',
        ]);
        $akun->role = $role;
        $akun->save();

        return $akun;
    }

    private function createProduct(string $nama = 'Monstera'): Produk
    {
        return Produk::query()->create([
            'kategori_id' => null,
            'nama' => $nama,
            'deskripsi' => 'Tanaman hias.',
            'harga' => 120000,
            'stok' => 10,
            'foto' => null,
        ]);
    }
}
