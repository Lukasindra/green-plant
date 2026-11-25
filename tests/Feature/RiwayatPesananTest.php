<?php

namespace Tests\Feature;

use App\Mail\PesananMail;
use App\Models\Akun;
use App\Models\PesananProduk;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class RiwayatPesananTest extends TestCase
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
            $table->string('nama');
            $table->unsignedInteger('harga');
            $table->unsignedInteger('stok');
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

        Schema::create('riwayat_pesanan', function (Blueprint $table): void {
            $table->id();
            $table->string('pesanan_type');
            $table->unsignedBigInteger('pesanan_id');
            $table->string('status');
            $table->text('keterangan')->nullable();
            $table->unsignedBigInteger('aktor_id')->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }

    public function test_checkout_records_creation_history_and_sends_email(): void
    {
        [$customer, $akun] = $this->buatCustomer('Rina');
        $produk = $this->buatProduk('Aglaonema', 90000, 5);

        $this->withSession(['cart' => [$produk => 1]])
            ->actingAs($akun)
            ->post(route('checkout.store'), ['alamat_pengiriman' => 'Jl. Melati 1'])
            ->assertRedirect();

        $pesanan = PesananProduk::query()->firstOrFail();

        $this->assertDatabaseHas('riwayat_pesanan', [
            'pesanan_type' => PesananProduk::class,
            'pesanan_id' => $pesanan->id,
            'status' => 'dibuat',
            'aktor_id' => $akun->id,
        ]);

        Mail::assertSent(PesananMail::class, fn (PesananMail $mail): bool => $mail->jenis === 'dibuat');
    }

    public function test_order_page_shows_the_history_timeline(): void
    {
        [$customer, $akun] = $this->buatCustomer('Sinta');
        $produk = $this->buatProduk('Begonia', 70000, 5);

        $this->withSession(['cart' => [$produk => 1]])
            ->actingAs($akun)
            ->post(route('checkout.store'), ['alamat_pengiriman' => 'Jl. Melati 2'])
            ->assertRedirect();

        $pesanan = PesananProduk::query()->firstOrFail();

        $this->withoutVite()
            ->actingAs($akun)
            ->get(route('pesanan.show', $pesanan))
            ->assertOk()
            ->assertSee('Riwayat Pesanan')
            ->assertSee('Pesanan dibuat');
    }

    public function test_payment_success_adds_a_paid_history_entry(): void
    {
        [$customer, $akun] = $this->buatCustomer('Tono');
        $produk = $this->buatProduk('Philodendron', 80000, 5);

        $this->withSession(['cart' => [$produk => 1]])
            ->actingAs($akun)
            ->post(route('checkout.store'), ['alamat_pengiriman' => 'Jl. Melati 3'])
            ->assertRedirect();

        $pesanan = PesananProduk::query()->firstOrFail();

        $this->actingAs($akun)
            ->get(route('payment.success', ['tipe' => 'produk', 'pesanan' => $pesanan->id]))
            ->assertRedirect();

        $this->assertDatabaseHas('riwayat_pesanan', [
            'pesanan_type' => PesananProduk::class,
            'pesanan_id' => $pesanan->id,
            'status' => 'lunas',
        ]);

        Mail::assertSent(PesananMail::class, fn (PesananMail $mail): bool => $mail->jenis === 'lunas');
    }

    public function test_customer_cancellation_adds_a_cancelled_history_entry(): void
    {
        [$customer, $akun] = $this->buatCustomer('Umar');
        $produk = $this->buatProduk('Sansevieria', 50000, 5);

        $this->withSession(['cart' => [$produk => 1]])
            ->actingAs($akun)
            ->post(route('checkout.store'), ['alamat_pengiriman' => 'Jl. Melati 4'])
            ->assertRedirect();

        $pesanan = PesananProduk::query()->firstOrFail();

        $this->actingAs($akun)
            ->post(route('pesanan.batalkan', $pesanan))
            ->assertSessionHas('status');

        $this->assertDatabaseHas('riwayat_pesanan', [
            'pesanan_type' => PesananProduk::class,
            'pesanan_id' => $pesanan->id,
            'status' => 'dibatalkan',
        ]);

        Mail::assertSent(PesananMail::class, fn (PesananMail $mail): bool => $mail->jenis === 'dibatalkan');
    }

    private function buatCustomer(string $nama): array
    {
        $akun = new Akun([
            'nama' => $nama,
            'email' => strtolower($nama).'.'.uniqid().'@example.test',
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
