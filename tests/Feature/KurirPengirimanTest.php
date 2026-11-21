<?php

namespace Tests\Feature;

use App\Models\Akun;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class KurirPengirimanTest extends TestCase
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
            $table->unsignedBigInteger('kurir_id')->nullable();
            $table->date('tanggal_pesan');
            $table->date('tanggal_bayar')->nullable();
            $table->date('tanggal_kirim')->nullable();
            $table->timestamp('tanggal_selesai')->nullable();
            $table->string('metode_pembayaran');
            $table->string('alamat_pengiriman');
            $table->unsignedInteger('total_bayar')->default(0);
            $table->unsignedInteger('ongkir')->default(0);
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

        Schema::create('produk', function (Blueprint $table): void {
            $table->id();
            $table->string('nama');
            $table->unsignedInteger('stok')->default(0);
        });
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get(route('kurir.index'))->assertRedirect(route('login'));
    }

    public function test_admin_and_customer_cannot_open_courier_pages(): void
    {
        $this->actingAs($this->createAccount('Admin Green Plant', 'admin'))
            ->get(route('kurir.index'))
            ->assertForbidden();

        $this->actingAs($this->createAccount('Pelanggan', 'customer'))
            ->get(route('kurir.index'))
            ->assertForbidden();
    }

    public function test_courier_account_without_employee_record_is_forbidden(): void
    {
        $this->actingAs($this->createAccount('Kurir Tanpa Data', 'kurir'))
            ->get(route('kurir.index'))
            ->assertForbidden();
    }

    public function test_courier_login_is_redirected_to_courier_page(): void
    {
        $this->buatKurir('Rizal', 'Rizal Kurir');

        $this->post(route('login'), ['email' => 'rizal.k@example.test', 'password' => 'password'])
            ->assertRedirect(route('kurir.index'));
    }

    public function test_courier_sees_only_own_shipments(): void
    {
        [$customer] = $this->buatCustomer('Bayu Aji');
        $rizal = $this->buatKurir('Rizal', 'Rizal Kurir');
        $dewi = $this->buatKurir('Dewi', 'Dewi Kurir');

        $this->buatPesananProduk($customer, 'PSN-K-001', 65000, ['status_pesanan' => 'dikirim', 'kurir_id' => $rizal]);
        $this->buatPesananProduk($customer, 'PSN-K-002', 45000, ['status_pesanan' => 'dikirim', 'kurir_id' => $dewi]);
        $this->buatPesananProduk($customer, 'PSN-K-003', 55000, ['status_pesanan' => 'dibayar', 'kurir_id' => $rizal]);
        $this->buatPesananProduk($customer, 'PSN-K-004', 35000, ['status_pesanan' => 'selesai', 'kurir_id' => $rizal]);

        $this->withoutVite()
            ->actingAs($this->akunKurir($rizal))
            ->get(route('kurir.index'))
            ->assertOk()
            ->assertSee('PSN-K-001')
            ->assertSee('Bayu Aji')
            ->assertDontSee('PSN-K-002')
            ->assertDontSee('PSN-K-003')
            ->assertDontSee('PSN-K-004');

        $this->withoutVite()
            ->actingAs($this->akunKurir($rizal))
            ->get(route('kurir.index', ['status' => 'selesai']))
            ->assertOk()
            ->assertSee('PSN-K-004')
            ->assertDontSee('PSN-K-001');
    }

    public function test_courier_can_open_own_order_but_not_another_couriers(): void
    {
        [$customer] = $this->buatCustomer('Citra Ayu');
        $rizal = $this->buatKurir('Rizal', 'Rizal Kurir');
        $dewi = $this->buatKurir('Dewi', 'Dewi Kurir');

        $milikRizal = $this->buatPesananProduk($customer, 'PSN-K-010', 75000, ['status_pesanan' => 'dikirim', 'kurir_id' => $rizal]);
        $milikDewi = $this->buatPesananProduk($customer, 'PSN-K-011', 75000, ['status_pesanan' => 'dikirim', 'kurir_id' => $dewi]);

        $this->withoutVite()
            ->actingAs($this->akunKurir($rizal))
            ->get(route('kurir.show', $milikRizal))
            ->assertOk()
            ->assertSee('PSN-K-010')
            ->assertSee('Jl. Melati 8')
            ->assertSee('Tandai Terkirim');

        $this->actingAs($this->akunKurir($rizal))
            ->get(route('kurir.show', $milikDewi))
            ->assertForbidden();
    }

    public function test_courier_can_mark_own_shipment_as_finished(): void
    {
        [$customer] = $this->buatCustomer('Eka Putri');
        $rizal = $this->buatKurir('Rizal', 'Rizal Kurir');
        $produkId = DB::table('produk')->insertGetId(['nama' => 'Pot Tanah', 'stok' => 5]);
        $id = $this->buatPesananProduk($customer, 'PSN-K-020', 60000, [
            'status_pembayaran' => 'lunas',
            'status_pesanan' => 'dikirim',
            'kurir_id' => $rizal,
        ]);
        DB::table('detail_pesanan_produk')->insert([
            'pesanan_produk_id' => $id, 'produk_id' => $produkId, 'qty' => 2, 'harga' => 22500, 'subtotal' => 45000,
        ]);

        $this->actingAs($this->akunKurir($rizal))
            ->post(route('kurir.selesai', $id))
            ->assertRedirect(route('kurir.index'))
            ->assertSessionHas('status', 'Pesanan PSN-K-020 ditandai sudah diterima customer.');

        $this->assertDatabaseHas('pesanan_produk', [
            'id' => $id,
            'status_pesanan' => 'selesai',
            'status_pembayaran' => 'lunas',
        ]);
        $this->assertNotNull(DB::table('pesanan_produk')->where('id', $id)->value('tanggal_selesai'));
        // Stok tidak disentuh kurir
        $this->assertSame(5, (int) DB::table('produk')->where('id', $produkId)->value('stok'));
    }

    public function test_courier_cannot_finish_another_couriers_order(): void
    {
        [$customer] = $this->buatCustomer('Fajar Nur');
        $rizal = $this->buatKurir('Rizal', 'Rizal Kurir');
        $dewi = $this->buatKurir('Dewi', 'Dewi Kurir');
        $id = $this->buatPesananProduk($customer, 'PSN-K-030', 50000, [
            'status_pembayaran' => 'lunas',
            'status_pesanan' => 'dikirim',
            'kurir_id' => $dewi,
        ]);

        $this->actingAs($this->akunKurir($rizal))
            ->post(route('kurir.selesai', $id))
            ->assertForbidden();

        $this->assertDatabaseHas('pesanan_produk', ['id' => $id, 'status_pesanan' => 'dikirim']);
    }

    public function test_courier_cannot_finish_an_order_that_is_not_being_shipped(): void
    {
        [$customer] = $this->buatCustomer('Gita Sari');
        $rizal = $this->buatKurir('Rizal', 'Rizal Kurir');
        $id = $this->buatPesananProduk($customer, 'PSN-K-040', 50000, [
            'status_pembayaran' => 'lunas',
            'status_pesanan' => 'dibayar',
            'kurir_id' => $rizal,
        ]);

        $this->actingAs($this->akunKurir($rizal))
            ->post(route('kurir.selesai', $id))
            ->assertRedirect(route('kurir.show', $id))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('pesanan_produk', ['id' => $id, 'status_pesanan' => 'dibayar']);
    }

    private function akunKurir(int $karyawanId): Akun
    {
        return Akun::findOrFail(DB::table('karyawan')->where('id', $karyawanId)->value('akun_id'));
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

    private function buatKurir(string $username, string $nama): int
    {
        $akunId = DB::table('akun')->insertGetId([
            'nama' => $nama,
            'email' => strtolower(str_replace(' ', '.', $username)).'.k@example.test',
            'password' => Hash::make('password'),
            'role' => 'kurir',
        ]);

        return DB::table('karyawan')->insertGetId(['akun_id' => $akunId, 'username' => $username]);
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
}
