<?php

namespace Tests\Feature;

use App\Models\Akun;
use App\Models\PesananProduk;
use App\Models\Produk;
use App\Services\MidtransService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CheckoutMidtransTest extends TestCase
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

        Schema::create('customer', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('akun_id');
            $table->string('alamat')->nullable();
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

        Schema::create('pesanan_produk', function (Blueprint $table): void {
            $table->id();
            $table->string('kode')->unique();
            $table->string('invoice_number')->nullable()->unique();
            $table->string('snap_token')->nullable();
            $table->unsignedBigInteger('customer_id');
            $table->unsignedBigInteger('admin_id')->nullable();
            $table->unsignedBigInteger('kurir_id')->nullable();
            $table->date('tanggal_pesan');
            $table->date('tanggal_bayar')->nullable();
            $table->date('tanggal_kirim')->nullable();
            $table->string('metode_pembayaran')->nullable();
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
    }

    public function test_checkout_creates_order_unpaid_with_snap_token_and_redirects_to_payment(): void
    {
        [$customer, $akun] = $this->buatCustomer('Budi Santoso');
        $produk = DB::table('produk')->insertGetId([
            'nama' => 'Monstera Deliciosa',
            'harga' => 100000,
            'stok' => 10,
        ]);

        $this->withSession(['cart' => [$produk => 2]])
            ->actingAs($akun)
            ->post(route('checkout.store'), [
                'alamat_pengiriman' => 'Jl. Mawar No. 10, Jakarta Selatan',
            ])
            ->assertRedirect();

        $pesanan = PesananProduk::query()->firstOrFail();
        $this->assertSame('unpaid', $pesanan->status_pembayaran);
        $this->assertSame('menunggu_pembayaran', $pesanan->status_pesanan);
        $this->assertSame(215000, (int) $pesanan->total_bayar); // 2 * 100k + 15k ongkir bawaan
        $this->assertMatchesRegularExpression('/^INV-\d{8}-[A-Z0-9]{4,}$/', $pesanan->invoice_number);
        $this->assertNotNull($pesanan->snap_token);
        $this->assertStringStartsWith('snap-token-test-', $pesanan->snap_token);

        // Keranjang dikosongkan setelah checkout
        $this->assertNull(session('cart'));

        // Stok BELUM berkurang saat checkout (baru berkurang setelah pembayaran berhasil dikonfirmasi)
        $this->assertSame(10, (int) DB::table('produk')->where('id', $produk)->value('stok'));
    }

    public function test_payment_show_renders_snap_page_for_order_owner(): void
    {
        [$customer, $akun] = $this->buatCustomer('Citra Dewi');
        $id = $this->buatPesananProduk($customer, 'PSN-P-901', 115000, [
            'invoice_number' => 'INV-20261118-CD01',
            'snap_token'     => 'snap-token-test-901',
        ]);

        $this->withoutVite()
            ->actingAs($akun)
            ->get(route('payment.show', ['tipe' => 'produk', 'pesanan' => $id]))
            ->assertOk()
            ->assertSee('INV-20261118-CD01')
            ->assertSee('Bayar via Midtrans Snap')
            ->assertSee('snap-token-test-901');
    }

    public function test_payment_show_forbidden_for_other_users(): void
    {
        [$customer, $akun] = $this->buatCustomer('Pemilik');
        [, $penyusup]      = $this->buatCustomer('Penyusup');
        $id = $this->buatPesananProduk($customer, 'PSN-P-902', 50000);

        $this->actingAs($penyusup)
            ->get(route('payment.show', ['tipe' => 'produk', 'pesanan' => $id]))
            ->assertForbidden();
    }

    public function test_retry_payment_overwrites_snap_token_without_creating_new_order_record(): void
    {
        [$customer, $akun] = $this->buatCustomer('Dedi Gunawan');
        $id = $this->buatPesananProduk($customer, 'PSN-P-903', 150000, [
            'invoice_number'    => 'INV-20261118-DG01',
            'snap_token'        => 'snap-token-lama',
            'status_pembayaran' => 'failed',
        ]);

        $this->actingAs($akun)
            ->post(route('payment.retry', ['tipe' => 'produk', 'pesanan' => $id]))
            ->assertRedirect(route('payment.show', ['tipe' => 'produk', 'pesanan' => $id]))
            ->assertSessionHas('status');

        $pesanan = PesananProduk::findOrFail($id);
        $this->assertSame('unpaid', $pesanan->status_pembayaran);
        $this->assertSame('INV-20261118-DG01', $pesanan->invoice_number);
        $this->assertNotSame('snap-token-lama', $pesanan->snap_token);

        // Jangan buat baris order baru
        $this->assertDatabaseCount('pesanan_produk', 1);
    }

    public function test_payment_success_marks_order_paid_and_decrements_product_stock(): void
    {
        [$customer, $akun] = $this->buatCustomer('Eka Kurniawan');
        $produk = DB::table('produk')->insertGetId([
            'nama' => 'Puring',
            'harga' => 50000,
            'stok' => 8,
        ]);

        $id = $this->buatPesananProduk($customer, 'PSN-P-904', 115000, [
            'invoice_number' => 'INV-20261118-EK01',
            'snap_token'     => 'snap-token-test-904',
        ]);

        DB::table('detail_pesanan_produk')->insert([
            'pesanan_produk_id' => $id,
            'produk_id'         => $produk,
            'qty'               => 2,
            'harga'             => 50000,
            'subtotal'          => 100000,
        ]);

        $this->actingAs($akun)
            ->get(route('payment.success', ['tipe' => 'produk', 'pesanan' => $id]))
            ->assertRedirect(route('pesanan.show', $id))
            ->assertSessionHas('status');

        $pesanan = PesananProduk::findOrFail($id);
        $this->assertSame('paid', $pesanan->status_pembayaran);
        $this->assertSame('dibayar', $pesanan->status_pesanan);
        $this->assertNotNull($pesanan->tanggal_bayar);

        // Stok produk terpotong (8 - 2 = 6)
        $this->assertSame(6, (int) DB::table('produk')->where('id', $produk)->value('stok'));

        // Dipanggil kedua kali (misal user refresh) tidak memotong stok lagi
        $this->actingAs($akun)
            ->get(route('payment.success', ['tipe' => 'produk', 'pesanan' => $id]))
            ->assertRedirect(route('pesanan.show', $id));

        $this->assertSame(6, (int) DB::table('produk')->where('id', $produk)->value('stok'));
    }

    public function test_checkout_keeps_single_order_and_clears_cart_when_snap_token_fails(): void
    {
        [$customer, $akun] = $this->buatCustomer('Fajar Nugroho');
        $produk = DB::table('produk')->insertGetId([
            'nama'  => 'Kaktus',
            'harga' => 40000,
            'stok'  => 5,
        ]);

        $this->mock(MidtransService::class, function ($mock): void {
            $mock->shouldReceive('snapToken')
                ->once()
                ->andThrow(new \RuntimeException('Kredensial Midtrans belum diisi.'));
        });

        $this->withSession(['cart' => [$produk => 1]])
            ->actingAs($akun)
            ->post(route('checkout.store'), [
                'alamat_pengiriman' => 'Jl. Kenanga No. 1',
            ])
            ->assertRedirect(route('payment.show', ['tipe' => 'produk', 'pesanan' => 1]))
            ->assertSessionHas('error', 'Kredensial Midtrans belum diisi.');

        // Hanya satu order, dan keranjang sudah kosong supaya tidak checkout ganda
        $this->assertDatabaseCount('pesanan_produk', 1);
        $this->assertNull(session('cart'));
        $this->assertNull(PesananProduk::query()->firstOrFail()->snap_token);
        $this->assertSame('unpaid', PesananProduk::query()->firstOrFail()->status_pembayaran);
    }

    public function test_payment_success_does_not_mark_a_cancelled_order_as_paid(): void
    {
        [$customer, $akun] = $this->buatCustomer('Gita Permata');
        $id = $this->buatPesananProduk($customer, 'PSN-P-905', 75000, [
            'status_pesanan' => 'dibatalkan',
        ]);

        $this->actingAs($akun)
            ->get(route('payment.success', ['tipe' => 'produk', 'pesanan' => $id]))
            ->assertRedirect(route('pesanan.show', $id))
            ->assertSessionHas('error');

        $pesanan = PesananProduk::findOrFail($id);
        $this->assertSame('unpaid', $pesanan->status_pembayaran);
        $this->assertSame('dibatalkan', $pesanan->status_pesanan);
        $this->assertNull($pesanan->tanggal_bayar);
    }

    private function buatCustomer(string $nama): array
    {
        $akun = new Akun([
            'nama'     => $nama,
            'email'    => strtolower(str_replace(' ', '.', $nama)) . '.' . uniqid() . '@example.test',
            'password' => Hash::make('password'),
        ]);
        $akun->role = 'customer';
        $akun->save();

        $customerId = DB::table('customer')->insertGetId([
            'akun_id' => $akun->id,
            'alamat'  => 'Jl. Anggrek No. 5',
        ]);

        return [$customerId, $akun];
    }

    private function buatPesananProduk(int $customerId, string $kode, int $total, array $tambahan = []): int
    {
        return DB::table('pesanan_produk')->insertGetId(array_merge([
            'kode'              => $kode,
            'customer_id'       => $customerId,
            'tanggal_pesan'     => now()->toDateString(),
            'alamat_pengiriman' => 'Jl. Anggrek No. 5',
            'total_bayar'       => $total,
            'ongkir'            => 15000,
            'status_pembayaran' => 'unpaid',
            'status_pesanan'    => 'menunggu_pembayaran',
        ], $tambahan));
    }
}
