<?php

namespace Tests\Feature;

use App\Models\Akun;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class InvoiceTest extends TestCase
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

        Schema::create('produk', function (Blueprint $table): void {
            $table->id();
            $table->string('nama');
            $table->unsignedInteger('harga');
            $table->unsignedInteger('stok');
        });

        Schema::create('jasa', function (Blueprint $table): void {
            $table->id();
            $table->string('nama');
            $table->unsignedInteger('harga');
            $table->string('satuan')->nullable();
        });

        Schema::create('pesanan_produk', function (Blueprint $table): void {
            $table->id();
            $table->string('kode')->unique();
            $table->string('invoice_number')->nullable()->unique();
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

        Schema::create('pesanan_jasa', function (Blueprint $table): void {
            $table->id();
            $table->string('kode')->unique();
            $table->string('invoice_number')->nullable()->unique();
            $table->unsignedBigInteger('customer_id');
            $table->date('tanggal_pesan');
            $table->date('tanggal_bayar')->nullable();
            $table->string('alamat_lokasi');
            $table->text('catatan')->nullable();
            $table->unsignedInteger('total_bayar')->default(0);
            $table->string('status_pembayaran')->default('unpaid');
            $table->string('status_pesanan')->default('menunggu_pembayaran');
        });

        Schema::create('detail_pesanan_jasa', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('pesanan_jasa_id');
            $table->unsignedBigInteger('jasa_id')->nullable();
            $table->unsignedInteger('qty')->default(1);
            $table->unsignedInteger('harga')->default(0);
            $table->unsignedInteger('subtotal')->default(0);
        });
    }

    public function test_order_owner_can_open_the_product_invoice(): void
    {
        [$customer, $akun] = $this->buatCustomer('Pemilik Invoice');
        $id = $this->buatPesananProduk($customer, 'INV-20261126-P01', 125000);

        $this->withoutVite()
            ->actingAs($akun)
            ->get(route('pesanan.invoice', $id))
            ->assertOk()
            ->assertSee('INV-20261126-P01')
            ->assertSee('Pemilik Invoice')
            ->assertSee('Rp 125.000');
    }

    public function test_other_customers_cannot_open_someone_elses_invoice(): void
    {
        [$customer] = $this->buatCustomer('Pemilik');
        [, $penyusup] = $this->buatCustomer('Penyusup');
        $id = $this->buatPesananProduk($customer, 'INV-20261126-P02', 50000);

        $this->actingAs($penyusup)
            ->get(route('pesanan.invoice', $id))
            ->assertForbidden();
    }

    public function test_admin_can_open_the_invoice(): void
    {
        [$customer] = $this->buatCustomer('Pelanggan Admin');
        $id = $this->buatPesananProduk($customer, 'INV-20261126-P03', 90000);
        $admin = $this->buatAkun('Admin', 'admin');

        $this->withoutVite()
            ->actingAs($admin)
            ->get(route('admin.pesanan.produk.invoice', $id))
            ->assertOk()
            ->assertSee('INV-20261126-P03');
    }

    public function test_service_order_owner_can_open_the_service_invoice(): void
    {
        [$customer, $akun] = $this->buatCustomer('Pelanggan Jasa');
        $id = $this->buatPesananJasa($customer, 'INV-20261126-J01', 450000);

        $this->withoutVite()
            ->actingAs($akun)
            ->get(route('pesanan-jasa.invoice', $id))
            ->assertOk()
            ->assertSee('INV-20261126-J01')
            ->assertSee('Perawatan Taman');
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get(route('pesanan.invoice', 1))->assertRedirect(route('login'));
    }

    private function buatAkun(string $nama, string $role): Akun
    {
        $akun = new Akun([
            'nama' => $nama,
            'email' => strtolower($nama).'.'.uniqid().'@example.test',
            'password' => Hash::make('password'),
        ]);
        $akun->role = $role;
        $akun->save();

        return $akun;
    }

    private function buatCustomer(string $nama): array
    {
        $akun = $this->buatAkun($nama, 'customer');
        $customerId = DB::table('customer')->insertGetId([
            'akun_id' => $akun->id,
            'alamat' => 'Jl. Anggrek 5',
        ]);

        return [$customerId, $akun];
    }

    private function buatPesananProduk(int $customerId, string $invoice, int $total): int
    {
        $produk = DB::table('produk')->insertGetId([
            'nama' => 'Monstera',
            'harga' => $total - 15000,
            'stok' => 5,
        ]);

        $id = DB::table('pesanan_produk')->insertGetId([
            'kode' => 'PRD-'.uniqid(),
            'invoice_number' => $invoice,
            'customer_id' => $customerId,
            'tanggal_pesan' => now()->toDateString(),
            'alamat_pengiriman' => 'Jl. Anggrek 5',
            'total_bayar' => $total,
            'ongkir' => 15000,
        ]);

        DB::table('detail_pesanan_produk')->insert([
            'pesanan_produk_id' => $id,
            'produk_id' => $produk,
            'qty' => 1,
            'harga' => $total - 15000,
            'subtotal' => $total - 15000,
        ]);

        return $id;
    }

    private function buatPesananJasa(int $customerId, string $invoice, int $total): int
    {
        $jasa = DB::table('jasa')->insertGetId([
            'nama' => 'Perawatan Taman',
            'harga' => $total,
        ]);

        $id = DB::table('pesanan_jasa')->insertGetId([
            'kode' => 'JSA-'.uniqid(),
            'invoice_number' => $invoice,
            'customer_id' => $customerId,
            'tanggal_pesan' => now()->toDateString(),
            'alamat_lokasi' => 'Jl. Anggrek 5',
            'total_bayar' => $total,
        ]);

        DB::table('detail_pesanan_jasa')->insert([
            'pesanan_jasa_id' => $id,
            'jasa_id' => $jasa,
            'qty' => 1,
            'harga' => $total,
            'subtotal' => $total,
        ]);

        return $id;
    }
}
