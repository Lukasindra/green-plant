<?php

namespace Tests\Feature;

use App\Models\Akun;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\Concerns\MembuatSkemaErd;
use Tests\TestCase;

class AdminPengirimanTest extends TestCase
{
    use MembuatSkemaErd;

    protected function setUp(): void
    {
        parent::setUp();

        $this->buatSkemaErd();
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get(route('admin.pengiriman.index'))
            ->assertRedirect(route('login'));
    }

    public function test_non_admin_users_cannot_browse_shipments(): void
    {
        $this->actingAs($this->createAccount('Pelanggan', 'customer'))
            ->get(route('admin.pengiriman.index'))
            ->assertForbidden();
    }

    public function test_admin_sees_paid_orders_ready_to_ship(): void
    {
        $admin = $this->createAccount('Admin Green Plant', 'admin');
        [$customer] = $this->buatCustomer('Bayu Aji');

        $this->buatPesananProduk($customer, 'PSN-P-200', 65000, ['status_pembayaran' => 'lunas', 'status_pesanan' => 'dibayar']);
        $this->buatPesananProduk($customer, 'PSN-P-201', 45000); // masih menunggu bayar
        $this->buatPesananProduk($customer, 'PSN-P-202', 55000, [
            'status_pembayaran' => 'lunas',
            'status_pesanan' => 'dikirim',
            'tanggal_kirim' => '2026-10-25',
        ]);

        $this->withoutVite()
            ->actingAs($admin)
            ->get(route('admin.pengiriman.index'))
            ->assertOk()
            ->assertSee('PSN-P-200')
            ->assertSee('Bayu Aji')
            ->assertSee('Tandai Dikirim')
            ->assertSee('Tanpa kurir')
            ->assertDontSee('PSN-P-201')
            ->assertDontSee('PSN-P-202');
    }

    public function test_admin_can_assign_courier_and_mark_as_shipped(): void
    {
        $admin = $this->createAccount('Admin Green Plant', 'admin');
        [$customer] = $this->buatCustomer('Citra Ayu');
        $kurirId = $this->buatKurir('Rizal', 'Rizal Kurir');
        $id = $this->buatPesananProduk($customer, 'PSN-P-210', 75000, ['status_pembayaran' => 'lunas', 'status_pesanan' => 'dibayar']);

        // Kurir terdaftar muncul di dropdown
        $this->actingAs($admin)
            ->get(route('admin.pengiriman.index'))
            ->assertOk()
            ->assertSee('Rizal');

        $this->actingAs($admin)
            ->post(route('admin.pengiriman.aksi', $id), [
                'aksi' => 'kirim',
                'kurir_id' => $kurirId,
            ])
            ->assertRedirect(route('admin.pengiriman.index'))
            ->assertSessionHas('status', 'Pesanan PSN-P-210 ditandai sudah dikirim.');

        $this->assertDatabaseHas('pesanan_produk', [
            'id' => $id,
            'status_pesanan' => 'dikirim',
            'kurir_id' => $kurirId,
        ]);
        $this->assertNotNull(DB::table('pesanan_produk')->where('id', $id)->value('tanggal_kirim'));
    }

    public function test_unpaid_orders_cannot_be_marked_as_shipped(): void
    {
        $admin = $this->createAccount('Admin Green Plant', 'admin');
        [$customer] = $this->buatCustomer('Dodi Rahmat');
        $id = $this->buatPesananProduk($customer, 'PSN-P-211', 40000); // menunggu bayar

        $this->actingAs($admin)
            ->post(route('admin.pengiriman.aksi', $id), [
                'aksi' => 'kirim',
            ])
            ->assertRedirect(route('admin.pengiriman.index'))
            ->assertSessionHas('error', 'Aksi pengiriman tidak dapat diterapkan pada pesanan ini.');

        $this->assertDatabaseHas('pesanan_produk', [
            'id' => $id,
            'status_pembayaran' => 'menunggu_bayar',
            'status_pesanan' => 'menunggu_pembayaran',
        ]);
    }

    public function test_admin_can_finish_a_shipped_order(): void
    {
        $admin = $this->createAccount('Admin Green Plant', 'admin');
        [$customer] = $this->buatCustomer('Eka Putri');
        $id = $this->buatPesananProduk($customer, 'PSN-P-212', 60000, [
            'status_pembayaran' => 'lunas',
            'status_pesanan' => 'dikirim',
            'tanggal_kirim' => '2026-10-25',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.pengiriman.index', ['status' => 'dikirim']))
            ->assertOk()
            ->assertSee('PSN-P-212')
            ->assertSee('Tandai Selesai');

        $this->actingAs($admin)
            ->post(route('admin.pengiriman.aksi', $id), ['aksi' => 'selesai'])
            ->assertRedirect(route('admin.pengiriman.index'))
            ->assertSessionHas('status', 'Pesanan PSN-P-212 ditandai selesai.');

        $this->assertDatabaseHas('pesanan_produk', [
            'id' => $id,
            'status_pesanan' => 'selesai',
        ]);

        // Pesanan selesai masuk tab Selesai
        $this->actingAs($admin)
            ->get(route('admin.pengiriman.index', ['status' => 'selesai']))
            ->assertOk()
            ->assertSee('PSN-P-212');
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
        ], $tambahan));
    }
}
