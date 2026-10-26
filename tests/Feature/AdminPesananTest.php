<?php

namespace Tests\Feature;

use App\Models\Akun;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AdminPesananTest extends TestCase
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

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get(route('admin.pesanan.index'))
            ->assertRedirect(route('login'));
    }

    public function test_non_admin_users_cannot_browse_orders(): void
    {
        $this->actingAs($this->createAccount('Pelanggan', 'customer'))
            ->get(route('admin.pesanan.index'))
            ->assertForbidden();
    }

    public function test_admin_sees_merged_product_and_service_orders(): void
    {
        $admin = $this->createAccount('Admin Green Plant', 'admin');
        [$customer] = $this->buatCustomer('Budi Santoso');
        $this->buatPesananProduk($customer, 'PSN-P-001', 60000);
        $this->buatPesananJasa($customer, 'PSN-J-001', 80000);

        $this->actingAs($admin)
            ->get(route('admin.pesanan.index'))
            ->assertOk()
            ->assertSee('PSN-P-001')
            ->assertSee('PSN-J-001')
            ->assertSee('Budi Santoso');
    }

    public function test_admin_can_filter_orders_by_type_and_status(): void
    {
        $admin = $this->createAccount('Admin Green Plant', 'admin');
        [$customer] = $this->buatCustomer('Sari Dewi');
        $this->buatPesananProduk($customer, 'PSN-P-002', 50000);
        $this->buatPesananJasa($customer, 'PSN-J-002', 70000);

        $this->actingAs($admin)
            ->get(route('admin.pesanan.index', ['tipe' => 'jasa']))
            ->assertOk()
            ->assertSee('PSN-J-002')
            ->assertDontSee('PSN-P-002');

        $this->actingAs($admin)
            ->get(route('admin.pesanan.index', ['status' => 'lunas']))
            ->assertOk()
            ->assertDontSee('PSN-P-002')
            ->assertDontSee('PSN-J-002');
    }

    public function test_admin_can_verify_upload_payment_proof_for_product_order(): void
    {
        $admin = $this->createAccount('Admin Green Plant', 'admin');
        [$customer] = $this->buatCustomer('Andi Wijaya');
        $id = $this->buatPesananProduk($customer, 'PSN-P-003', 45000, [
            'status_pembayaran' => 'menunggu_verifikasi',
            'bukti_bayar' => 'bukti-bayar/fake.png',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.pesanan.produk.aksi', $id), ['aksi' => 'verifikasi_bayar'])
            ->assertRedirect(route('admin.pesanan.index', ['tipe' => 'produk']))
            ->assertSessionHas('status', 'Pembayaran berhasil diverifikasi.');

        $this->assertDatabaseHas('pesanan_produk', [
            'id' => $id,
            'status_pembayaran' => 'lunas',
            'admin_id' => null,
        ]);
        $this->assertNotNull(DB::table('pesanan_produk')->find($id)->tanggal_bayar);
    }

    public function test_admin_can_reject_upload_payment_proof(): void
    {
        $admin = $this->createAccount('Admin Green Plant', 'admin');
        [$customer] = $this->buatCustomer('Rina Kusuma');
        $id = $this->buatPesananJasa($customer, 'PSN-J-003', 65000, [
            'status_pembayaran' => 'menunggu_verifikasi',
            'bukti_bayar' => 'bukti-bayar/fake.png',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.pesanan.jasa.aksi', $id), ['aksi' => 'tolak_bayar'])
            ->assertRedirect(route('admin.pesanan.index', ['tipe' => 'jasa']))
            ->assertSessionHas('status', 'Bukti bayar ditolak. Pesanan kembali menunggu pembayaran.');

        $this->assertDatabaseHas('pesanan_jasa', [
            'id' => $id,
            'status_pembayaran' => 'menunggu_bayar',
        ]);
    }

    public function test_admin_can_mark_a_paid_product_order_as_shipped_and_finished(): void
    {
        $admin = $this->createAccount('Admin Green Plant', 'admin');
        [$customer] = $this->buatCustomer('Tono Hartanto');
        $id = $this->buatPesananProduk($customer, 'PSN-P-004', 120000, [
            'status_pembayaran' => 'lunas',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.pesanan.produk.aksi', $id), ['aksi' => 'dikirim'])
            ->assertSessionHas('status', 'Pesanan ditandai sudah dikirim.');

        $this->assertDatabaseHas('pesanan_produk', [
            'id' => $id,
            'status_pesanan' => 'dikirim',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.pesanan.produk.aksi', $id), ['dari' => 'detail', 'aksi' => 'selesai'])
            ->assertRedirect(route('admin.pesanan.produk.show', $id))
            ->assertSessionHas('status', 'Pesanan ditandai selesai.');

        $this->assertDatabaseHas('pesanan_produk', [
            'id' => $id,
            'status_pesanan' => 'selesai',
        ]);
    }

    public function test_admin_can_finish_a_paid_service_order(): void
    {
        $admin = $this->createAccount('Admin Green Plant', 'admin');
        [$customer] = $this->buatCustomer('Gita Maharani');
        $id = $this->buatPesananJasa($customer, 'PSN-J-004', 90000, [
            'status_pembayaran' => 'lunas',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.pesanan.jasa.aksi', $id), ['aksi' => 'selesai'])
            ->assertSessionHas('status', 'Pesanan ditandai selesai.');

        $this->assertDatabaseHas('pesanan_jasa', [
            'id' => $id,
            'status_pesanan' => 'selesai',
        ]);
    }

    public function test_admin_cannot_ship_an_unpaid_order_or_cancel_a_paid_one(): void
    {
        $admin = $this->createAccount('Admin Green Plant', 'admin');
        [$customer] = $this->buatCustomer('Wawan Pratama');
        $belumLunas = $this->buatPesananProduk($customer, 'PSN-P-005', 30000, [
            'status_pembayaran' => 'menunggu_bayar',
        ]);
        $lunas = $this->buatPesananProduk($customer, 'PSN-P-006', 46000, [
            'status_pembayaran' => 'lunas',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.pesanan.produk.aksi', $belumLunas), ['aksi' => 'dikirim'])
            ->assertSessionHas('error');

        $this->assertDatabaseHas('pesanan_produk', [
            'id' => $belumLunas,
            'status_pesanan' => 'diproses',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.pesanan.produk.aksi', $lunas), ['aksi' => 'batalkan'])
            ->assertSessionHas('error');

        $this->assertDatabaseMissing('pesanan_produk', [
            'id' => $lunas,
            'status_pesanan' => 'dibatalkan',
        ]);
    }

    public function test_admin_cannot_access_order_details_for_non_admin_actions(): void
    {
        $customer = $this->createAccount('Dewi Anggraini', 'customer');
        [$customerData] = $this->buatCustomer('Dewi Anggraini Akun Customer');
        $id = $this->buatPesananProduk($customerData, 'PSN-P-007', 20000);

        $this->actingAs($customer)
            ->get(route('admin.pesanan.produk.show', $id))
            ->assertForbidden();

        $this->actingAs($customer)
            ->post(route('admin.pesanan.produk.aksi', $id), ['aksi' => 'verifikasi_bayar'])
            ->assertForbidden();
    }

    /**
     * @return array<int, mixed> Simulasi DB table rows
     */
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
