<?php

namespace Tests\Feature;

use App\Models\Akun;
use App\Models\PesananProduk;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ReservasiStokTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();

        Schema::create('akun', function (Blueprint $table): void {
            $table->id();
            $table->string('nama');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('no_hp')->nullable();
            $table->string('role');
            $table->string('remember_token')->nullable();
        });

        Schema::create('customer', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('akun_id');
            $table->string('alamat')->nullable();
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

        Schema::create('pesanan_produk', function (Blueprint $table): void {
            $table->id();
            $table->string('kode')->unique();
            $table->string('invoice_number')->nullable()->unique();
            $table->string('snap_token')->nullable();
            $table->string('midtrans_order_id')->nullable();
            $table->unsignedBigInteger('customer_id');
            $table->date('tanggal_pesan');
            $table->date('tanggal_bayar')->nullable();
            $table->string('alamat_pengiriman');
            $table->unsignedInteger('total_bayar')->default(0);
            $table->unsignedInteger('ongkir')->default(0);
            $table->string('status_pembayaran')->default('unpaid');
            $table->string('status_pesanan')->default('menunggu_pembayaran');
        });

        Schema::create('detail_pesanan_produk', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('pesanan_produk_id');
            $table->unsignedBigInteger('produk_id')->nullable();
            $table->unsignedInteger('qty')->default(1);
            $table->unsignedInteger('harga')->default(0);
            $table->unsignedInteger('subtotal')->default(0);
        });

        Schema::create('reservasi_stok', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('pesanan_produk_id');
            $table->unsignedBigInteger('produk_id');
            $table->unsignedInteger('qty')->default(1);
            $table->timestamp('kedaluwarsa_pada');
            $table->timestamp('created_at')->nullable();
        });
    }

    public function test_checkout_reserves_stock_but_does_not_decrement_physical_stock(): void
    {
        [$customer, $akun] = $this->buatCustomer('Pembeli Pertama');
        $produk = $this->buatProduk('Monstera Terakhir', 100000, 1);

        $this->withSession(['cart' => [$produk => 1]])
            ->actingAs($akun)
            ->post(route('checkout.store'), ['alamat_pengiriman' => 'Jl. Mawar 1'])
            ->assertRedirect();

        $this->assertDatabaseHas('reservasi_stok', [
            'produk_id' => $produk,
            'qty' => 1,
        ]);

        // Stok fisik belum berubah sampai pembayaran lunas.
        $this->assertSame(1, (int) DB::table('produk')->where('id', $produk)->value('stok'));
    }

    public function test_second_customer_cannot_buy_the_same_reserved_stock(): void
    {
        [, $pembeliA] = $this->buatCustomer('Pembeli A');
        [, $pembeliB] = $this->buatCustomer('Pembeli B');
        $produk = $this->buatProduk('Hanya Satu', 50000, 1);

        $this->withSession(['cart' => [$produk => 1]])
            ->actingAs($pembeliA)
            ->post(route('checkout.store'), ['alamat_pengiriman' => 'Jl. A'])
            ->assertRedirect();

        $this->withSession(['cart' => [$produk => 1]])
            ->actingAs($pembeliB)
            ->post(route('checkout.store'), ['alamat_pengiriman' => 'Jl. B'])
            ->assertRedirect(route('keranjang.index'))
            ->assertSessionHas('error');

        $this->assertSame(1, PesananProduk::query()->count());
        $this->assertSame(1, (int) DB::table('produk')->where('id', $produk)->value('stok'));
    }

    public function test_payment_success_decrements_stock_and_releases_the_reservation(): void
    {
        [$customer, $akun] = $this->buatCustomer('Pembeli Lunas');
        $produk = $this->buatProduk('Calathea', 60000, 2);

        $this->withSession(['cart' => [$produk => 2]])
            ->actingAs($akun)
            ->post(route('checkout.store'), ['alamat_pengiriman' => 'Jl. C'])
            ->assertRedirect();

        $pesanan = PesananProduk::query()->firstOrFail();

        $this->actingAs($akun)
            ->get(route('payment.success', ['tipe' => 'produk', 'pesanan' => $pesanan->id]))
            ->assertRedirect(route('pesanan.show', $pesanan->id));

        $this->assertSame('paid', PesananProduk::findOrFail($pesanan->id)->status_pembayaran);
        $this->assertSame(0, (int) DB::table('produk')->where('id', $produk)->value('stok'));
        $this->assertSame(0, DB::table('reservasi_stok')->count());
    }

    public function test_cancelling_an_order_releases_the_reservation(): void
    {
        [$customer, $akun] = $this->buatCustomer('Pembeli Batal');
        $produk = $this->buatProduk('Puring', 40000, 1);

        $this->withSession(['cart' => [$produk => 1]])
            ->actingAs($akun)
            ->post(route('checkout.store'), ['alamat_pengiriman' => 'Jl. D'])
            ->assertRedirect();

        $pesanan = PesananProduk::query()->firstOrFail();

        $this->actingAs($akun)
            ->post(route('pesanan.batalkan', $pesanan))
            ->assertSessionHas('status');

        $this->assertSame(0, DB::table('reservasi_stok')->count());
        $this->assertSame(1, (int) DB::table('produk')->where('id', $produk)->value('stok'));
    }

    public function test_expired_reservations_no_longer_block_stock(): void
    {
        [$customer, $akun] = $this->buatCustomer('Pembeli Baru');
        $produk = $this->buatProduk('Lidah Mertua', 30000, 1);

        DB::table('reservasi_stok')->insert([
            'pesanan_produk_id' => 999,
            'produk_id' => $produk,
            'qty' => 1,
            'kedaluwarsa_pada' => now()->subMinute(),
            'created_at' => now()->subDay(),
        ]);

        $this->withSession(['cart' => [$produk => 1]])
            ->actingAs($akun)
            ->post(route('checkout.store'), ['alamat_pengiriman' => 'Jl. E'])
            ->assertRedirect();

        $this->assertSame(1, PesananProduk::query()->count());
    }

    private function buatCustomer(string $nama): array
    {
        $akun = new Akun([
            'nama' => $nama,
            'email' => strtolower(str_replace(' ', '.', $nama)).'.'.uniqid().'@example.test',
            'password' => Hash::make('password'),
        ]);
        $akun->role = 'customer';
        $akun->save();

        $customerId = DB::table('customer')->insertGetId([
            'akun_id' => $akun->id,
            'alamat' => 'Jl. Anggrek 5',
        ]);

        return [$customerId, $akun];
    }

    private function buatProduk(string $nama, int $harga, int $stok): int
    {
        return DB::table('produk')->insertGetId([
            'nama' => $nama,
            'harga' => $harga,
            'stok' => $stok,
        ]);
    }
}
