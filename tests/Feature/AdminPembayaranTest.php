<?php

namespace Tests\Feature;

use App\Models\Akun;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AdminPembayaranTest extends TestCase
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

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get(route('admin.pembayaran.index'))
            ->assertRedirect(route('login'));
    }

    public function test_non_admin_users_cannot_browse_payments(): void
    {
        $this->actingAs($this->createAccount('Pelanggan', 'customer'))
            ->get(route('admin.pembayaran.index'))
            ->assertForbidden();
    }

    public function test_admin_sees_payment_worklist_and_summary(): void
    {
        $admin = $this->createAccount('Admin Green Plant', 'admin');
        [$customer] = $this->buatCustomer('Bayu Aji');
        $this->buatPesananProduk($customer, 'PSN-P-100', 55000, [
            'status_pembayaran' => 'menunggu_verifikasi',
            'bukti_bayar' => 'bukti-bayar/p100.png',
        ]);
        $this->buatPesananJasa($customer, 'PSN-J-100', 85000, [
            'metode_pembayaran' => 'cod',
        ]);

        $this->withoutVite()
            ->actingAs($admin)
            ->get(route('admin.pembayaran.index'))
            ->assertOk()
            ->assertSee('PSN-P-100')
            ->assertSee('Bayu Aji')
            ->assertSee('Verifikasi')
            ->assertDontSee('PSN-J-100')
            ->assertSee('Menunggu Bayar')
            ->assertSee('Rp 85.000');
    }

    public function test_admin_can_filter_payments_by_type_and_status(): void
    {
        $admin = $this->createAccount('Admin Green Plant', 'admin');
        [$customer] = $this->buatCustomer('Citra Ayu');
        $this->buatPesananProduk($customer, 'PSN-P-101', 35000, ['status_pembayaran' => 'lunas']);
        $this->buatPesananJasa($customer, 'PSN-J-101', 95000, ['metode_pembayaran' => 'cod']);

        $this->actingAs($admin)
            ->get(route('admin.pembayaran.index', ['jenis' => 'lunas']))
            ->assertOk()
            ->assertSee('PSN-P-101')
            ->assertDontSee('PSN-J-101');

        $this->actingAs($admin)
            ->get(route('admin.pembayaran.index', ['jenis' => 'menunggu_bayar', 'tipe' => 'jasa']))
            ->assertOk()
            ->assertSee('PSN-J-101')
            ->assertSee('Konfirmasi COD')
            ->assertDontSee('PSN-P-101');
    }

    public function test_quick_action_stays_on_the_payment_page(): void
    {
        $admin = $this->createAccount('Admin Green Plant', 'admin');
        [$customer] = $this->buatCustomer('Dodi Rahmat');
        $id = $this->buatPesananJasa($customer, 'PSN-J-102', 70000, [
            'status_pembayaran' => 'menunggu_verifikasi',
            'bukti_bayar' => 'bukti-bayar/j102.png',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.pesanan.jasa.aksi', $id), [
                'dari' => 'pembayaran',
                'aksi' => 'verifikasi_bayar',
            ])
            ->assertRedirect(route('admin.pembayaran.index'))
            ->assertSessionHas('status', 'Pembayaran berhasil diverifikasi.');

        $this->assertDatabaseHas('pesanan_jasa', [
            'id' => $id,
            'status_pembayaran' => 'lunas',
        ]);
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

    /**
     * @return array<int, int>
     */
    private function buatCustomer(string $nama): array
    {
        $akunId = DB::table('akun')->insertGetId([
            'nama' => $nama,
            'email' => strtolower(str_replace(' ', '.', $nama)).'.c@example.test',
            'password' => Hash::make('password'),
            'role' => 'customer',
        ]);
        $customerId = DB::table('customer')->insertGetId(['akun_id' => $akunId]);

        return [$customerId, $akunId];
    }

    private function buatPesananProduk(int $customerId, string $kode, int $total, array $tambahan = []): int
    {
        return DB::table('pesanan_produk')->insertGetId(array_merge([
            'kode' => $kode,
            'customer_id' => $customerId,
            'tanggal_pesan' => '2026-10-26',
            'metode_pembayaran' => 'transfer',
            'alamat_pengiriman' => 'Jl. Melati 8',
            'total_bayar' => $total,
            'ongkir' => 15000,
        ], $tambahan));
    }

    private function buatPesananJasa(int $customerId, string $kode, int $total, array $tambahan = []): int
    {
        return DB::table('pesanan_jasa')->insertGetId(array_merge([
            'kode' => $kode,
            'customer_id' => $customerId,
            'tanggal_pesan' => '2026-10-26',
            'metode_pembayaran' => 'qris',
            'alamat_lokasi' => 'Perum Griya Asri',
            'total_bayar' => $total,
        ], $tambahan));
    }
}
