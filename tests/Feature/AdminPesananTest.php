<?php

namespace Tests\Feature;

use App\Models\Akun;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\Concerns\MembuatSkemaErd;
use Tests\TestCase;

class AdminPesananTest extends TestCase
{
    use MembuatSkemaErd;

    protected function setUp(): void
    {
        parent::setUp();

        $this->buatSkemaErd();
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

    public function test_verifying_payment_of_product_order_marks_it_paid_and_reduces_stock(): void
    {
        $admin = $this->createAccount('Admin Green Plant', 'admin');
        [$customer] = $this->buatCustomer('Andi Wijaya');
        $produkId = $this->buatProduk(10);
        $id = $this->buatPesananProduk($customer, 'PSN-P-003', 45000, [
            'status_pembayaran' => 'menunggu_verifikasi',
            'bukti_bayar' => 'bukti-bayar/fake.png',
        ], $produkId, 2);

        $this->actingAs($admin)
            ->post(route('admin.pesanan.produk.aksi', $id), ['aksi' => 'verifikasi_bayar'])
            ->assertRedirect(route('admin.pesanan.index', ['tipe' => 'produk']))
            ->assertSessionHas('status', 'Pembayaran berhasil diverifikasi.');

        $this->assertDatabaseHas('pesanan_produk', [
            'id' => $id,
            'status_pembayaran' => 'lunas',
            'status_pesanan' => 'dibayar',
            'admin_id' => null,
        ]);
        $this->assertNotNull(DB::table('pesanan_produk')->find($id)->tanggal_bayar);
        $this->assertSame(8, (int) DB::table('produk')->where('id', $produkId)->value('stok'));
    }

    public function test_payment_is_not_verified_when_stock_is_insufficient(): void
    {
        $admin = $this->createAccount('Admin Green Plant', 'admin');
        [$customer] = $this->buatCustomer('Lina Marlina');
        $produkId = $this->buatProduk(1);
        $id = $this->buatPesananProduk($customer, 'PSN-P-008', 45000, [
            'status_pembayaran' => 'menunggu_verifikasi',
            'bukti_bayar' => 'bukti-bayar/fake.png',
        ], $produkId, 2);

        $this->actingAs($admin)
            ->post(route('admin.pesanan.produk.aksi', $id), ['aksi' => 'verifikasi_bayar'])
            ->assertSessionHas('error');

        $this->assertDatabaseHas('pesanan_produk', [
            'id' => $id,
            'status_pembayaran' => 'menunggu_verifikasi',
            'status_pesanan' => 'menunggu_pembayaran',
        ]);
        $this->assertSame(1, (int) DB::table('produk')->where('id', $produkId)->value('stok'));
    }

    public function test_admin_can_confirm_cod_for_product_order(): void
    {
        $admin = $this->createAccount('Admin Green Plant', 'admin');
        [$customer] = $this->buatCustomer('Hari Susanto');
        $produkId = $this->buatProduk(5);
        $id = $this->buatPesananProduk($customer, 'PSN-P-009', 30000, [
            'metode_pembayaran' => 'cod',
        ], $produkId, 3);

        $this->actingAs($admin)
            ->post(route('admin.pesanan.produk.aksi', $id), ['aksi' => 'konfirmasi_cod'])
            ->assertSessionHas('status', 'Pembayaran COD berhasil dikonfirmasi.');

        $this->assertDatabaseHas('pesanan_produk', [
            'id' => $id,
            'status_pembayaran' => 'lunas',
            'status_pesanan' => 'dibayar',
        ]);
        $this->assertSame(2, (int) DB::table('produk')->where('id', $produkId)->value('stok'));
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
            'status_pesanan' => 'dibayar',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.pesanan.produk.aksi', $id), ['aksi' => 'dikirim'])
            ->assertSessionHas('status', 'Pesanan ditandai sudah dikirim.');

        $this->assertDatabaseHas('pesanan_produk', [
            'id' => $id,
            'status_pesanan' => 'dikirim',
        ]);
        $this->assertNotNull(DB::table('pesanan_produk')->find($id)->tanggal_kirim);

        $this->actingAs($admin)
            ->post(route('admin.pesanan.produk.aksi', $id), ['dari' => 'detail', 'aksi' => 'selesai'])
            ->assertRedirect(route('admin.pesanan.produk.show', $id))
            ->assertSessionHas('status', 'Pesanan ditandai selesai.');

        $this->assertDatabaseHas('pesanan_produk', [
            'id' => $id,
            'status_pesanan' => 'selesai',
        ]);
    }

    public function test_service_order_goes_through_scheduled_in_progress_and_finished(): void
    {
        $admin = $this->createAccount('Admin Green Plant', 'admin');
        [$customer] = $this->buatCustomer('Gita Maharani');
        $id = $this->buatPesananJasa($customer, 'PSN-J-004', 90000, [
            'status_pembayaran' => 'lunas',
        ]);

        // Belum dijadwalkan: tidak bisa langsung selesai
        $this->actingAs($admin)
            ->post(route('admin.pesanan.jasa.aksi', $id), ['aksi' => 'selesai'])
            ->assertSessionHas('error');

        $this->actingAs($admin)
            ->post(route('admin.pesanan.jasa.aksi', $id), ['aksi' => 'jadwalkan'])
            ->assertSessionHas('status', 'Pesanan jasa dijadwalkan.');
        $this->assertDatabaseHas('pesanan_jasa', ['id' => $id, 'status_pesanan' => 'dijadwalkan']);

        $this->actingAs($admin)
            ->post(route('admin.pesanan.jasa.aksi', $id), ['aksi' => 'kerjakan'])
            ->assertSessionHas('status', 'Pengerjaan jasa dimulai.');
        $this->assertDatabaseHas('pesanan_jasa', ['id' => $id, 'status_pesanan' => 'dikerjakan']);

        $this->actingAs($admin)
            ->post(route('admin.pesanan.jasa.aksi', $id), ['aksi' => 'selesai'])
            ->assertSessionHas('status', 'Pesanan ditandai selesai.');
        $this->assertDatabaseHas('pesanan_jasa', ['id' => $id, 'status_pesanan' => 'selesai']);
        $this->assertNotNull(DB::table('pesanan_jasa')->find($id)->tanggal_selesai);
    }

    public function test_unpaid_service_order_cannot_be_scheduled(): void
    {
        $admin = $this->createAccount('Admin Green Plant', 'admin');
        [$customer] = $this->buatCustomer('Nia Ramadhani');
        $id = $this->buatPesananJasa($customer, 'PSN-J-005', 50000);

        $this->actingAs($admin)
            ->post(route('admin.pesanan.jasa.aksi', $id), ['aksi' => 'jadwalkan'])
            ->assertSessionHas('error');

        $this->assertDatabaseHas('pesanan_jasa', ['id' => $id, 'status_pesanan' => 'menunggu_konfirmasi']);
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
            'status_pesanan' => 'dibayar',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.pesanan.produk.aksi', $belumLunas), ['aksi' => 'dikirim'])
            ->assertSessionHas('error');

        $this->assertDatabaseHas('pesanan_produk', [
            'id' => $belumLunas,
            'status_pesanan' => 'menunggu_pembayaran',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.pesanan.produk.aksi', $lunas), ['aksi' => 'batalkan'])
            ->assertSessionHas('error');

        $this->assertDatabaseMissing('pesanan_produk', [
            'id' => $lunas,
            'status_pesanan' => 'dibatalkan',
        ]);
    }

    public function test_admin_can_cancel_an_unpaid_order(): void
    {
        $admin = $this->createAccount('Admin Green Plant', 'admin');
        [$customer] = $this->buatCustomer('Yuni Astuti');
        $id = $this->buatPesananProduk($customer, 'PSN-P-010', 25000);

        $this->actingAs($admin)
            ->post(route('admin.pesanan.produk.aksi', $id), ['aksi' => 'batalkan'])
            ->assertSessionHas('status', 'Pesanan berhasil dibatalkan.');

        $this->assertDatabaseHas('pesanan_produk', ['id' => $id, 'status_pesanan' => 'dibatalkan']);
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
     * @return array<int, int> [customer_id, akun_id]
     */
    private function buatCustomer(string $nama): array
    {
        $akunId = DB::table('akun')->insertGetId([
            'nama' => $nama,
            'email' => strtolower(str_replace(' ', '.', $nama)).'@example.test',
            'password' => Hash::make('password'),
            'role' => 'customer',
        ]);
        $customerId = DB::table('customer')->insertGetId(['akun_id' => $akunId, 'alamat' => 'Jl. Melati 1']);

        return [$customerId, $akunId];
    }

    private function buatProduk(int $stok): int
    {
        $kategoriId = DB::table('kategori')->where('nama', 'Pot')->where('tipe', 'produk')->value('id')
            ?? DB::table('kategori')->insertGetId(['nama' => 'Pot', 'tipe' => 'produk']);

        return DB::table('produk')->insertGetId([
            'kategori_id' => $kategoriId,
            'nama' => 'Pot Tanah Liat '.uniqid(),
            'harga' => 20000,
            'stok' => $stok,
        ]);
    }

    private function buatPesananProduk(int $customerId, string $kode, int $total, array $tambahan = [], ?int $produkId = null, int $qty = 2): int
    {
        $produkId ??= $this->buatProduk(10);

        $id = DB::table('pesanan_produk')->insertGetId(array_merge([
            'kode' => $kode,
            'customer_id' => $customerId,
            'tanggal_pesan' => '2026-10-26',
            'metode_pembayaran' => 'transfer',
            'alamat_pengiriman' => 'Jl. Kenanga 2',
            'total_bayar' => $total,
        ], $tambahan));

        DB::table('detail_pesanan_produk')->insert([
            'pesanan_produk_id' => $id,
            'produk_id' => $produkId,
            'qty' => $qty,
            'harga' => intdiv($total, $qty),
            'subtotal' => $total,
        ]);

        return $id;
    }

    private function buatPesananJasa(int $customerId, string $kode, int $total, array $tambahan = []): int
    {
        $kategoriId = DB::table('kategori')->where('nama', 'Perawatan Taman')->where('tipe', 'jasa')->value('id')
            ?? DB::table('kategori')->insertGetId(['nama' => 'Perawatan Taman', 'tipe' => 'jasa']);
        $jasaId = DB::table('jasa')->insertGetId([
            'kategori_id' => $kategoriId,
            'nama' => 'Perawatan '.uniqid(),
            'harga' => $total,
            'satuan' => 'paket',
        ]);

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
            'jasa_id' => $jasaId,
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
