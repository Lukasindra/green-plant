<?php

namespace Tests\Feature;

use App\Models\Akun;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class BatalkanPesananKedaluwarsaTest extends TestCase
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

        Schema::create('karyawan', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('akun_id');
            $table->string('username');
        });

        Schema::create('pesanan_produk', function (Blueprint $table): void {
            $table->id();
            $table->string('kode')->unique();
            $table->unsignedBigInteger('customer_id');
            $table->unsignedBigInteger('admin_id')->nullable();
            $table->date('tanggal_pesan');
            $table->date('tanggal_bayar')->nullable();
            $table->date('tanggal_kirim')->nullable();
            $table->string('metode_pembayaran');
            $table->string('alamat_pengiriman');
            $table->unsignedInteger('total_bayar')->default(0);
            $table->unsignedInteger('ongkir')->default(0);
            $table->string('status_pembayaran')->default('menunggu_bayar');
            $table->string('status_pesanan')->default('diproses');
            $table->string('bukti_bayar')->nullable();
        });

        Schema::create('pesanan_jasa', function (Blueprint $table): void {
            $table->id();
            $table->string('kode')->unique();
            $table->unsignedBigInteger('customer_id');
            $table->unsignedBigInteger('admin_id')->nullable();
            $table->date('tanggal_pesan');
            $table->date('tanggal_bayar')->nullable();
            $table->string('metode_pembayaran');
            $table->string('alamat_lokasi');
            $table->text('catatan')->nullable();
            $table->unsignedInteger('total_bayar')->default(0);
            $table->string('status_pembayaran')->default('menunggu_bayar');
            $table->string('status_pesanan')->default('diproses');
            $table->string('bukti_bayar')->nullable();
        });

        Schema::create('detail_pesanan_produk', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('pesanan_produk_id');
            $table->unsignedBigInteger('produk_id')->nullable();
            $table->unsignedInteger('qty')->default(1);
            $table->unsignedInteger('harga')->default(0);
            $table->unsignedInteger('subtotal')->default(0);
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

    public function test_it_cancels_unpaid_orders_older_than_the_limit(): void
    {
        [$customer] = $this->buatCustomer('Rina Kusuma');
        $produkLama = $this->buatPesananProduk($customer, 'PSN-P-300', 60000, ['tanggal_pesan' => $this->hariLalu(3)]);
        $jasaLama = $this->buatPesananJasa($customer, 'PSN-J-300', 80000, ['tanggal_pesan' => $this->hariLalu(2)]);

        $this->artisan('pesanan:batalkan-kedaluwarsa')
            ->expectsOutputToContain('Pesanan produk dibatalkan: 1')
            ->expectsOutputToContain('Pesanan jasa dibatalkan: 1')
            ->assertSuccessful();

        $this->assertDatabaseHas('pesanan_produk', ['id' => $produkLama, 'status_pesanan' => 'dibatalkan']);
        $this->assertDatabaseHas('pesanan_jasa', ['id' => $jasaLama, 'status_pesanan' => 'dibatalkan']);
    }

    public function test_recent_orders_are_kept(): void
    {
        [$customer] = $this->buatCustomer('Satria Nugraha');
        $hariIni = $this->buatPesananProduk($customer, 'PSN-P-301', 60000, ['tanggal_pesan' => $this->hariLalu(0)]);
        $kemarin = $this->buatPesananJasa($customer, 'PSN-J-301', 80000, ['tanggal_pesan' => $this->hariLalu(1)]);

        $this->artisan('pesanan:batalkan-kedaluwarsa')->assertSuccessful();

        $this->assertDatabaseHas('pesanan_produk', ['id' => $hariIni, 'status_pesanan' => 'diproses']);
        $this->assertDatabaseHas('pesanan_jasa', ['id' => $kemarin, 'status_pesanan' => 'diproses']);
    }

    public function test_paid_waiting_verification_and_cod_orders_are_never_cancelled(): void
    {
        [$customer] = $this->buatCustomer('Tari Anggraini');
        $lama = $this->hariLalu(10);

        $lunas = $this->buatPesananProduk($customer, 'PSN-P-310', 60000, ['tanggal_pesan' => $lama, 'status_pembayaran' => 'lunas']);
        $verifikasi = $this->buatPesananProduk($customer, 'PSN-P-311', 60000, ['tanggal_pesan' => $lama, 'status_pembayaran' => 'menunggu_verifikasi']);
        $cod = $this->buatPesananProduk($customer, 'PSN-P-312', 60000, ['tanggal_pesan' => $lama, 'metode_pembayaran' => 'cod']);
        $dikirim = $this->buatPesananProduk($customer, 'PSN-P-313', 60000, ['tanggal_pesan' => $lama, 'status_pesanan' => 'dikirim']);
        $jasaLunas = $this->buatPesananJasa($customer, 'PSN-J-310', 80000, ['tanggal_pesan' => $lama, 'status_pembayaran' => 'lunas']);
        $jasaCod = $this->buatPesananJasa($customer, 'PSN-J-311', 80000, ['tanggal_pesan' => $lama, 'metode_pembayaran' => 'cod']);

        $this->artisan('pesanan:batalkan-kedaluwarsa')->assertSuccessful();

        foreach ([$lunas, $verifikasi, $cod] as $id) {
            $this->assertNotSame('dibatalkan', DB::table('pesanan_produk')->where('id', $id)->value('status_pesanan'));
        }
        $this->assertDatabaseHas('pesanan_produk', ['id' => $dikirim, 'status_pesanan' => 'dikirim']);
        foreach ([$jasaLunas, $jasaCod] as $id) {
            $this->assertNotSame('dibatalkan', DB::table('pesanan_jasa')->where('id', $id)->value('status_pesanan'));
        }
    }

    public function test_dry_run_changes_nothing(): void
    {
        [$customer] = $this->buatCustomer('Umar Faruq');
        $id = $this->buatPesananProduk($customer, 'PSN-P-320', 60000, ['tanggal_pesan' => $this->hariLalu(5)]);

        $this->artisan('pesanan:batalkan-kedaluwarsa', ['--dry-run' => true])
            ->expectsOutputToContain('Pesanan produk yang akan dibatalkan: 1')
            ->assertSuccessful();

        $this->assertDatabaseHas('pesanan_produk', ['id' => $id, 'status_pesanan' => 'diproses']);
    }

    public function test_running_it_twice_cancels_nothing_new(): void
    {
        [$customer] = $this->buatCustomer('Vina Oktaviani');
        $this->buatPesananProduk($customer, 'PSN-P-330', 60000, ['tanggal_pesan' => $this->hariLalu(4)]);

        $this->artisan('pesanan:batalkan-kedaluwarsa')->expectsOutputToContain('Pesanan produk dibatalkan: 1')->assertSuccessful();
        $this->artisan('pesanan:batalkan-kedaluwarsa')->expectsOutputToContain('Pesanan produk dibatalkan: 0')->assertSuccessful();
    }

    public function test_the_day_limit_can_be_changed(): void
    {
        [$customer] = $this->buatCustomer('Wawan Hadi');
        $kemarin = $this->buatPesananProduk($customer, 'PSN-P-340', 60000, ['tanggal_pesan' => $this->hariLalu(1)]);

        $this->artisan('pesanan:batalkan-kedaluwarsa', ['--hari' => 1])->assertSuccessful();

        $this->assertDatabaseHas('pesanan_produk', ['id' => $kemarin, 'status_pesanan' => 'dibatalkan']);
    }

    public function test_an_invalid_day_limit_is_rejected(): void
    {
        $this->artisan('pesanan:batalkan-kedaluwarsa', ['--hari' => 0])
            ->expectsOutputToContain('Nilai --hari minimal 1.')
            ->assertFailed();
    }

    private function hariLalu(int $hari): string
    {
        return now()->subDays($hari)->toDateString();
    }

    private function buatCustomer(string $nama): array
    {
        $akunId = DB::table('akun')->insertGetId([
            'nama' => $nama,
            'email' => strtolower(str_replace([' ', '.'], ['.', '.'], $nama)).'@example.test',
            'password' => Hash::make('password'),
            'role' => 'customer',
        ]);
        $customerId = DB::table('customer')->insertGetId(['akun_id' => $akunId, 'alamat' => 'Jl. Melati 1']);

        return [$customerId, $akunId];
    }

    private function buatPesananProduk(int $customerId, string $kode, int $total, array $tambahan = []): int
    {
        $id = DB::table('pesanan_produk')->insertGetId(array_merge([
            'kode' => $kode,
            'customer_id' => $customerId,
            'tanggal_pesan' => '2026-10-26',
            'metode_pembayaran' => 'transfer',
            'alamat_pengiriman' => 'Jl. Kenanga 2',
            'total_bayar' => $total,
            'ongkir' => 15000,
        ], $tambahan));

        DB::table('detail_pesanan_produk')->insert([
            'pesanan_produk_id' => $id,
            'produk_id' => null,
            'qty' => 2,
            'harga' => intdiv($total, 2),
            'subtotal' => $total,
        ]);

        return $id;
    }

    private function buatPesananJasa(int $customerId, string $kode, int $total, array $tambahan = []): int
    {
        $id = DB::table('pesanan_jasa')->insertGetId(array_merge([
            'kode' => $kode,
            'customer_id' => $customerId,
            'tanggal_pesan' => '2026-10-26',
            'metode_pembayaran' => 'qris',
            'alamat_lokasi' => 'Perum Griya Asri',
            'total_bayar' => $total,
        ], $tambahan));

        DB::table('detail_pesanan_jasa')->insert([
            'pesanan_jasa_id' => $id,
            'jasa_id' => null,
            'qty' => 1,
            'harga' => $total,
            'subtotal' => $total,
        ]);

        return $id;
    }

    private function createAccount(string $name, string $role): Akun
    {
        $accountId = DB::table('akun')->insertGetId([
            'nama' => $name,
            'email' => strtolower(str_replace(' ', '.', $name)).'@example.test',
            'password' => Hash::make('password'),
            'role' => $role,
        ]);

        return Akun::findOrFail($accountId);
    }
}
